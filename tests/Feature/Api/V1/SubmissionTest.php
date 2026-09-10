<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Issue;
use App\Models\Journal;
use App\Models\JournalAnnouncement;
use App\Models\Submission;
use App\Models\User;
use App\Models\Volume;
use App\Services\Journal\CategoryService;
use App\Support\AnnouncementType;
use App\Support\JournalActivation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_options_lists_open_calls(): void
    {
        ['call' => $call] = $this->seedOpenCall();
        $user = User::factory()->create(['email_verified_at' => now()]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/me/submissions/create-options')
            ->assertOk()
            ->assertJsonPath('data.open_calls.0.id', $call->id)
            ->assertJsonStructure([
                'data' => [
                    'open_calls',
                    'categories_by_journal',
                ],
            ]);
    }

    public function test_member_can_create_submission_via_api(): void
    {
        Storage::fake('local');

        ['call' => $call, 'journal' => $journal] = $this->seedOpenCall();
        $category = Category::query()->forJournal($journal)->where('name', 'Research Article')->firstOrFail();
        $user = User::factory()->create(['email_verified_at' => now()]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/me/submissions', [
            'announcement_id' => $call->id,
            'title' => 'API manuscript title',
            'abstract' => 'Sample abstract.',
            'category' => $category->name,
            'keywords' => 'api, testing',
            'document' => UploadedFile::fake()->create('manuscript.docx', 120, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
        ])
            ->assertCreated()
            ->assertJsonPath('data.submission.title', 'API manuscript title')
            ->assertJsonPath('data.submission.status', 'submitted');

        $this->assertDatabaseHas('submissions', [
            'author_id' => $user->id,
            'title' => 'API manuscript title',
            'status' => 'submitted',
        ]);
    }

    public function test_author_can_list_and_view_own_submissions(): void
    {
        ['call' => $call, 'journal' => $journal] = $this->seedOpenCall();
        $user = User::factory()->create(['email_verified_at' => now()]);
        $other = User::factory()->create(['email_verified_at' => now()]);

        $submission = Submission::query()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'journal_id' => $journal->id,
            'issue_id' => $call->issue_id,
            'announcement_id' => $call->id,
            'author_id' => $user->id,
            'title' => 'My submission',
            'status' => 'submitted',
            'review_type' => 'closed',
        ]);

        Submission::query()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'journal_id' => $journal->id,
            'issue_id' => $call->issue_id,
            'announcement_id' => $call->id,
            'author_id' => $other->id,
            'title' => 'Not mine',
            'status' => 'submitted',
            'review_type' => 'closed',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/me/submissions')
            ->assertOk()
            ->assertJsonPath('meta.stats.total', 1)
            ->assertJsonPath('data.0.title', 'My submission');

        $this->getJson('/api/v1/me/submissions/'.$submission->id)
            ->assertOk()
            ->assertJsonPath('data.title', 'My submission')
            ->assertJsonStructure(['data' => ['timeline', 'revisions', 'can_resubmit']]);
    }

    public function test_author_can_resubmit_revision_when_requested(): void
    {
        Storage::fake('local');

        ['call' => $call, 'journal' => $journal] = $this->seedOpenCall();
        $user = User::factory()->create(['email_verified_at' => now()]);

        $submission = Submission::query()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'journal_id' => $journal->id,
            'issue_id' => $call->issue_id,
            'announcement_id' => $call->id,
            'author_id' => $user->id,
            'title' => 'Revision manuscript',
            'status' => 'revision_requested',
            'review_type' => 'closed',
            'document_path' => 'journals/demo-journal/submissions/x/original.docx',
            'document_disk' => 'local',
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/me/submissions/'.$submission->id.'/resubmit', [
            'document' => UploadedFile::fake()->create('revision.docx', 120, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
            'notes' => 'Addressed reviewer comments.',
        ])
            ->assertOk()
            ->assertJsonPath('data.submission.status', 'resubmitted');

        $this->assertDatabaseHas('submissions', [
            'id' => $submission->id,
            'status' => 'resubmitted',
        ]);
    }

    public function test_pay_submission_fee_returns_paystack_initialize_payload(): void
    {
        config([
            'paystack.secret_key' => 'sk_platform',
            'paystack.public_key' => 'pk_platform',
            'paystack.base_url' => 'https://api.paystack.co',
            'paystack.currency' => 'NGN',
        ]);

        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://checkout.paystack.com/test',
                    'access_code' => 'ACCESS123',
                    'reference' => 'TJS_FEE_123',
                ],
            ], 200),
        ]);

        ['call' => $call, 'journal' => $journal] = $this->seedOpenCall();
        $journal->update(['paystack_split_code' => 'SPL_testfee']);
        $user = User::factory()->create(['email_verified_at' => now()]);

        $fee = \App\Models\JournalFee::query()->create([
            'journal_id' => $journal->id,
            'purpose' => \App\Support\JournalFeePurpose::SUBMISSION,
            'name' => 'Submission fee',
            'amount' => 5000,
            'currency' => 'NGN',
            'is_active' => true,
        ]);

        $submission = Submission::query()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'journal_id' => $journal->id,
            'issue_id' => $call->issue_id,
            'announcement_id' => $call->id,
            'author_id' => $user->id,
            'journal_fee_id' => $fee->id,
            'title' => 'Fee pending submission',
            'status' => 'fee_pending',
            'review_type' => 'closed',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/me/submissions/'.$submission->id.'/pay-submission-fee')
            ->assertOk()
            ->assertJsonPath('data.authorization_url', 'https://checkout.paystack.com/test')
            ->assertJsonPath('data.access_code', 'ACCESS123');

        $this->assertStringStartsWith('TJS_', $response->json('data.reference'));
    }

    /**
     * @return array{journal: Journal, issue: Issue, call: JournalAnnouncement}
     */
    private function seedOpenCall(): array
    {
        $journal = Journal::query()->create([
            'slug' => 'demo-journal',
            'title' => 'Demo Journal',
            'is_active' => true,
            'activation_status' => JournalActivation::STATUS_ACTIVE,
        ]);
        app(CategoryService::class)->seedDefaults($journal);

        $volume = Volume::query()->create([
            'journal_id' => $journal->id,
            'volume_number' => 1,
            'year' => 2026,
            'status' => 'published',
        ]);
        $issue = Issue::query()->create([
            'volume_id' => $volume->id,
            'issue_number' => 1,
            'status' => 'published',
        ]);
        $call = JournalAnnouncement::query()->create([
            'journal_id' => $journal->id,
            'issue_id' => $issue->id,
            'type' => AnnouncementType::CALL_FOR_SUBMISSIONS,
            'title' => 'Special issue call',
            'is_published' => true,
            'opens_at' => now()->subDay(),
            'closes_at' => now()->addWeek(),
        ]);

        return compact('journal', 'issue', 'call');
    }
}

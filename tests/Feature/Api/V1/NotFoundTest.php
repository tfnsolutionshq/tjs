<?php

namespace Tests\Feature\Api\V1;

use App\Models\Journal;
use App\Models\User;
use App\Support\JournalActivation;
use App\Support\JournalTeamRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotFoundTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_journal_returns_json_404_without_accept_header(): void
    {
        $response = $this->call(
            'GET',
            '/api/v1/journals/nonexistent-slug',
            [],
            [],
            [],
            ['HTTP_ACCEPT' => '*/*']
        );

        $response
            ->assertNotFound()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonPath('message', 'Journal not found.');
    }

    public function test_unknown_api_route_returns_json_404_without_accept_header(): void
    {
        $response = $this->call(
            'GET',
            '/api/v1/does-not-exist',
            [],
            [],
            [],
            ['HTTP_ACCEPT' => '*/*']
        );

        $response
            ->assertNotFound()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonPath('message', 'The requested endpoint was not found.');
    }

    public function test_missing_journal_slug_in_manage_route_returns_json_404(): void
    {
        $editor = User::factory()->create(['email_verified_at' => now()]);
        Sanctum::actingAs($editor);

        $response = $this->call(
            'GET',
            '/api/v1/me/journals/unizik/manage/dashboard',
            [],
            [],
            [],
            ['HTTP_ACCEPT' => '*/*']
        );

        $response
            ->assertNotFound()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonPath('message', 'Journal not found.');
    }

    public function test_abort_404_message_is_returned_as_json(): void
    {
        $journal = Journal::query()->create([
            'slug' => 'demo-journal',
            'title' => 'Demo Journal',
            'is_active' => true,
            'activation_status' => JournalActivation::STATUS_ACTIVE,
        ]);
        $editor = User::factory()->create(['email_verified_at' => now()]);
        $journal->assignTeamMember($editor, JournalTeamRoles::EDITOR);
        Sanctum::actingAs($editor);

        $response = $this->call(
            'GET',
            '/api/v1/me/journals/'.$journal->slug.'/manage/submissions/00000000-0000-0000-0000-000000000000',
            [],
            [],
            [],
            ['HTTP_ACCEPT' => '*/*']
        );

        $response
            ->assertNotFound()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonPath('message', 'Submission not found.');
    }
}

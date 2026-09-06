<?php

namespace Tests\Feature\Member;

use App\Models\Journal;
use App\Models\User;
use App\Support\JournalTeamRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberCreateJournalTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_member_can_view_create_journal_form(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('member.journals.create'))
            ->assertOk()
            ->assertSee('Create a journal', false)
            ->assertSee(route('member.journals.store'), false);
    }

    public function test_verified_member_can_create_journal_and_becomes_manager(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('member.journals.store'), [
            'title' => 'Member Open Research',
            'slug' => 'member-open-research',
            'review_type' => 'closed',
            'subtitle' => 'Member-founded journal',
            'description' => 'A journal created from the member dashboard.',
            'language' => 'en',
        ]);

        $journal = Journal::query()->where('slug', 'member-open-research')->first();
        $this->assertNotNull($journal);
        $this->assertSame('Member Open Research', $journal->title);
        $this->assertTrue($journal->is_active);

        $this->assertTrue($user->fresh()->canManageJournal($journal));
        $this->assertSame(
            JournalTeamRoles::ADMIN,
            $user->fresh()->journalTeamRole($journal)
        );

        $response->assertRedirect(route('journal.manage.activation.show', $journal));
        $this->assertSame(\App\Support\JournalActivation::STATUS_UNPAID, $journal->activation_status);
        $this->followRedirects($response)->assertOk();
    }

    public function test_when_activation_fees_disabled_new_journal_is_waived(): void
    {
        config(['tjs.journal_activation.enabled' => false]);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('member.journals.store'), [
            'title' => 'Waived Journal',
            'slug' => 'waived-journal',
            'review_type' => 'closed',
        ]);

        $journal = Journal::query()->where('slug', 'waived-journal')->firstOrFail();
        $this->assertSame(\App\Support\JournalActivation::STATUS_ACTIVE, $journal->activation_status);
        $this->assertTrue($journal->isListed());
        $response->assertRedirect(route('journal.manage.settings.edit', $journal));
    }

    public function test_member_with_managed_journal_still_sees_dashboard(): void
    {
        $user = User::factory()->create();
        $journal = Journal::query()->create([
            'slug' => 'owned-journal',
            'title' => 'Owned Journal',
            'is_active' => true,
        ]);
        $journal->assignTeamMember($user, JournalTeamRoles::ADMIN);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Owned Journal', false)
            ->assertSee(route('member.journals.create'), false);
    }

    public function test_duplicate_slug_is_rejected(): void
    {
        Journal::query()->create([
            'slug' => 'taken-slug',
            'title' => 'Existing',
            'is_active' => true,
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('member.journals.create'))
            ->post(route('member.journals.store'), [
                'title' => 'Another Journal',
                'slug' => 'taken-slug',
                'review_type' => 'open',
            ])
            ->assertRedirect(route('member.journals.create'))
            ->assertSessionHasErrors('slug');
    }

    public function test_guest_cannot_create_journal(): void
    {
        $this->get(route('member.journals.create'))
            ->assertRedirect(route('login'));
    }
}

<?php

namespace Tests\Feature;

use App\Models\Journal;
use App\Models\User;
use App\Notifications\JournalActivationReminderNotification;
use App\Support\JournalActivation;
use App\Support\JournalTeamRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class JournalActivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_created_journal_is_unlisted_until_paid(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('member.journals.store'), [
            'title' => 'Pending Fee Journal',
            'slug' => 'pending-fee-journal',
            'review_type' => 'closed',
        ])->assertRedirect(route('journal.manage.activation.show', 'pending-fee-journal'));

        $journal = Journal::query()->where('slug', 'pending-fee-journal')->firstOrFail();
        $this->assertSame(JournalActivation::STATUS_UNPAID, $journal->activation_status);
        $this->assertFalse($journal->isListed());
        $this->assertFalse($journal->managementUnlocked());

        $this->get(route('journals.show', $journal))->assertNotFound();
    }

    public function test_unpaid_journal_locks_major_manage_routes(): void
    {
        $user = User::factory()->create();
        $journal = Journal::query()->create([
            'slug' => 'locked-journal',
            'title' => 'Locked Journal',
            'is_active' => true,
            'activation_status' => JournalActivation::STATUS_UNPAID,
        ]);
        $journal->assignTeamMember($user, JournalTeamRoles::ADMIN);

        $this->actingAs($user)
            ->get(route('journal.manage.articles.index', $journal))
            ->assertRedirect(route('journal.manage.activation.show', $journal));

        $this->actingAs($user)
            ->get(route('journal.manage.dashboard', $journal))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('journal.manage.activation.show', $journal))
            ->assertOk()
            ->assertSee('Activation fee required', false);
    }

    public function test_paying_activation_lists_and_unlocks_journal(): void
    {
        $user = User::factory()->create();
        $journal = Journal::query()->create([
            'slug' => 'to-activate',
            'title' => 'To Activate',
            'is_active' => true,
            'activation_status' => JournalActivation::STATUS_UNPAID,
        ]);
        $journal->assignTeamMember($user, JournalTeamRoles::ADMIN);

        $journal->markActivationPaid();

        $this->assertTrue($journal->fresh()->isListed());
        $this->assertTrue($journal->fresh()->managementUnlocked());
        $this->assertSame(JournalActivation::STATUS_ACTIVE, $journal->fresh()->activation_status);
        $this->assertNotNull($journal->fresh()->activation_expires_at);

        $this->get(route('journals.show', $journal))->assertOk();
    }

    public function test_expired_journal_is_unlisted_and_reminder_marks_expired(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $journal = Journal::query()->create([
            'slug' => 'expiring-journal',
            'title' => 'Expiring Journal',
            'is_active' => true,
            'activation_status' => JournalActivation::STATUS_ACTIVE,
            'activation_paid_at' => now()->subYear(),
            'activation_expires_at' => now()->subDay(),
            'activation_reminders_sent' => [],
        ]);
        $journal->assignTeamMember($user, JournalTeamRoles::ADMIN);

        $this->assertFalse($journal->isListed());

        $this->artisan('journals:send-activation-reminders')->assertSuccessful();

        $journal->refresh();
        $this->assertSame(JournalActivation::STATUS_EXPIRED, $journal->activation_status);
        Notification::assertSentTo($user, JournalActivationReminderNotification::class);
    }

    public function test_skip_activation_goes_to_dashboard(): void
    {
        $user = User::factory()->create();
        $journal = Journal::query()->create([
            'slug' => 'skip-pay',
            'title' => 'Skip Pay',
            'is_active' => true,
            'activation_status' => JournalActivation::STATUS_UNPAID,
        ]);
        $journal->assignTeamMember($user, JournalTeamRoles::ADMIN);

        $this->actingAs($user)
            ->post(route('journal.manage.activation.skip', $journal))
            ->assertRedirect(route('journal.manage.dashboard', $journal));
    }

    public function test_disabling_activation_fees_keeps_existing_unpaid_locked(): void
    {
        config(['tjs.journal_activation.enabled' => false]);

        $user = User::factory()->create();
        $journal = Journal::query()->create([
            'slug' => 'still-locked',
            'title' => 'Still Locked',
            'is_active' => true,
            'activation_status' => JournalActivation::STATUS_UNPAID,
        ]);
        $journal->assignTeamMember($user, JournalTeamRoles::ADMIN);

        $this->assertFalse($journal->isListed());
        $this->assertTrue($journal->activationLocked());
        $this->assertFalse($journal->activationNeedsPayment());

        $this->actingAs($user)
            ->get(route('journal.manage.articles.index', $journal))
            ->assertRedirect(route('journal.manage.activation.show', $journal));
    }
}

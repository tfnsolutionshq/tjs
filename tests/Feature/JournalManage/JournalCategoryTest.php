<?php

namespace Tests\Feature\JournalManage;

use App\Models\Category;
use App\Models\Journal;
use App\Models\User;
use App\Services\Journal\CategoryService;
use App\Support\JournalTeamRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_are_scoped_per_journal(): void
    {
        $journalA = Journal::query()->create(['slug' => 'journal-a', 'title' => 'Journal A', 'is_active' => true]);
        $journalB = Journal::query()->create(['slug' => 'journal-b', 'title' => 'Journal B', 'is_active' => true]);
        app(CategoryService::class)->seedDefaults($journalA);
        app(CategoryService::class)->seedDefaults($journalB);

        $this->assertSame(
            Category::query()->forJournal($journalA)->where('name', 'Research Article')->count(),
            1
        );
        $this->assertSame(
            Category::query()->forJournal($journalB)->where('name', 'Research Article')->count(),
            1
        );
        $this->assertNotEquals(
            Category::query()->forJournal($journalA)->where('name', 'Research Article')->value('id'),
            Category::query()->forJournal($journalB)->where('name', 'Research Article')->value('id')
        );
    }

    public function test_journal_admin_can_create_category(): void
    {
        $journal = Journal::query()->create(['slug' => 'demo-journal', 'title' => 'Demo', 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'member']);
        $journal->assignTeamMember($admin, JournalTeamRoles::ADMIN);

        $this->actingAs($admin)
            ->post(route('journal.manage.settings.categories.store', $journal), [
                'name' => 'Methods Paper',
                'is_active' => '1',
            ])
            ->assertRedirect(route('journal.manage.settings.edit', $journal))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('categories', [
            'journal_id' => $journal->id,
            'name' => 'Methods Paper',
        ]);
    }
}

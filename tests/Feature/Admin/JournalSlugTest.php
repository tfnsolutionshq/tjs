<?php

namespace Tests\Feature\Admin;

use App\Models\Journal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalSlugTest extends TestCase
{
    use RefreshDatabase;

    public function test_check_slug_reports_when_slug_is_taken(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Journal::query()->create([
            'slug' => 'tfn-open-research',
            'title' => 'TFN Open Research Journal',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->getJson(route('journals.check-slug', ['slug' => 'tfn-open-research']))
            ->assertOk()
            ->assertJson([
                'available' => false,
                'slug' => 'tfn-open-research',
            ]);
    }

    public function test_check_slug_reports_when_slug_is_available(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->getJson(route('journals.check-slug', ['slug' => 'ajdi']))
            ->assertOk()
            ->assertJson([
                'available' => true,
                'slug' => 'ajdi',
            ]);
    }

    public function test_check_slug_ignores_current_journal_on_edit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $journal = Journal::query()->create([
            'slug' => 'tfn-open-research',
            'title' => 'TFN Open Research Journal',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->getJson(route('journals.check-slug', [
                'slug' => 'tfn-open-research',
                'except' => $journal->id,
            ]))
            ->assertOk()
            ->assertJson([
                'available' => true,
                'slug' => 'tfn-open-research',
            ]);
    }

    public function test_store_rejects_duplicate_journal_slug(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Journal::query()->create([
            'slug' => 'tfn-open-research',
            'title' => 'TFN Open Research Journal',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.journals.create'))
            ->post(route('admin.journals.store'), [
                'title' => 'Duplicate Slug Journal',
                'slug' => 'tfn-open-research',
                'review_type' => 'closed',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.journals.create'))
            ->assertSessionHasErrors('slug');

        $this->assertDatabaseCount('journals', 1);
    }

    public function test_update_ignores_attempted_slug_change(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $journal = Journal::query()->create([
            'slug' => 'locked-slug',
            'title' => 'Locked Slug Journal',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.journals.update', $journal), [
                'title' => 'Updated Title',
                'slug' => 'new-slug',
                'review_type' => 'closed',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.journals.edit', $journal));

        $journal->refresh();

        $this->assertSame('locked-slug', $journal->slug);
        $this->assertSame('Updated Title', $journal->title);
    }
}

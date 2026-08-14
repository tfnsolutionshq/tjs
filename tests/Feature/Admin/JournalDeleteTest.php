<?php

namespace Tests\Feature\Admin;

use App\Models\Journal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class JournalDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_journal_with_slug_confirmation(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);
        $journal = Journal::query()->create([
            'slug' => 'delete-me',
            'title' => 'Delete Me Journal',
            'is_active' => true,
        ]);

        Storage::disk('public')->put('journals/delete-me/branding/logo.png', 'fake');

        $this->actingAs($admin)
            ->delete(route('admin.journals.destroy', $journal), [
                'confirm' => 'delete-me',
            ])
            ->assertRedirect(route('admin.journals.index'));

        $this->assertDatabaseMissing('journals', ['slug' => 'delete-me']);
        Storage::disk('public')->assertMissing('journals/delete-me/branding/logo.png');
    }

    public function test_journal_delete_requires_matching_slug(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $journal = Journal::query()->create([
            'slug' => 'keep-me',
            'title' => 'Keep Me Journal',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.journals.edit', $journal))
            ->delete(route('admin.journals.destroy', $journal), [
                'confirm' => 'wrong-slug',
            ])
            ->assertRedirect(route('admin.journals.edit', $journal))
            ->assertSessionHasErrors('confirm');

        $this->assertDatabaseHas('journals', ['slug' => 'keep-me']);
    }
}

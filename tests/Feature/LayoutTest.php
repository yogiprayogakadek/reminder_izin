<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_layout_renders_sidebar_toggle(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@siperiz.test')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertSuccessful()
            ->assertSee('data-sidebar-toggle', false)
            ->assertSee('id="app-sidebar"', false)
            ->assertSee('aria-controls="app-sidebar"', false);
    }

    public function test_application_layout_renders_footer_without_greeting(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@siperiz.test')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertSuccessful()
            ->assertSee('Dibuat oleh')
            ->assertSee('Yogi Prayoga')
            ->assertDontSee('Halo,');
    }

    public function test_guest_layout_shows_author_credit(): void
    {
        $this->get(route('login'))
            ->assertSuccessful()
            ->assertSee('Yogi Prayoga');
    }
}

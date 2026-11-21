<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('reports.index'))->assertRedirect(route('login'));
    }

    public function test_inactive_users_are_logged_out(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'is_active' => false]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_viewer_has_read_only_access(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer', 'is_active' => true]);

        $this->actingAs($viewer)->get(route('permits.index'))->assertOk();
        $this->actingAs($viewer)->get(route('reports.index'))->assertOk();
        $this->actingAs($viewer)->get(route('notifications.inbox'))->assertOk();

        $this->actingAs($viewer)->get(route('permits.create'))->assertForbidden();
        $this->actingAs($viewer)->get(route('categories.index'))->assertForbidden();
        $this->actingAs($viewer)->get(route('contacts.index'))->assertForbidden();
        $this->actingAs($viewer)->get(route('notifications.deliveries'))->assertForbidden();
        $this->actingAs($viewer)->get(route('settings.users'))->assertForbidden();
        $this->actingAs($viewer)->get(route('audit.index'))->assertForbidden();
    }

    public function test_secretary_can_manage_permits_and_contacts_but_not_administration(): void
    {
        $secretary = User::factory()->create(['role' => 'secretary', 'is_active' => true]);

        $this->actingAs($secretary)->get(route('permits.create'))->assertOk();
        $this->actingAs($secretary)->get(route('contacts.index'))->assertOk();
        $this->actingAs($secretary)->get(route('notifications.deliveries'))->assertOk();

        $this->actingAs($secretary)->get(route('categories.index'))->assertForbidden();
        $this->actingAs($secretary)->get(route('notifications.rules'))->assertForbidden();
        $this->actingAs($secretary)->get(route('settings.users'))->assertForbidden();
        $this->actingAs($secretary)->get(route('settings.waha'))->assertForbidden();
        $this->actingAs($secretary)->get(route('audit.index'))->assertForbidden();
    }

    public function test_hrd_can_manage_permits_and_contacts_without_system_settings(): void
    {
        $hrd = User::factory()->create(['role' => 'hrd', 'is_active' => true]);

        $this->actingAs($hrd)->get(route('permits.create'))->assertOk();
        $this->actingAs($hrd)->get(route('contacts.index'))->assertOk();
        $this->actingAs($hrd)->get(route('reports.index'))->assertOk();

        $this->actingAs($hrd)->get(route('notifications.deliveries'))->assertForbidden();
        $this->actingAs($hrd)->get(route('notifications.templates'))->assertForbidden();
        $this->actingAs($hrd)->get(route('settings.application'))->assertForbidden();
        $this->actingAs($hrd)->get(route('audit.index'))->assertForbidden();
    }

    public function test_admin_can_open_administration_pages(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        foreach ([
            'categories.index',
            'contacts.index',
            'notifications.rules',
            'notifications.templates',
            'notifications.deliveries',
            'settings.waha',
            'settings.users',
            'settings.application',
            'audit.index',
        ] as $route) {
            $this->actingAs($admin)->get(route($route))->assertOk();
        }
    }
}

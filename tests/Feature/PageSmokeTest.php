<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_every_application_page(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@siperiz.test')->firstOrFail();

        $routes = [
            'dashboard',
            'permits.index',
            'permits.create',
            'categories.index',
            'contacts.index',
            'notifications.rules',
            'notifications.templates',
            'notifications.deliveries',
            'notifications.inbox',
            'reports.index',
            'settings.waha',
            'settings.users',
            'settings.application',
            'audit.index',
            'profile',
        ];

        foreach ($routes as $route) {
            $this->actingAs($admin)
                ->get(route($route))
                ->assertSuccessful();
        }
    }
}

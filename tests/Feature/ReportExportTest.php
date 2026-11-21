<?php

namespace Tests\Feature;

use App\Enums\DeliveryStatus;
use App\Models\Permit;
use App\Models\PermitCategory;
use App\Models\ReminderDelivery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_permits_export_contains_filtered_rows(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $permit = $this->permit();

        $response = $this->actingAs($admin)->get(route('reports.permits.export', [
            'status' => 'all',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Nama Izin', $csv);
        $this->assertStringContainsString($permit->title, $csv);
        $this->assertStringContainsString($permit->category->name, $csv);
    }

    public function test_delivery_history_export_is_limited_to_authorized_roles(): void
    {
        $category = PermitCategory::query()->create([
            'name' => 'Reklame',
            'color' => '#0369A1',
            'is_active' => true,
        ]);

        $permit = Permit::query()->create([
            'category_id' => $category->id,
            'title' => 'Izin Reklame Toko',
            'issuer' => 'Dinas Penanaman Modal',
            'expires_at' => '2027-01-31',
        ]);

        ReminderDelivery::query()->create([
            'permit_id' => $permit->id,
            'cycle_key' => 'cycle:2027-01-31',
            'recipient_phone' => '6281234567890',
            'message_body' => 'Pesan pengingat izin.',
            'scheduled_for' => now(),
            'status' => DeliveryStatus::Sent,
            'idempotency_key' => 'test-delivery-1',
            'is_manual' => true,
        ]);

        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $viewer = User::factory()->create(['role' => 'viewer', 'is_active' => true]);

        $this->actingAs($viewer)
            ->get(route('reports.deliveries.export'))
            ->assertForbidden();

        $response = $this->actingAs($admin)->get(route('reports.deliveries.export'));

        $response->assertOk();

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Nomor WhatsApp', $csv);
        $this->assertStringContainsString('6281234567890', $csv);
        $this->assertStringContainsString('Izin Reklame Toko', $csv);
    }

    protected function permit(): Permit
    {
        $category = PermitCategory::query()->create([
            'name' => 'Reklame',
            'color' => '#0369A1',
            'is_active' => true,
        ]);

        return Permit::query()->create([
            'category_id' => $category->id,
            'title' => 'Izin Reklame Toko',
            'number' => '503/REK/2026',
            'issuer' => 'Dinas Penanaman Modal',
            'issued_at' => '2026-01-01',
            'expires_at' => '2027-01-31',
            'reminder_enabled' => true,
        ]);
    }
}

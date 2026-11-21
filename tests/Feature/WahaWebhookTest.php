<?php

namespace Tests\Feature;

use App\Enums\DeliveryStatus;
use App\Models\Permit;
use App\Models\PermitCategory;
use App\Models\ReminderDelivery;
use App\Models\WahaSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WahaWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_webhook_requires_the_configured_secret(): void
    {
        $this->settings();

        $this->postJson(route('webhooks.waha'), ['event' => 'session.status'])
            ->assertForbidden();

        $this->postJson(route('webhooks.waha'), ['event' => 'session.status'], [
            'X-Webhook-Secret' => 'wrong-secret',
        ])->assertForbidden();
    }

    public function test_message_ack_updates_the_delivery_status(): void
    {
        $settings = $this->settings();

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

        $delivery = ReminderDelivery::query()->create([
            'permit_id' => $permit->id,
            'cycle_key' => 'cycle:2027-01-31',
            'recipient_phone' => '6281234567890',
            'message_body' => 'Pesan pengingat izin.',
            'scheduled_for' => now(),
            'sent_at' => now(),
            'status' => DeliveryStatus::Sent,
            'waha_message_id' => 'wamid.abc123',
            'idempotency_key' => 'test-delivery-1',
        ]);

        $this->postJson(route('webhooks.waha'), [
            'event' => 'message.ack',
            'session' => $settings->session,
            'payload' => [
                'id' => ['_serialized' => 'wamid.abc123'],
                'ack' => 3,
            ],
        ], [
            'X-Webhook-Secret' => 'webhook-test-secret',
        ])->assertOk();

        $this->assertSame(DeliveryStatus::Read, $delivery->refresh()->status);
    }

    public function test_session_status_updates_the_waha_health(): void
    {
        $settings = $this->settings();

        $this->postJson(route('webhooks.waha'), [
            'event' => 'session.status',
            'session' => $settings->session,
            'payload' => ['status' => 'WORKING'],
        ], [
            'X-Webhook-Secret' => 'webhook-test-secret',
        ])->assertOk();

        $this->assertSame('working', $settings->refresh()->last_status);
        $this->assertNull($settings->last_error);
    }

    protected function settings(): WahaSetting
    {
        return WahaSetting::query()->create([
            'base_url' => 'http://127.0.0.1:3000',
            'api_key' => 'test-api-key',
            'session' => 'default',
            'webhook_secret' => 'webhook-test-secret',
            'default_country_code' => '62',
            'daily_limit' => 500,
            'is_enabled' => true,
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Enums\DeliveryStatus;
use App\Jobs\SendReminderJob;
use App\Models\Contact;
use App\Models\Permit;
use App\Models\PermitCategory;
use App\Models\ReminderDelivery;
use App\Models\ReminderRule;
use App\Models\User;
use App\Models\WahaSetting;
use App\Services\Reminders\ReminderDispatcher;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ReminderDispatcherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-11-21 09:00:00'));
        Queue::fake();
    }

    public function test_it_creates_one_delivery_per_cycle_and_is_idempotent(): void
    {
        [$permit, $contact] = $this->scenario();

        $summary = app(ReminderDispatcher::class)->dispatchDue();

        $this->assertSame(1, $summary['created']);
        $this->assertSame(0, $summary['duplicate']);

        $delivery = ReminderDelivery::query()->sole();

        $this->assertSame($permit->id, $delivery->permit_id);
        $this->assertSame($contact->id, $delivery->contact_id);
        $this->assertSame($contact->phone_e164, $delivery->recipient_phone);
        $this->assertSame(DeliveryStatus::Pending, $delivery->status);
        $this->assertFalse($delivery->is_manual);
        $this->assertStringContainsString($permit->title, (string) $delivery->message_body);

        Queue::assertPushed(SendReminderJob::class, 1);

        $second = app(ReminderDispatcher::class)->dispatchDue();

        $this->assertSame(0, $second['created']);
        $this->assertSame(1, $second['duplicate']);
        $this->assertSame(1, ReminderDelivery::query()->count());
    }

    public function test_it_skips_paused_renewing_and_archived_permits(): void
    {
        [$permit] = $this->scenario();

        $permit->forceFill(['reminder_paused_until' => CarbonImmutable::today()])->save();
        $this->assertSame(0, app(ReminderDispatcher::class)->dispatchDue()['created']);

        $permit->forceFill(['reminder_paused_until' => null, 'renewal_started_at' => now()])->save();
        $this->assertSame(0, app(ReminderDispatcher::class)->dispatchDue()['created']);

        $permit->forceFill(['renewal_started_at' => null, 'archived_at' => now()])->save();
        $this->assertSame(0, app(ReminderDispatcher::class)->dispatchDue()['created']);

        $this->assertSame(0, ReminderDelivery::query()->count());
    }

    public function test_it_stops_when_waha_is_disabled(): void
    {
        $this->scenario();

        WahaSetting::query()->update(['is_enabled' => false]);

        $summary = app(ReminderDispatcher::class)->dispatchDue();

        $this->assertSame(0, $summary['created']);
        $this->assertSame('WAHA belum aktif atau belum dikonfigurasi.', $summary['reason']);
    }

    public function test_permit_specific_recipients_override_category_recipients(): void
    {
        [$permit] = $this->scenario();

        $direct = Contact::query()->create([
            'name' => 'PIC Langsung',
            'phone_e164' => '6281299990000',
            'is_active' => true,
        ]);

        $permit->recipients()->attach($direct);

        app(ReminderDispatcher::class)->dispatchDue();

        $this->assertSame([$direct->id], ReminderDelivery::query()->pluck('contact_id')->all());
    }

    public function test_a_linked_contact_user_receives_one_in_app_notification(): void
    {
        [, $contact] = $this->scenario();

        $user = User::factory()->create(['role' => 'viewer', 'is_active' => true]);
        $contact->forceFill(['user_id' => $user->id])->save();

        app(ReminderDispatcher::class)->dispatchDue();

        $this->assertSame(1, $user->notifications()->count());

        app(ReminderDispatcher::class)->dispatchDue();

        $this->assertSame(1, $user->notifications()->count());
    }

    /**
     * @return array{0: Permit, 1: Contact}
     */
    protected function scenario(): array
    {
        $category = PermitCategory::query()->create([
            'name' => 'Reklame',
            'color' => '#0369A1',
            'is_active' => true,
        ]);

        $contact = Contact::query()->create([
            'name' => 'Budi Santoso',
            'phone_e164' => '6281234567890',
            'is_active' => true,
        ]);

        $category->recipients()->attach($contact);

        ReminderRule::query()->create([
            'category_id' => $category->id,
            'offset_days' => 7,
            'send_time' => '00:00:00',
            'is_active' => true,
        ]);

        $permit = Permit::query()->create([
            'category_id' => $category->id,
            'title' => 'Izin Reklame Toko',
            'issuer' => 'Dinas Penanaman Modal',
            'issued_at' => CarbonImmutable::today()->subYear()->toDateString(),
            'expires_at' => CarbonImmutable::today()->addDays(7)->toDateString(),
            'reminder_enabled' => true,
        ]);

        WahaSetting::query()->create([
            'base_url' => 'http://127.0.0.1:3000',
            'api_key' => 'test-api-key',
            'session' => 'default',
            'default_country_code' => '62',
            'daily_limit' => 500,
            'is_enabled' => true,
        ]);

        return [$permit, $contact];
    }
}

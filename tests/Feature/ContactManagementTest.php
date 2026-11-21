<?php

namespace Tests\Feature;

use App\Enums\DeliveryStatus;
use App\Livewire\Contacts\Index;
use App\Models\Contact;
use App\Models\Permit;
use App\Models\PermitCategory;
use App\Models\ReminderDelivery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class ContactManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_secretary_can_deactivate_a_contact_but_cannot_delete_one(): void
    {
        $secretary = User::factory()->create(['role' => 'secretary', 'is_active' => true]);
        $contact = $this->contact();

        Livewire::actingAs($secretary)
            ->test(Index::class)
            ->call('toggleActive', $contact->id);

        $this->assertFalse($contact->refresh()->is_active);
        $this->assertTrue(Gate::forUser($secretary)->allows('manage-contacts'));
        $this->assertFalse(Gate::forUser($secretary)->allows('delete-contacts'));
    }

    public function test_admin_can_delete_an_unused_contact(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $contact = $this->contact();

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('delete', $contact->id);

        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }

    public function test_a_contact_with_delivery_history_is_only_deactivated(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $contact = $this->contact();

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
            'contact_id' => $contact->id,
            'cycle_key' => 'cycle:2027-01-31',
            'recipient_phone' => $contact->phone_e164,
            'message_body' => 'Pesan pengingat izin.',
            'scheduled_for' => now(),
            'status' => DeliveryStatus::Sent,
            'idempotency_key' => 'contact-delete-guard',
        ]);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('delete', $contact->id);

        $this->assertDatabaseHas('contacts', ['id' => $contact->id]);
    }

    protected function contact(): Contact
    {
        return Contact::query()->create([
            'name' => 'Budi Santoso',
            'phone_e164' => '6281234567890',
            'is_active' => true,
        ]);
    }
}

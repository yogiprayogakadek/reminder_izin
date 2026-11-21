<?php

namespace Tests\Feature;

use App\Livewire\Permits\Form;
use App\Livewire\Permits\Renew;
use App\Livewire\Permits\Show;
use App\Models\Permit;
use App\Models\PermitCategory;
use App\Models\PermitFile;
use App\Models\PermitRenewal;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PermitLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected PermitCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-11-21 09:00:00'));

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->category = PermitCategory::query()->create([
            'name' => 'Reklame',
            'color' => '#0369A1',
            'is_active' => true,
        ]);
    }

    public function test_an_authorized_user_can_create_a_permit(): void
    {
        Livewire::actingAs($this->admin)
            ->test(Form::class)
            ->set('categoryId', $this->category->id)
            ->set('title', 'Izin Reklame Toko')
            ->set('number', '503/REK/2026')
            ->set('issuer', 'Dinas Penanaman Modal')
            ->set('issuedAt', '2026-11-21')
            ->set('expiresAt', '2027-11-21')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('permits.show', Permit::query()->sole()));

        $permit = Permit::query()->sole();

        $this->assertSame('Izin Reklame Toko', $permit->title);
        $this->assertSame($this->admin->id, $permit->created_by);
        $this->assertTrue($permit->reminder_enabled);
    }

    public function test_reminders_can_be_paused_and_resumed(): void
    {
        $permit = $this->permit();

        Livewire::actingAs($this->admin)
            ->test(Show::class, ['permit' => $permit])
            ->set('pauseUntil', '2026-12-05')
            ->call('pauseReminders')
            ->assertHasNoErrors();

        $this->assertSame('2026-12-05', $permit->refresh()->reminder_paused_until->toDateString());
        $this->assertTrue($permit->is_paused);

        Livewire::actingAs($this->admin)
            ->test(Show::class, ['permit' => $permit])
            ->call('resumeReminders');

        $this->assertNull($permit->refresh()->reminder_paused_until);
    }

    public function test_archiving_and_restoring_a_permit(): void
    {
        $permit = $this->permit();

        Livewire::actingAs($this->admin)
            ->test(Show::class, ['permit' => $permit])
            ->call('archive');

        $this->assertNotNull($permit->refresh()->archived_at);

        Livewire::actingAs($this->admin)
            ->test(Show::class, ['permit' => $permit])
            ->call('unarchive');

        $this->assertNull($permit->refresh()->archived_at);
    }

    public function test_renewing_a_permit_moves_the_previous_file_to_history(): void
    {
        $permit = $this->permit();

        $file = PermitFile::query()->create([
            'permit_id' => $permit->id,
            'path' => 'permits/'.$permit->id.'/old.pdf',
            'original_name' => 'izin-lama.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'uploaded_by' => $this->admin->id,
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(Renew::class, ['permit' => $permit]);

        $this->assertNotNull($permit->refresh()->renewal_started_at);

        $component
            ->set('newIssuedAt', '2027-01-01')
            ->set('newExpiresAt', '2027-12-31')
            ->set('notes', 'Berkas baru diterima.')
            ->call('save')
            ->assertHasNoErrors();

        $permit->refresh();
        $renewal = PermitRenewal::query()->sole();

        $this->assertSame('2027-12-31', $permit->expires_at->toDateString());
        $this->assertNull($permit->renewal_started_at);
        $this->assertSame('2026-12-31', $renewal->old_expires_at->toDateString());
        $this->assertSame($renewal->id, $file->refresh()->permit_renewal_id);
        $this->assertSame('Berkas baru diterima.', $renewal->notes);
    }

    public function test_renewal_rejects_a_date_that_is_not_after_the_current_expiry(): void
    {
        $permit = $this->permit();

        Livewire::actingAs($this->admin)
            ->test(Renew::class, ['permit' => $permit])
            ->set('newExpiresAt', '2026-12-31')
            ->call('save')
            ->assertHasErrors(['newExpiresAt']);

        $this->assertSame(0, PermitRenewal::query()->count());
    }

    public function test_deleting_a_permit_is_a_soft_delete(): void
    {
        $permit = $this->permit();

        Livewire::actingAs($this->admin)
            ->test(Show::class, ['permit' => $permit])
            ->call('delete')
            ->assertRedirect(route('permits.index'));

        $this->assertSoftDeleted('permits', ['id' => $permit->id]);
    }

    protected function permit(): Permit
    {
        return Permit::query()->create([
            'category_id' => $this->category->id,
            'title' => 'Izin Reklame Toko',
            'number' => '503/REK/2026',
            'issuer' => 'Dinas Penanaman Modal',
            'issued_at' => '2026-01-01',
            'expires_at' => '2026-12-31',
            'created_by' => $this->admin->id,
            'reminder_enabled' => true,
        ]);
    }
}

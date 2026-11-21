<?php

namespace App\Livewire\Permits;

use App\Models\ActivityLog;
use App\Models\Permit;
use App\Models\ReminderDelivery;
use App\Models\ReminderRule;
use App\Models\ReminderTemplate;
use App\Models\WahaSetting;
use App\Services\Reminders\ReminderMessage;
use App\Services\Waha\WahaClient;
use App\Jobs\SendReminderJob;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Detail izin')]
class Show extends Component
{
    public Permit $permit;

    public string $pauseUntil = '';

    public function mount(Permit $permit): void
    {
        $this->permit = $permit;
        $this->pauseUntil = $permit->reminder_paused_until?->toDateString() ?? '';
    }

    public function startRenewal(): void
    {
        Gate::authorize('manage-permits');

        if ($this->permit->archived_at !== null) {
            session()->flash('error', 'Izin yang diarsipkan tidak dapat diperpanjang.');

            return;
        }

        if ($this->permit->renewal_started_at === null) {
            $this->permit->forceFill(['renewal_started_at' => now()])->save();

            ActivityLog::record(
                'permit.renewal_started',
                'Memulai perpanjangan izin '.$this->permit->title,
                $this->permit,
            );
        }

        $this->redirectRoute('permits.renew', $this->permit, navigate: true);
    }

    public function cancelRenewal(): void
    {
        Gate::authorize('manage-permits');

        if ($this->permit->renewal_started_at === null) {
            return;
        }

        $this->permit->forceFill(['renewal_started_at' => null])->save();

        ActivityLog::record(
            'permit.renewal_finished',
            'Membatalkan proses perpanjangan izin '.$this->permit->title,
            $this->permit,
        );

        session()->flash('success', 'Proses perpanjangan dibatalkan. Reminder otomatis aktif kembali.');
    }

    public function pauseReminders(): void
    {
        Gate::authorize('manage-permits');

        $validated = $this->validate([
            'pauseUntil' => ['required', 'date', 'after_or_equal:today'],
        ], [
            'pauseUntil.required' => 'Pilih tanggal akhir penjedaan.',
            'pauseUntil.after_or_equal' => 'Tanggal jeda tidak boleh sebelum hari ini.',
        ]);

        $this->permit->forceFill(['reminder_paused_until' => $validated['pauseUntil']])->save();

        ActivityLog::record(
            'permit.paused',
            'Menjeda reminder izin '.$this->permit->title.' sampai '.$this->permit->reminder_paused_until->translatedFormat('d M Y'),
            $this->permit,
        );

        session()->flash('success', 'Reminder dijeda sampai '.$this->permit->reminder_paused_until->translatedFormat('d M Y').'.');
    }

    public function resumeReminders(): void
    {
        Gate::authorize('manage-permits');

        $this->permit->forceFill(['reminder_paused_until' => null])->save();
        $this->pauseUntil = '';

        ActivityLog::record(
            'permit.resumed',
            'Mengaktifkan kembali reminder izin '.$this->permit->title,
            $this->permit,
        );

        session()->flash('success', 'Reminder izin aktif kembali.');
    }

    public function archive(): void
    {
        Gate::authorize('manage-permits');

        if ($this->permit->archived_at !== null) {
            return;
        }

        $this->permit->forceFill(['archived_at' => now(), 'renewal_started_at' => null])->save();

        ActivityLog::record('permit.archived', 'Mengarsipkan izin '.$this->permit->title, $this->permit);

        session()->flash('success', 'Izin '.$this->permit->title.' dipindahkan ke arsip.');
    }

    public function unarchive(): void
    {
        Gate::authorize('manage-permits');

        $this->permit->forceFill(['archived_at' => null])->save();

        ActivityLog::record('permit.unarchived', 'Mengeluarkan izin '.$this->permit->title.' dari arsip', $this->permit);

        session()->flash('success', 'Izin '.$this->permit->title.' kembali ke daftar aktif.');
    }

    public function delete(): void
    {
        Gate::authorize('delete-permits');

        $title = $this->permit->title;
        $this->permit->delete();

        ActivityLog::record('permit.deleted', 'Menghapus izin '.$title);

        session()->flash('success', 'Izin '.$title.' dihapus.');
        $this->redirectRoute('permits.index', navigate: true);
    }

    public function sendManualReminder(): void
    {
        Gate::authorize('manage-permits');

        if ($this->permit->archived_at !== null) {
            session()->flash('error', 'Izin yang diarsipkan tidak dapat dikirimi reminder.');

            return;
        }

        $waha = WahaSetting::current();

        if (! $waha->isConfigured()) {
            session()->flash('error', 'Konfigurasi WAHA belum lengkap. Hubungi admin sebelum mengirim reminder.');

            return;
        }

        $contacts = $this->permit->resolveContacts();

        if ($contacts->isEmpty()) {
            foreach (ReminderRule::forCategory($this->permit->category_id) as $rule) {
                $contacts = $contacts->merge($rule->recipients->where('is_active', true));
            }

            $contacts = $contacts->unique('id')->values();
        }

        if ($contacts->isEmpty()) {
            session()->flash('error', 'Belum ada kontak aktif untuk izin ini. Pilih penerima pada formulir izin atau kategori.');

            return;
        }

        $template = ReminderTemplate::forCategory($this->permit->category_id);
        $message = app(ReminderMessage::class)->render($this->permit, $template);
        $batch = Str::uuid()->toString();
        $created = 0;

        DB::transaction(function () use ($contacts, $template, $message, $batch, &$created) {
            foreach ($contacts as $contact) {
                $delivery = ReminderDelivery::query()->create([
                    'permit_id' => $this->permit->id,
                    'reminder_template_id' => $template?->id,
                    'contact_id' => $contact->id,
                    'cycle_key' => $this->permit->cycleKey(),
                    'recipient_phone' => $contact->phone_e164,
                    'message_body' => $message,
                    'scheduled_for' => now(),
                    'status' => \App\Enums\DeliveryStatus::Pending,
                    'idempotency_key' => 'manual:'.$this->permit->id.':'.$batch.':'.$contact->id,
                    'is_manual' => true,
                    'created_by' => auth()->id(),
                ]);

                SendReminderJob::dispatch($delivery->id);
                $created++;
            }
        });

        ActivityLog::record(
            'reminder.manual_sent',
            'Mengirim reminder manual izin '.$this->permit->title.' ke '.$created.' kontak',
            $this->permit,
        );

        session()->flash('success', 'Reminder manual diantrekan untuk '.$created.' kontak. Status pengiriman dapat dipantau di riwayat.');
    }

    public function testWaha(): void
    {
        Gate::authorize('manage-app');

        $result = (new WahaClient)->testConnection();

        session()->flash($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function render(): View
    {
        $this->permit->load([
            'category',
            'pic',
            'creator',
            'recipients',
            'currentFiles',
            'renewals.creator',
            'renewals.files',
        ]);

        $cycleKey = $this->permit->cycleKey();

        $ruleStats = ReminderDelivery::query()
            ->where('permit_id', $this->permit->id)
            ->where('cycle_key', $cycleKey)
            ->whereIn('status', ['sent', 'delivered', 'read', 'queued', 'pending'])
            ->selectRaw('reminder_rule_id, COUNT(*) as total')
            ->groupBy('reminder_rule_id')
            ->pluck('total', 'reminder_rule_id');

        $today = CarbonImmutable::today();

        $schedule = ReminderRule::forCategory($this->permit->category_id)
            ->map(fn (ReminderRule $rule) => [
                'rule' => $rule,
                'date' => $rule->thresholdDate($this->permit->expires_at),
                'sent' => (int) ($ruleStats[$rule->id] ?? 0),
                'past' => $rule->thresholdDate($this->permit->expires_at)->lt($today),
            ])
            ->sortBy(fn (array $item) => $item['date'])
            ->values();

        $deliveries = $this->permit->deliveries()
            ->with(['contact', 'rule'])
            ->limit(8)
            ->get();

        $effectiveContacts = $this->permit->recipients->isNotEmpty()
            ? $this->permit->recipients
            : $this->permit->category->recipients()->get();

        $waha = WahaSetting::current();

        return view('livewire.permits.show', [
            'schedule' => $schedule,
            'deliveries' => $deliveries,
            'effectiveContacts' => $effectiveContacts->where('is_active', true)->values(),
            'waha' => $waha,
            'wahaReady' => $waha->isConfigured() && $waha->is_enabled,
            'deliveryTotal' => ReminderDelivery::query()->where('permit_id', $this->permit->id)->count(),
        ]);
    }
}

<?php

namespace App\Livewire\Permits;

use App\Models\ActivityLog;
use App\Models\Permit;
use App\Models\PermitFile;
use App\Models\PermitRenewal;
use App\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Perpanjang izin')]
class Renew extends Component
{
    use WithFileUploads;

    public Permit $permit;

    public string $newIssuedAt = '';

    public string $newExpiresAt = '';

    public string $notes = '';

    public $document = null;

    public function mount(Permit $permit): void
    {
        Gate::authorize('manage-permits');

        $this->permit = $permit;

        if ($permit->archived_at !== null) {
            session()->flash('error', 'Izin yang diarsipkan tidak dapat diperpanjang.');

            $this->redirectRoute('permits.show', $permit, navigate: true);

            return;
        }

        if ($permit->renewal_started_at === null) {
            $permit->forceFill(['renewal_started_at' => now()])->save();

            ActivityLog::record(
                'permit.renewal_started',
                'Memulai perpanjangan izin '.$permit->title,
                $permit,
            );
        }

        $today = CarbonImmutable::today();
        $afterOldExpiry = CarbonImmutable::parse($permit->expires_at)->addDay();
        $base = $today->greaterThan($afterOldExpiry) ? $today : $afterOldExpiry;
        $months = (int) ($permit->category?->default_validity_months ?: 12);

        $this->newIssuedAt = $today->toDateString();
        $this->newExpiresAt = $base->addMonthsNoOverflow(max(1, $months))->toDateString();
    }

    public function cancel(): void
    {
        Gate::authorize('manage-permits');

        $this->permit->forceFill(['renewal_started_at' => null])->save();
        ActivityLog::record('permit.renewal_finished', 'Membatalkan proses perpanjangan izin '.$this->permit->title, $this->permit);

        session()->flash('success', 'Proses perpanjangan dibatalkan.');
        $this->redirectRoute('permits.show', $this->permit, navigate: true);
    }

    public function save(): void
    {
        Gate::authorize('manage-permits');

        $maxKb = max(1, (int) Setting::get('file_max_mb', 10)) * 1024;

        $validated = $this->validate([
            'newIssuedAt' => ['nullable', 'date'],
            'newExpiresAt' => ['required', 'date', 'after:'.$this->permit->expires_at->toDateString()],
            'notes' => ['nullable', 'string', 'max:2000'],
            'document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.$maxKb],
        ], [
            'newExpiresAt.after' => 'Tanggal kedaluwarsa baru harus setelah tanggal kedaluwarsa saat ini ('.$this->permit->expires_at->translatedFormat('d M Y').').',
            'document.mimes' => 'Dokumen harus berformat PDF, JPG, atau PNG.',
            'document.max' => 'Ukuran dokumen maksimal '.(int) Setting::get('file_max_mb', 10).' MB.',
        ]);

        $old = $this->permit->only(['issued_at', 'expires_at']);

        DB::transaction(function () use ($validated, $old) {
            $renewal = PermitRenewal::query()->create([
                'permit_id' => $this->permit->id,
                'old_issued_at' => $this->permit->issued_at,
                'old_expires_at' => $this->permit->expires_at,
                'new_issued_at' => $validated['newIssuedAt'] ?: $this->permit->issued_at,
                'new_expires_at' => $validated['newExpiresAt'],
                'notes' => $validated['notes'] ?: null,
                'created_by' => auth()->id(),
            ]);

            foreach ($this->permit->currentFiles()->get() as $file) {
                $file->forceFill(['permit_renewal_id' => $renewal->id])->save();
            }

            if ($this->document) {
                $path = $this->document->store('permits/'.$this->permit->id, 'local');

                PermitFile::query()->create([
                    'permit_id' => $this->permit->id,
                    'permit_renewal_id' => $renewal->id,
                    'path' => $path,
                    'original_name' => $this->document->getClientOriginalName(),
                    'mime_type' => $this->document->getMimeType() ?: 'application/octet-stream',
                    'size' => $this->document->getSize(),
                    'uploaded_by' => auth()->id(),
                ]);
            }

            $this->permit->forceFill([
                'issued_at' => $validated['newIssuedAt'] ?: $this->permit->issued_at,
                'expires_at' => $validated['newExpiresAt'],
                'renewal_started_at' => null,
            ])->save();

            ActivityLog::record(
                'permit.renewed',
                'Memperpanjang izin '.$this->permit->title.' sampai '.$this->permit->expires_at->translatedFormat('d M Y'),
                $this->permit,
                $old,
                $this->permit->only(['issued_at', 'expires_at']),
            );
        });

        session()->flash('success', 'Izin '.$this->permit->title.' berhasil diperpanjang sampai '.$this->permit->expires_at->translatedFormat('d M Y').'.');
        $this->redirectRoute('permits.show', $this->permit, navigate: true);
    }

    public function render(): View
    {
        $permit = $this->permit->load(['category', 'currentFiles', 'pic']);

        $previewLabel = null;
        $previewTone = 'text-muted';

        if ($this->newExpiresAt !== '' && strtotime($this->newExpiresAt) !== false) {
            $days = (int) CarbonImmutable::today()->startOfDay()->diffInDays(
                CarbonImmutable::parse($this->newExpiresAt)->startOfDay(),
                false,
            );

            $previewLabel = match (true) {
                $days < 0 => 'Tanggal sudah lewat '.abs($days).' hari',
                $days === 0 => 'Kedaluwarsa hari ini',
                default => $days.' hari setelah hari ini',
            };

            $previewTone = $days < 0 ? 'text-danger' : ($days <= 30 ? 'text-warning' : 'text-success');
        }

        return view('livewire.permits.renew', [
            'permit' => $permit,
            'previewLabel' => $previewLabel,
            'previewTone' => $previewTone,
            'fileMaxMb' => (int) Setting::get('file_max_mb', 10),
        ]);
    }
}

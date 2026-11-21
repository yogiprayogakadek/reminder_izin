<?php

namespace App\Livewire\Notifications;

use App\Enums\DeliveryStatus;
use App\Jobs\SendReminderJob;
use App\Models\ActivityLog;
use App\Models\ReminderDelivery;
use App\Models\WahaSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Riwayat pengiriman')]
class Deliveries extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public string $mode = '';

    public string $from = '';

    public string $to = '';

    public ?int $viewingId = null;

    public function mount(): void
    {
        Gate::authorize('view-deliveries');
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'status', 'mode', 'from', 'to'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'status', 'mode', 'from', 'to');
        $this->resetPage();
    }

    public function viewMessage(int $id): void
    {
        $this->viewingId = $id;
    }

    public function closeMessage(): void
    {
        $this->viewingId = null;
    }

    public function retry(int $id): void
    {
        Gate::authorize('manage-reminders');

        $delivery = ReminderDelivery::query()->with('permit')->findOrFail($id);

        if (! in_array($delivery->status, [DeliveryStatus::Failed, DeliveryStatus::Cancelled], true)) {
            session()->flash('error', 'Hanya pengiriman gagal atau dibatalkan yang dapat dikirim ulang.');

            return;
        }

        $waha = WahaSetting::current();

        if (! $waha->isConfigured() || ! $waha->is_enabled) {
            session()->flash('error', 'WAHA belum aktif atau belum dikonfigurasi.');

            return;
        }

        $delivery->forceFill([
            'status' => DeliveryStatus::Pending,
            'attempts' => 0,
            'error_message' => null,
            'sent_at' => null,
        ])->save();

        SendReminderJob::dispatch($delivery->id);

        ActivityLog::record(
            'reminder.retried',
            'Mengirim ulang reminder izin '.($delivery->permit?->title ?? '#'.$delivery->permit_id),
            $delivery->permit,
        );

        session()->flash('success', 'Reminder diantrekan ulang untuk '.($delivery->contact?->name ?? $delivery->recipient_phone).'.');
    }

    public function cancel(int $id): void
    {
        Gate::authorize('manage-reminders');

        $delivery = ReminderDelivery::query()->with('permit')->findOrFail($id);

        if (in_array($delivery->status, [DeliveryStatus::Sent, DeliveryStatus::Delivered, DeliveryStatus::Read], true)) {
            session()->flash('error', 'Pesan yang sudah terkirim tidak dapat dibatalkan.');

            return;
        }

        $delivery->forceFill([
            'status' => DeliveryStatus::Cancelled,
            'error_message' => 'Dibatalkan manual oleh '.auth()->user()->name.'.',
        ])->save();

        ActivityLog::record(
            'reminder.cancelled',
            'Membatalkan pengiriman reminder izin '.($delivery->permit?->title ?? '#'.$delivery->permit_id),
            $delivery->permit,
        );

        session()->flash('success', 'Pengiriman dibatalkan.');
    }

    public function render(): View
    {
        $query = ReminderDelivery::query()
            ->with(['permit', 'contact', 'rule', 'creator'])
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('recipient_phone', 'like', $term)
                        ->orWhereHas('contact', fn ($contact) => $contact->where('name', 'like', $term))
                        ->orWhereHas('permit', fn ($permit) => $permit->where('title', 'like', $term)->orWhere('number', 'like', $term));
                });
            })
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->mode === 'manual', fn ($q) => $q->where('is_manual', true))
            ->when($this->mode === 'auto', fn ($q) => $q->where('is_manual', false))
            ->when($this->from !== '', fn ($q) => $q->whereDate('created_at', '>=', $this->from))
            ->when($this->to !== '', fn ($q) => $q->whereDate('created_at', '<=', $this->to));

        $statusCounts = (clone $query)
            ->reorder()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $total = (int) $statusCounts->sum();
        $success = (int) ($statusCounts[DeliveryStatus::Sent->value] ?? 0)
            + (int) ($statusCounts[DeliveryStatus::Delivered->value] ?? 0)
            + (int) ($statusCounts[DeliveryStatus::Read->value] ?? 0);

        return view('livewire.notifications.deliveries', [
            'deliveries' => $query->latest()->paginate(20)->withQueryString(),
            'viewing' => $this->viewingId ? ReminderDelivery::query()->with(['permit', 'contact', 'rule', 'template'])->find($this->viewingId) : null,
            'statuses' => DeliveryStatus::cases(),
            'summary' => [
                'total' => $total,
                'success' => $success,
                'failed' => (int) ($statusCounts[DeliveryStatus::Failed->value] ?? 0),
                'pending' => (int) ($statusCounts[DeliveryStatus::Pending->value] ?? 0) + (int) ($statusCounts[DeliveryStatus::Queued->value] ?? 0),
                'success_rate' => $total > 0 ? round($success / $total * 100) : null,
            ],
            'wahaReady' => WahaSetting::current()->isConfigured() && WahaSetting::current()->is_enabled,
        ]);
    }
}

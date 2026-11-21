<?php

namespace App\Livewire;

use App\Enums\DeliveryStatus;
use App\Models\ActivityLog;
use App\Models\Contact;
use App\Models\Permit;
use App\Models\PermitCategory;
use App\Models\ReminderDelivery;
use App\Models\ReminderRule;
use App\Models\WahaSetting;
use App\Services\Waha\WahaClient;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Dashboard')]
class Dashboard extends Component
{
    public string $categoryId = '';

    public string $status = '';

    public string $picId = '';

    public ?string $wahaMessage = null;

    public bool $wahaOk = false;

    public function resetFilters(): void
    {
        $this->reset('categoryId', 'status', 'picId');
    }

    public function testWaha(): void
    {
        Gate::authorize('manage-app');

        $result = (new WahaClient)->testConnection();
        $this->wahaOk = $result['ok'];
        $this->wahaMessage = $result['message'];

        ActivityLog::record(
            'waha.tested',
            'Menguji koneksi WAHA: '.$result['message'],
        );
    }

    public function render(): View
    {
        $today = CarbonImmutable::today();

        $stats = [
            'active' => Permit::query()->status('active')->count(),
            'expiring' => Permit::query()->status('expiring')->count(),
            'expired' => Permit::query()->status('expired')->count(),
            'renewing' => Permit::query()->status('renewing')->count(),
            'reminders' => ReminderDelivery::query()
                ->whereIn('status', [DeliveryStatus::Sent->value, DeliveryStatus::Delivered->value, DeliveryStatus::Read->value])
                ->whereBetween('sent_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->count(),
        ];

        $actionQuery = Permit::query()
            ->needsAction()
            ->with(['category', 'pic'])
            ->when($this->categoryId !== '', fn ($q) => $q->where('category_id', $this->categoryId))
            ->when($this->status !== '', fn ($q) => $q->status($this->status))
            ->when($this->picId !== '', fn ($q) => $q->where('pic_user_id', $this->picId))
            ->orderBy('expires_at')
            ->orderBy('title');

        $actionTotal = (clone $actionQuery)->count();
        $actionPermits = $actionQuery->limit(6)->get();

        $categories = PermitCategory::query()
            ->withCount(['permits as permit_count' => fn ($q) => $q->notArchived()])
            ->orderBy('name')
            ->get();

        $maxCategory = max(1, (int) $categories->max('permit_count'));

        $calendarStart = $today->startOfWeek(CarbonImmutable::MONDAY);
        $calendarEnd = $calendarStart->addWeeks(13)->subDay();
        $windowEnd = $today->addDays(90);

        $counts = Permit::query()
            ->whereNull('archived_at')
            ->whereDate('expires_at', '>=', $today->toDateString())
            ->whereDate('expires_at', '<=', $windowEnd->toDateString())
            ->selectRaw('DATE(expires_at) as expiry_day, COUNT(*) as total')
            ->groupBy('expiry_day')
            ->pluck('total', 'expiry_day');

        $calendarRows = [];
        for ($weekday = 0; $weekday < 7; $weekday++) {
            for ($week = 0; $week < 13; $week++) {
                $date = $calendarStart->addDays(($week * 7) + $weekday);
                $calendarRows[$weekday][$week] = [
                    'date' => $date,
                    'count' => (int) ($counts[$date->toDateString()] ?? 0),
                    'inWindow' => $date->betweenIncluded($today, $windowEnd),
                    'isToday' => $date->isSameDay($today),
                ];
            }
        }

        $activities = ActivityLog::query()
            ->with('user')
            ->where('action', 'like', 'permit.%')
            ->latest()
            ->limit(6)
            ->get();

        $waha = WahaSetting::current();
        $wahaState = $this->wahaState($waha);
        $wahaWarning = $waha->isConfigured()
            && $waha->is_enabled
            && in_array($waha->last_status, ['failed', 'stopped', 'scan_qr_code', 'unreachable', null], true);

        return view('livewire.dashboard', [
            'stats' => $stats,
            'actionPermits' => $actionPermits,
            'actionTotal' => $actionTotal,
            'categories' => $categories,
            'maxCategory' => $maxCategory,
            'calendarStart' => $calendarStart,
            'windowEnd' => $windowEnd,
            'calendarRows' => $calendarRows,
            'activities' => $activities,
            'waha' => $waha,
            'wahaState' => $wahaState,
            'wahaWarning' => $wahaWarning,
            'categoryOptions' => PermitCategory::query()->active()->orderBy('name')->get(['id', 'name']),
            'picOptions' => \App\Models\User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'activeRules' => ReminderRule::query()->where('is_active', true)->count(),
            'activeContacts' => Contact::query()->where('is_active', true)->count(),
            'recentFailures' => ReminderDelivery::query()->where('status', DeliveryStatus::Failed->value)->where('created_at', '>=', now()->subDays(30))->count(),
            'hasCategories' => PermitCategory::query()->where('is_active', true)->exists(),
            'totalPermits' => Permit::query()->count(),
        ]);
    }

    /**
     * @return array{tone: string, label: string, description: string}
     */
    protected function wahaState(WahaSetting $waha): array
    {
        if (! $waha->isConfigured()) {
            return [
                'tone' => 'neutral',
                'label' => 'Belum dikonfigurasi',
                'description' => 'Admin perlu mengisi base URL dan API key WAHA sebelum reminder dapat dikirim.',
            ];
        }

        if (! $waha->is_enabled) {
            return [
                'tone' => 'neutral',
                'label' => 'Nonaktif',
                'description' => 'Koneksi WAHA sudah diisi, tetapi pengiriman otomatis sedang dimatikan.',
            ];
        }

        return match ($waha->last_status) {
            'working', 'connected' => [
                'tone' => 'success',
                'label' => 'Terhubung',
                'description' => 'Sesi WhatsApp aktif dan siap mengirim reminder.',
            ],
            'scan_qr_code' => [
                'tone' => 'warning',
                'label' => 'Perlu pindai QR',
                'description' => 'Sesi belum terhubung. Pindai QR melalui dashboard WAHA.',
            ],
            'starting' => [
                'tone' => 'warning',
                'label' => 'Sedang dimulai',
                'description' => 'Sesi sedang dinyalakan. Periksa kembali beberapa saat lagi.',
            ],
            'failed', 'stopped', 'unreachable' => [
                'tone' => 'danger',
                'label' => 'Tidak terhubung',
                'description' => 'Reminder tidak dapat dikirim. Periksa konfigurasi dan server WAHA.',
            ],
            default => [
                'tone' => 'warning',
                'label' => 'Belum diperiksa',
                'description' => 'Status sesi belum diketahui. Jalankan uji koneksi untuk memastikan.',
            ],
        };
    }
}

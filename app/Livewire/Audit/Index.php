<?php

namespace App\Livewire\Audit;

use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Audit log')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $action = '';

    public string $userId = '';

    public string $from = '';

    public string $to = '';

    public ?int $expandedId = null;

    protected array $labels = [
        'name' => 'Nama',
        'title' => 'Nama izin',
        'number' => 'Nomor',
        'issuer' => 'Penerbit',
        'issued_at' => 'Tanggal terbit',
        'expires_at' => 'Kedaluwarsa',
        'category_id' => 'Kategori',
        'pic_user_id' => 'PIC',
        'location' => 'Lokasi',
        'notes' => 'Catatan',
        'email' => 'Email',
        'phone' => 'Telepon',
        'phone_e164' => 'Nomor WhatsApp',
        'label' => 'Label',
        'role' => 'Peran',
        'is_active' => 'Status aktif',
        'reminder_enabled' => 'Reminder aktif',
        'reminder_paused_until' => 'Jeda sampai',
        'default_validity_months' => 'Masa berlaku',
        'default_pic_user_id' => 'PIC default',
        'color' => 'Warna',
        'description' => 'Deskripsi',
        'offset_days' => 'Jarak hari',
        'send_time' => 'Jam kirim',
        'repeat_overdue_days' => 'Interval pengulangan',
        'max_repeats' => 'Batas pengulangan',
        'body' => 'Isi pesan',
        'base_url' => 'Base URL',
        'session' => 'Sesi',
        'daily_limit' => 'Batas harian',
        'is_enabled' => 'Pengiriman aktif',
        'app_name' => 'Nama aplikasi',
        'timezone' => 'Zona waktu',
        'locale' => 'Bahasa',
        'reminder_time' => 'Jam reminder',
        'file_max_mb' => 'Batas file',
        'log_retention_days' => 'Retensi log',
        'workdays_only' => 'Hari kerja saja',
        'skip_holidays' => 'Lewati hari libur',
    ];

    public function mount(): void
    {
        Gate::authorize('view-audit');
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'action', 'userId', 'from', 'to'], true)) {
            $this->expandedId = null;
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'action', 'userId', 'from', 'to');
        $this->expandedId = null;
        $this->resetPage();
    }

    public function toggleDetails(int $id): void
    {
        $this->expandedId = $this->expandedId === $id ? null : $id;
    }

    protected function formatValues(?array $values): array
    {
        if (! $values) {
            return [];
        }

        $formatted = [];

        foreach ($values as $key => $value) {
            $formatted[] = [
                'label' => $this->labels[$key] ?? str($key)->replace('_', ' ')->title()->toString(),
                'value' => match (true) {
                    is_bool($value) => $value ? 'Ya' : 'Tidak',
                    $value === null => '-',
                    is_array($value) => json_encode($value, JSON_UNESCAPED_UNICODE),
                    default => (string) $value,
                },
            ];
        }

        return $formatted;
    }

    public function render(): View
    {
        $query = ActivityLog::query()
            ->with('user')
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('description', 'like', $term)
                        ->orWhere('action', 'like', $term)
                        ->orWhere('ip_address', 'like', $term)
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', $term)->orWhere('email', 'like', $term));
                });
            })
            ->when($this->action !== '', fn ($q) => $q->where('action', $this->action))
            ->when($this->userId !== '', fn ($q) => $q->where('user_id', $this->userId))
            ->when($this->from !== '', fn ($q) => $q->whereDate('created_at', '>=', $this->from))
            ->when($this->to !== '', fn ($q) => $q->whereDate('created_at', '<=', $this->to));

        $expanded = $this->expandedId
            ? ActivityLog::query()->with('user')->find($this->expandedId)
            : null;

        return view('livewire.audit.index', [
            'logs' => $query->latest()->paginate(25)->withQueryString(),
            'actions' => ActivityLog::query()
                ->select('action')
                ->distinct()
                ->orderBy('action')
                ->pluck('action')
                ->mapWithKeys(fn ($action) => [$action => ActivityLog::labelFor($action)]),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'expanded' => $expanded,
            'oldValues' => $expanded ? $this->formatValues($expanded->old_values) : [],
            'newValues' => $expanded ? $this->formatValues($expanded->new_values) : [],
            'summary' => [
                'total' => ActivityLog::query()->count(),
                'today' => ActivityLog::query()->whereDate('created_at', now()->toDateString())->count(),
                'actors' => ActivityLog::query()->whereNotNull('user_id')->distinct('user_id')->count('user_id'),
                'retention' => (int) Setting::get('log_retention_days', 90),
            ],
        ]);
    }
}

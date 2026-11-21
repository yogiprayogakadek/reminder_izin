<?php

namespace App\Livewire\Settings;

use App\Models\ActivityLog;
use App\Models\Holiday;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Pengaturan aplikasi')]
class Application extends Component
{
    public string $appName = '';

    public string $appUrl = '';

    public string $timezone = 'Asia/Makassar';

    public string $locale = 'id';

    public string $reminderTime = '08:00';

    public int $fileMaxMb = 10;

    public int $logRetentionDays = 90;

    public bool $workdaysOnly = false;

    public bool $skipHolidays = false;

    public string $holidayDate = '';

    public string $holidayName = '';

    public function mount(): void
    {
        Gate::authorize('manage-app');

        $this->appName = (string) Setting::get('app_name', 'SIPERIZ');
        $this->appUrl = (string) Setting::get('app_url', '');
        $this->timezone = (string) Setting::get('timezone', config('app.timezone'));
        $this->locale = (string) Setting::get('locale', 'id');
        $this->reminderTime = substr((string) Setting::get('reminder_time', '08:00'), 0, 5);
        $this->fileMaxMb = (int) Setting::get('file_max_mb', 10);
        $this->logRetentionDays = (int) Setting::get('log_retention_days', 90);
        $this->workdaysOnly = Setting::get('workdays_only') === '1';
        $this->skipHolidays = Setting::get('skip_holidays') === '1';
    }

    public function save(): void
    {
        Gate::authorize('manage-app');

        $validated = $this->validate([
            'appName' => ['required', 'string', 'max:60'],
            'appUrl' => ['nullable', 'string', 'max:255', 'url:http,https'],
            'timezone' => ['required', 'string', Rule::in(['Asia/Makassar', 'Asia/Jakarta', 'Asia/Jayapura', 'Asia/Pontianak'])],
            'locale' => ['required', 'string', Rule::in(['id', 'en'])],
            'reminderTime' => ['required', 'date_format:H:i'],
            'fileMaxMb' => ['required', 'integer', 'min:1', 'max:50'],
            'logRetentionDays' => ['required', 'integer', 'min:7', 'max:3650'],
            'workdaysOnly' => ['boolean'],
            'skipHolidays' => ['boolean'],
        ], [
            'appUrl.url' => 'URL aplikasi harus diawali http:// atau https://.',
        ]);

        $old = [
            'app_name' => Setting::get('app_name'),
            'timezone' => Setting::get('timezone'),
            'locale' => Setting::get('locale'),
            'reminder_time' => Setting::get('reminder_time'),
            'file_max_mb' => Setting::get('file_max_mb'),
            'log_retention_days' => Setting::get('log_retention_days'),
            'workdays_only' => Setting::get('workdays_only'),
            'skip_holidays' => Setting::get('skip_holidays'),
        ];

        Setting::many([
            'app_name' => $validated['appName'],
            'app_url' => $validated['appUrl'] ?: '',
            'timezone' => $validated['timezone'],
            'locale' => $validated['locale'],
            'reminder_time' => $validated['reminderTime'],
            'file_max_mb' => (string) $validated['fileMaxMb'],
            'log_retention_days' => (string) $validated['logRetentionDays'],
            'workdays_only' => $validated['workdaysOnly'] ? '1' : '0',
            'skip_holidays' => $validated['skipHolidays'] ? '1' : '0',
        ]);

        Setting::applyRuntime();

        ActivityLog::record(
            'settings.updated',
            'Memperbarui pengaturan aplikasi',
            null,
            $old,
            [
                'app_name' => $validated['appName'],
                'timezone' => $validated['timezone'],
                'locale' => $validated['locale'],
                'reminder_time' => $validated['reminderTime'],
                'file_max_mb' => (string) $validated['fileMaxMb'],
                'log_retention_days' => (string) $validated['logRetentionDays'],
                'workdays_only' => $validated['workdaysOnly'] ? '1' : '0',
                'skip_holidays' => $validated['skipHolidays'] ? '1' : '0',
            ],
        );

        session()->flash('success', 'Pengaturan aplikasi disimpan.');
    }

    public function addHoliday(): void
    {
        Gate::authorize('manage-app');

        $this->holidayDate = trim($this->holidayDate);
        $this->holidayName = trim($this->holidayName);

        $validated = $this->validate([
            'holidayDate' => ['required', 'date', 'after_or_equal:today', Rule::unique('holidays', 'date')],
            'holidayName' => ['required', 'string', 'max:100'],
        ], [
            'holidayDate.unique' => 'Tanggal ini sudah tercatat sebagai hari libur.',
            'holidayDate.after_or_equal' => 'Tambahkan hari libur hari ini atau tanggal mendatang.',
        ]);

        $holiday = Holiday::query()->create($validated);

        ActivityLog::record('holiday.created', 'Menambah hari libur '.$holiday->name.' pada '.$holiday->date->translatedFormat('d M Y'), $holiday);
        session()->flash('success', 'Hari libur '.$holiday->name.' ditambahkan.');

        $this->reset('holidayDate', 'holidayName');
        $this->resetErrorBag();
    }

    public function deleteHoliday(int $id): void
    {
        Gate::authorize('manage-app');

        $holiday = Holiday::query()->findOrFail($id);
        $label = $holiday->name.' ('.$holiday->date->translatedFormat('d M Y').')';
        $holiday->delete();

        ActivityLog::record('holiday.deleted', 'Menghapus hari libur '.$label);
        session()->flash('success', 'Hari libur '.$label.' dihapus.');
    }

    public function render(): View
    {
        return view('livewire.settings.application', [
            'holidays' => Holiday::query()
                ->whereDate('date', '>=', now()->subDays(1)->toDateString())
                ->orderBy('date')
                ->limit(25)
                ->get(),
            'timezones' => [
                'Asia/Makassar' => 'WITA, Asia/Makassar (UTC+8)',
                'Asia/Jakarta' => 'WIB, Asia/Jakarta (UTC+7)',
                'Asia/Pontianak' => 'WIB, Asia/Pontianak (UTC+7)',
                'Asia/Jayapura' => 'WIT, Asia/Jayapura (UTC+9)',
            ],
            'queueDriver' => config('queue.default'),
        ]);
    }
}

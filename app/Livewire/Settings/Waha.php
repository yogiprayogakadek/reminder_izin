<?php

namespace App\Livewire\Settings;

use App\Models\ActivityLog;
use App\Models\WahaSetting;
use App\Services\Waha\WahaClient;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Koneksi WAHA')]
class Waha extends Component
{
    public WahaSetting $settings;

    public string $baseUrl = '';

    public string $apiKey = '';

    public string $session = 'default';

    public string $webhookSecret = '';

    public string $defaultCountryCode = '62';

    public int $dailyLimit = 500;

    public bool $isEnabled = false;

    public bool $clearApiKey = false;

    public bool $clearWebhookSecret = false;

    public ?array $testResult = null;

    public function mount(): void
    {
        Gate::authorize('manage-app');

        $this->settings = WahaSetting::current();

        $this->baseUrl = (string) $this->settings->base_url;
        $this->session = $this->settings->resolvedSession();
        $this->defaultCountryCode = (string) ($this->settings->default_country_code ?: '62');
        $this->dailyLimit = (int) ($this->settings->daily_limit ?: 500);
        $this->isEnabled = (bool) $this->settings->is_enabled;
    }

    public function save(): void
    {
        Gate::authorize('manage-app');

        if (! $this->persist()) {
            return;
        }

        session()->flash('success', 'Konfigurasi WAHA disimpan.');
    }

    public function testConnection(): void
    {
        Gate::authorize('manage-app');

        if (! $this->persist()) {
            return;
        }

        $result = (new WahaClient($this->settings))->testConnection();
        $this->settings->refresh();
        $this->testResult = $result;

        ActivityLog::record('waha.tested', 'Menguji koneksi WAHA: '.$result['message']);

        session()->flash($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function disable(): void
    {
        Gate::authorize('manage-app');

        $this->settings->forceFill(['is_enabled' => false])->save();
        $this->isEnabled = false;

        ActivityLog::record('waha.updated', 'Menonaktifkan pengiriman WhatsApp');

        session()->flash('success', 'Pengiriman WhatsApp dinonaktifkan. Reminder baru tidak akan dibuat.');
    }

    protected function persist(): bool
    {
        $validated = $this->validate([
            'baseUrl' => ['nullable', 'string', 'max:255', 'url:http,https'],
            'apiKey' => ['nullable', 'string', 'max:500'],
            'session' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9._-]+$/'],
            'webhookSecret' => ['nullable', 'string', 'max:500'],
            'defaultCountryCode' => ['required', 'string', 'max:5', 'regex:/^[0-9]+$/'],
            'dailyLimit' => ['required', 'integer', 'min:1', 'max:10000'],
            'isEnabled' => ['boolean'],
            'clearApiKey' => ['boolean'],
            'clearWebhookSecret' => ['boolean'],
        ], [
            'baseUrl.url' => 'Base URL harus diawali http:// atau https://.',
            'session.regex' => 'Nama sesi hanya boleh berisi huruf, angka, titik, garis bawah, atau tanda hubung.',
        ]);

        $attributes = [
            'base_url' => $validated['baseUrl'] ? rtrim($validated['baseUrl'], '/') : null,
            'session' => $validated['session'],
            'default_country_code' => $validated['defaultCountryCode'],
            'daily_limit' => (int) $validated['dailyLimit'],
            'is_enabled' => $validated['isEnabled'],
        ];

        if ($this->clearApiKey) {
            $attributes['api_key'] = null;
        } elseif (filled($this->apiKey)) {
            $attributes['api_key'] = $this->apiKey;
        }

        if ($this->clearWebhookSecret) {
            $attributes['webhook_secret'] = null;
        } elseif (filled($this->webhookSecret)) {
            $attributes['webhook_secret'] = $this->webhookSecret;
        }

        $this->settings->fill($attributes)->save();

        if ($this->settings->is_enabled && ! $this->settings->isConfigured()) {
            $this->settings->forceFill(['is_enabled' => false])->save();
            $this->isEnabled = false;
            $this->addError('isEnabled', 'Lengkapi base URL dan API key sebelum mengaktifkan pengiriman.');

            return false;
        }

        $this->apiKey = '';
        $this->webhookSecret = '';
        $this->clearApiKey = false;
        $this->clearWebhookSecret = false;

        ActivityLog::record(
            'waha.updated',
            'Memperbarui konfigurasi WAHA'.($this->settings->is_enabled ? ' dan mengaktifkan pengiriman' : ''),
            $this->settings,
            [],
            [
                'base_url' => $this->settings->base_url,
                'session' => $this->settings->session,
                'is_enabled' => $this->settings->is_enabled,
                'daily_limit' => $this->settings->daily_limit,
            ],
        );

        return true;
    }

    public function render(): View
    {
        return view('livewire.settings.waha', [
            'webhookUrl' => route('webhooks.waha'),
            'statusLabel' => $this->statusLabel($this->settings),
            'statusTone' => $this->statusTone($this->settings),
        ]);
    }

    protected function statusLabel(WahaSetting $settings): string
    {
        if (! $settings->isConfigured()) {
            return 'Belum dikonfigurasi';
        }

        return match ($settings->last_status) {
            'working', 'connected' => 'Terhubung',
            'scan_qr_code' => 'Perlu pindai QR',
            'starting' => 'Sedang dimulai',
            'failed' => 'Gagal',
            'stopped' => 'Berhenti',
            'unreachable' => 'Tidak dapat dijangkau',
            default => 'Belum diuji',
        };
    }

    protected function statusTone(WahaSetting $settings): string
    {
        if (! $settings->isConfigured()) {
            return 'neutral';
        }

        return match ($settings->last_status) {
            'working', 'connected' => 'success',
            'scan_qr_code', 'starting' => 'warning',
            'failed', 'stopped', 'unreachable' => 'danger',
            default => 'neutral',
        };
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WahaSetting extends Model
{
    protected $fillable = [
        'base_url',
        'api_key',
        'session',
        'webhook_secret',
        'default_country_code',
        'daily_limit',
        'is_enabled',
        'sender_number',
        'last_status',
        'last_error',
        'last_tested_at',
    ];

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'webhook_secret' => 'encrypted',
            'is_enabled' => 'boolean',
            'daily_limit' => 'integer',
            'last_tested_at' => 'datetime',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrNew([], [
            'session' => config('services.waha.session', 'default'),
            'default_country_code' => '62',
            'daily_limit' => 500,
            'is_enabled' => false,
        ]);
    }

    public function isConfigured(): bool
    {
        return filled($this->resolvedBaseUrl()) && filled($this->resolvedApiKey());
    }

    public function maskedApiKey(): string
    {
        $key = $this->resolvedApiKey();

        if (blank($key)) {
            return 'Belum diatur';
        }

        return (filled($this->api_key) ? '' : 'Env: ').'••••••'.substr($key, -4);
    }

    public function maskedWebhookSecret(): string
    {
        $secret = $this->resolvedWebhookSecret();

        if (blank($secret)) {
            return 'Belum diatur';
        }

        return (filled($this->webhook_secret) ? '' : 'Env: ').'••••••'.substr($secret, -4);
    }

    public function resolvedBaseUrl(): string
    {
        return rtrim($this->base_url ?: (string) config('services.waha.base_url'), '/');
    }

    public function resolvedApiKey(): string
    {
        return (string) ($this->api_key ?: config('services.waha.api_key'));
    }

    public function resolvedSession(): string
    {
        return $this->session ?: (string) config('services.waha.session', 'default');
    }

    public function resolvedWebhookSecret(): string
    {
        return (string) ($this->webhook_secret ?: config('services.waha.webhook_secret'));
    }
}

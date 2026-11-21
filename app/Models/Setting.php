<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $table = 'app_settings';

    protected $fillable = ['key', 'value'];

    public const DEFAULTS = [
        'app_name' => 'SIPERIZ',
        'timezone' => 'Asia/Makassar',
        'locale' => 'id',
        'date_format' => 'd M Y',
        'country_code' => '62',
        'file_max_mb' => '10',
        'reminder_time' => '08:00',
        'workdays_only' => '0',
        'skip_holidays' => '0',
        'log_retention_days' => '90',
        'app_url' => '',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $values = Cache::rememberForever('app_settings', fn () => static::query()->pluck('value', 'key')->all());

        return $values[$key] ?? $default ?? self::DEFAULTS[$key] ?? null;
    }

    public static function put(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value === null ? null : (string) $value]);
        Cache::forget('app_settings');
    }

    public static function many(array $values): void
    {
        foreach ($values as $key => $value) {
            static::updateOrCreate(['key' => $key], ['value' => $value === null ? null : (string) $value]);
        }

        Cache::forget('app_settings');
    }

    public static function appUrl(): string
    {
        return rtrim((string) (static::get('app_url') ?: config('app.url')), '/');
    }

    public static function applyRuntime(): void
    {
        try {
            $timezone = (string) static::get('timezone', config('app.timezone'));

            if ($timezone !== '' && in_array($timezone, timezone_identifiers_list(), true)) {
                config(['app.timezone' => $timezone]);
                date_default_timezone_set($timezone);
            }

            $locale = (string) static::get('locale', config('app.locale'));

            if ($locale !== '') {
                app()->setLocale($locale);
            }
        } catch (\Throwable) {
            // Database may not be available during installation or migrations.
        }
    }
}

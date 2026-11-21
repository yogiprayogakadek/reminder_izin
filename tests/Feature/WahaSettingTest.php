<?php

namespace Tests\Feature;

use App\Models\WahaSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WahaSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_environment_credentials_count_as_configured(): void
    {
        config([
            'services.waha.base_url' => 'http://waha.local:3000',
            'services.waha.api_key' => 'env-api-key-1234',
            'services.waha.webhook_secret' => 'env-secret-5678',
        ]);

        $settings = WahaSetting::current();

        $this->assertTrue($settings->isConfigured());
        $this->assertSame('http://waha.local:3000', $settings->resolvedBaseUrl());
        $this->assertSame('Env: ••••••1234', $settings->maskedApiKey());
        $this->assertSame('Env: ••••••5678', $settings->maskedWebhookSecret());
    }

    public function test_missing_credentials_are_not_configured(): void
    {
        config([
            'services.waha.base_url' => '',
            'services.waha.api_key' => '',
        ]);

        $this->assertFalse(WahaSetting::current()->isConfigured());
    }
}

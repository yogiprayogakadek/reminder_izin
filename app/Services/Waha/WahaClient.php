<?php

namespace App\Services\Waha;

use App\Models\WahaSetting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WahaClient
{
    public function __construct(protected ?WahaSetting $settings = null)
    {
        $this->settings ??= WahaSetting::current();
    }

    public function settings(): WahaSetting
    {
        return $this->settings;
    }

    public function isConfigured(): bool
    {
        return $this->settings->isConfigured();
    }

    /**
     * @return array{ok: bool, status: string, message: string, sender: ?string}
     */
    public function testConnection(): array
    {
        if (! $this->isConfigured()) {
            $this->persistStatus('unconfigured', 'Base URL dan API key WAHA belum diisi.');

            return [
                'ok' => false,
                'status' => 'unconfigured',
                'message' => 'Base URL dan API key WAHA belum diisi.',
                'sender' => null,
            ];
        }

        try {
            $response = $this->request()->get('api/sessions/'.rawurlencode($this->settings->resolvedSession()));

            if ($response->status() === 404) {
                $response = $this->request()->get('api/sessions');
            }

            if (! $response->successful()) {
                throw new WahaException($this->errorMessage($response), $response->status());
            }

            $data = $response->json();
            $session = $this->extractSession($data, $this->settings->resolvedSession());
            $status = strtoupper((string) ($session['status'] ?? 'UNKNOWN'));
            $sender = $this->extractSender($session);

            $connected = in_array($status, ['WORKING', 'CONNECTED'], true);
            $message = match ($status) {
                'WORKING', 'CONNECTED' => 'Sesi WhatsApp terhubung.',
                'SCAN_QR_CODE' => 'Sesi menunggu pemindaian QR di dashboard WAHA.',
                'STARTING' => 'Sesi sedang dimulai. Coba lagi sebentar lagi.',
                'STOPPED' => 'Sesi berhenti. Jalankan sesi dari dashboard WAHA.',
                'FAILED' => 'Sesi gagal. Periksa dashboard WAHA.',
                default => 'Status sesi: '.$status.'.',
            };

            $this->persistStatus(strtolower($status), $connected ? null : $message, $sender);

            return [
                'ok' => $connected,
                'status' => strtolower($status),
                'message' => $message,
                'sender' => $sender,
            ];
        } catch (WahaException $e) {
            $this->persistStatus('failed', $e->getMessage());

            return ['ok' => false, 'status' => 'failed', 'message' => $e->getMessage(), 'sender' => null];
        } catch (ConnectionException) {
            $message = 'Tidak dapat menjangkau WAHA. Periksa alamat, port, dan jaringan server.';
            $this->persistStatus('unreachable', $message);
            Log::warning('WAHA connection failed', ['base_url' => $this->settings->resolvedBaseUrl()]);

            return ['ok' => false, 'status' => 'unreachable', 'message' => $message, 'sender' => null];
        }
    }

    /**
     * @return array{message_id: ?string, status: string}
     */
    public function sendText(string $phoneE164, string $text): array
    {
        if (! $this->isConfigured()) {
            throw new WahaException('Konfigurasi WAHA belum lengkap.');
        }

        try {
            $response = $this->request()->post('api/sendText', [
                'session' => $this->settings->resolvedSession(),
                'chatId' => $this->chatId($phoneE164),
                'text' => $text,
            ]);
        } catch (ConnectionException) {
            throw new WahaException('Tidak dapat menjangkau WAHA. Pengiriman dibatalkan.');
        }

        if (! $response->successful()) {
            throw new WahaException($this->errorMessage($response), $response->status());
        }

        $data = $response->json();

        return [
            'message_id' => data_get($data, 'id._serialized')
                ?? data_get($data, 'id.id')
                ?? data_get($data, '_data.id._serialized')
                ?? (is_string($data) ? $data : null),
            'status' => (string) data_get($data, 'status', 'sent'),
        ];
    }

    public function chatId(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        return $digits.'@c.us';
    }

    protected function request(): \Illuminate\Http\Client\PendingRequest
    {
        $request = Http::baseUrl($this->settings->resolvedBaseUrl().'/')
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('services.waha.timeout', 15));

        $apiKey = $this->settings->resolvedApiKey();

        if (filled($apiKey)) {
            $request = $request->withHeaders(['X-Api-Key' => $apiKey]);
        }

        return $request;
    }

    protected function extractSession(mixed $data, string $session): array
    {
        if (! is_array($data)) {
            return [];
        }

        if (array_is_list($data)) {
            foreach ($data as $item) {
                if (is_array($item) && (($item['name'] ?? null) === $session)) {
                    return $item;
                }
            }

            return is_array($data[0] ?? null) ? $data[0] : [];
        }

        return $data;
    }

    protected function extractSender(array $session): ?string
    {
        $me = $session['me'] ?? [];

        $id = $me['id'] ?? $me['_serialized'] ?? null;

        if (is_array($id)) {
            $id = $id['_serialized'] ?? $id['id'] ?? null;
        }

        return $id ? preg_replace('/@.*/', '', (string) $id) : null;
    }

    protected function errorMessage(Response $response): string
    {
        $data = $response->json();
        $detail = is_array($data)
            ? ($data['message'] ?? $data['error'] ?? null)
            : null;

        return match ($response->status()) {
            401, 403 => 'API key WAHA ditolak. Periksa kembali kredensial.',
            404 => 'Endpoint WAHA tidak ditemukan. Periksa base URL dan nama sesi.',
            429 => 'WAHA membatasi permintaan. Coba lagi beberapa saat.',
            default => 'WAHA mengembalikan kesalahan'.($detail ? ': '.$detail : ' (HTTP '.$response->status().').'),
        };
    }

    protected function persistStatus(string $status, ?string $error = null, ?string $sender = null): void
    {
        $attributes = [
            'last_status' => $status,
            'last_error' => $error,
            'last_tested_at' => now(),
        ];

        if ($sender) {
            $attributes['sender_number'] = $sender;
        }

        if ($this->settings->exists) {
            $this->settings->forceFill($attributes)->save();

            return;
        }

        $this->settings = WahaSetting::query()->create([
            ...$attributes,
            'session' => $this->settings->session ?: 'default',
            'default_country_code' => $this->settings->default_country_code ?: '62',
            'daily_limit' => $this->settings->daily_limit ?: 500,
            'is_enabled' => $this->settings->is_enabled,
        ]);
    }
}

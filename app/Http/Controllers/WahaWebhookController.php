<?php

namespace App\Http\Controllers;

use App\Enums\DeliveryStatus;
use App\Models\ActivityLog;
use App\Models\ReminderDelivery;
use App\Models\WahaSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WahaWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $settings = WahaSetting::current();
        $secret = $settings->resolvedWebhookSecret();

        if (blank($secret)) {
            Log::warning('WAHA webhook rejected: webhook secret is not configured.');

            return response()->json(['message' => 'Webhook secret belum dikonfigurasi.'], 403);
        }

        $provided = (string) ($request->header('X-Webhook-Secret') ?: $request->query('secret', ''));

        if ($provided === '' || ! hash_equals($secret, $provided)) {
            Log::warning('WAHA webhook rejected: invalid secret.', ['ip' => $request->ip()]);

            return response()->json(['message' => 'Secret tidak valid.'], 403);
        }

        $event = (string) $request->input('event', '');
        $session = $request->input('session');

        if ($session && $settings->resolvedSession() && $session !== $settings->resolvedSession()) {
            return response()->json(['ok' => true, 'ignored' => 'session']);
        }

        if ($event === 'session.status') {
            $status = strtolower((string) $request->input('payload.status', $request->input('payload', '')));

            if ($status !== '') {
                $settings->forceFill([
                    'last_status' => $status,
                    'last_tested_at' => now(),
                    'last_error' => in_array($status, ['working', 'connected'], true) ? null : 'Status dari webhook: '.$status,
                ])->save();
            }

            return response()->json(['ok' => true]);
        }

        if ($event === 'message.ack') {
            $payload = (array) $request->input('payload', []);
            $messageId = data_get($payload, 'id._serialized') ?? data_get($payload, 'id');
            $ack = data_get($payload, 'ack');

            if (! $messageId) {
                return response()->json(['ok' => true, 'ignored' => 'message_id']);
            }

            $delivery = ReminderDelivery::query()
                ->where('waha_message_id', $messageId)
                ->first();

            if (! $delivery || $delivery->status === DeliveryStatus::Cancelled) {
                return response()->json(['ok' => true, 'ignored' => 'delivery']);
            }

            $status = match ((int) $ack) {
                1 => DeliveryStatus::Sent,
                2 => DeliveryStatus::Delivered,
                3 => DeliveryStatus::Read,
                -1 => DeliveryStatus::Failed,
                default => null,
            };

            if ($status) {
                $delivery->forceFill([
                    'status' => $status,
                    'sent_at' => $delivery->sent_at ?? now(),
                    'error_message' => $status === DeliveryStatus::Failed ? 'WAHA melaporkan pengiriman gagal.' : $delivery->error_message,
                ])->save();

                ActivityLog::record(
                    'reminder.webhook',
                    'Status pengiriman diperbarui menjadi '.$status->label().' melalui webhook WAHA.',
                    $delivery->permit,
                );
            }

            return response()->json(['ok' => true]);
        }

        return response()->json(['ok' => true, 'ignored' => $event ?: 'event']);
    }
}

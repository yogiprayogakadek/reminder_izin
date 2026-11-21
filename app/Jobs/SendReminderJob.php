<?php

namespace App\Jobs;

use App\Enums\DeliveryStatus;
use App\Models\ReminderDelivery;
use App\Models\ReminderTemplate;
use App\Models\Setting;
use App\Models\WahaSetting;
use App\Services\Reminders\ReminderMessage;
use App\Services\Waha\WahaClient;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;
use Throwable;

class SendReminderJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public function __construct(public int $deliveryId)
    {
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new RateLimited('waha-send')];
    }

    public function handle(WahaClient $client): void
    {
        Setting::applyRuntime();

        $delivery = ReminderDelivery::query()->with(['permit.category', 'permit.pic', 'contact'])->find($this->deliveryId);

        if (! $delivery) {
            return;
        }

        if (in_array($delivery->status, [DeliveryStatus::Sent, DeliveryStatus::Delivered, DeliveryStatus::Read, DeliveryStatus::Cancelled], true)) {
            return;
        }

        if ($delivery->attempts >= $this->tries) {
            $delivery->forceFill([
                'status' => DeliveryStatus::Failed,
                'error_message' => 'Batas percobaan otomatis tercapai. Kirim ulang secara manual.',
            ])->save();

            return;
        }

        if ($delivery->created_at->lt(now()->subDay()) && $delivery->attempts > 0) {
            $delivery->forceFill([
                'status' => DeliveryStatus::Cancelled,
                'error_message' => 'Pengiriman lebih dari 24 jam dan tidak dikirim otomatis untuk menghindari informasi kedaluwarsa.',
            ])->save();

            return;
        }

        if (! $delivery->is_manual && ! (WahaSetting::current()->is_enabled && WahaSetting::current()->isConfigured())) {
            $delivery->forceFill([
                'status' => DeliveryStatus::Cancelled,
                'error_message' => 'WAHA sedang nonaktif atau belum dikonfigurasi.',
            ])->save();

            return;
        }

        $permit = $delivery->permit;

        if (! $permit || $permit->archived_at !== null) {
            $delivery->forceFill(['status' => DeliveryStatus::Cancelled, 'error_message' => 'Izin sudah diarsipkan.'])->save();

            return;
        }

        $phone = $delivery->recipient_phone ?: $delivery->contact?->phone_e164;

        if (! $phone) {
            $delivery->forceFill(['status' => DeliveryStatus::Cancelled, 'error_message' => 'Kontak penerima tidak tersedia.'])->save();

            return;
        }

        $template = $delivery->template ?: ReminderTemplate::forCategory($permit->category_id);
        $message = $delivery->message_body ?: app(ReminderMessage::class)->render($permit, $template, $delivery->contact);

        $delivery->forceFill([
            'status' => DeliveryStatus::Queued,
            'attempts' => $delivery->attempts + 1,
            'message_body' => $message,
            'reminder_template_id' => $template?->id,
        ])->save();

        try {
            $result = $client->sendText($phone, $message);

            $delivery->forceFill([
                'status' => DeliveryStatus::Sent,
                'sent_at' => now(),
                'waha_message_id' => $result['message_id'] ?: $delivery->waha_message_id,
                'error_message' => null,
            ])->save();
        } catch (Throwable $e) {
            $delivery->forceFill([
                'status' => DeliveryStatus::Failed,
                'error_message' => mb_substr($e->getMessage(), 0, 500),
            ])->save();

            throw $e;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $delivery = ReminderDelivery::query()->find($this->deliveryId);

        if ($delivery && ! in_array($delivery->status, [DeliveryStatus::Sent, DeliveryStatus::Delivered, DeliveryStatus::Read], true)) {
            $delivery->forceFill([
                'status' => DeliveryStatus::Failed,
                'error_message' => mb_substr($exception?->getMessage() ?: 'Pengiriman gagal.', 0, 500),
            ])->save();
        }
    }
}

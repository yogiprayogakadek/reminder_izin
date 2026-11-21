<?php

namespace App\Enums;

enum DeliveryStatus: string
{
    case Pending = 'pending';
    case Queued = 'queued';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Read = 'read';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Queued => 'Dalam antrean',
            self::Sent => 'Terkirim',
            self::Delivered => 'Diterima',
            self::Read => 'Dibaca',
            self::Failed => 'Gagal',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending, self::Queued => 'neutral',
            self::Sent, self::Delivered, self::Read => 'success',
            self::Failed => 'danger',
            self::Cancelled => 'warning',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Delivered, self::Read, self::Cancelled], true);
    }
}

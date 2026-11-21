<?php

namespace App\Enums;

use Carbon\CarbonImmutable;

enum PermitStatus: string
{
    case Active = 'active';
    case Expiring = 'expiring';
    case Expired = 'expired';
    case Renewing = 'renewing';
    case Archived = 'archived';

    public static function fromDates(
        CarbonImmutable $expiresAt,
        CarbonImmutable $today,
        bool $isRenewing = false,
        bool $isArchived = false,
    ): self {
        if ($isArchived) {
            return self::Archived;
        }

        if ($isRenewing) {
            return self::Renewing;
        }

        $expires = $expiresAt->startOfDay();
        $current = $today->startOfDay();

        if ($expires->lt($current)) {
            return self::Expired;
        }

        if ($current->diffInDays($expires) <= 30) {
            return self::Expiring;
        }

        return self::Active;
    }

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Expiring => 'Akan kedaluwarsa',
            self::Expired => 'Kedaluwarsa',
            self::Renewing => 'Sedang diperpanjang',
            self::Archived => 'Diarsipkan',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Expiring => 'warning',
            self::Expired => 'danger',
            self::Renewing => 'info',
            self::Archived => 'neutral',
        };
    }
}

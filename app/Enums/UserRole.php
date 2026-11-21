<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Secretary = 'secretary';
    case Hrd = 'hrd';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Secretary => 'Sekretaris',
            self::Hrd => 'HRD',
            self::Viewer => 'Viewer',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Admin => 'info',
            self::Secretary => 'neutral',
            self::Hrd => 'success',
            self::Viewer => 'neutral',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role) => [$role->value => $role->label()])
            ->all();
    }
}

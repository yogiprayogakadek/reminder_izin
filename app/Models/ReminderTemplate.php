<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReminderTemplate extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'body',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PermitCategory::class, 'category_id')->withTrashed();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function forCategory(?int $categoryId): ?self
    {
        if ($categoryId !== null) {
            $specific = static::active()
                ->where('category_id', $categoryId)
                ->latest('updated_at')
                ->first();

            if ($specific) {
                return $specific;
            }
        }

        return static::active()
            ->whereNull('category_id')
            ->latest('updated_at')
            ->first();
    }

    public static function defaultBody(): string
    {
        return implode("\n", [
            'Yth. Bapak/Ibu,',
            '',
            'Pengingat masa berlaku izin perusahaan:',
            '• Nama: {nama_izin}',
            '• Nomor: {nomor_izin}',
            '• Kategori: {kategori}',
            '• Penerbit: {penerbit}',
            '• Kedaluwarsa: {tanggal_kedaluwarsa} ({sisa_hari})',
            '• PIC: {pic}',
            '',
            'Mohon segera ditindaklanjuti. Terima kasih.',
        ]);
    }
}

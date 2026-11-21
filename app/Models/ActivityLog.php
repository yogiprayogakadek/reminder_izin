<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public static function record(
        string $action,
        string $description,
        ?Model $subject = null,
        array $oldValues = [],
        array $newValues = [],
    ): self {
        $request = request();

        return static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'ip_address' => $request?->ip(),
            'user_agent' => mb_substr((string) $request?->userAgent(), 0, 500),
        ]);
    }

    public function actionLabel(): string
    {
        return static::labelFor($this->action);
    }

    public static function labelFor(string $action): string
    {
        return match ($action) {
            'auth.login' => 'Masuk',
            'auth.logout' => 'Keluar',
            'permit.created' => 'Membuat izin',
            'permit.updated' => 'Mengubah izin',
            'permit.deleted' => 'Menghapus izin',
            'permit.renewed' => 'Memperpanjang izin',
            'permit.renewal_started' => 'Mulai perpanjangan',
            'permit.renewal_finished' => 'Selesai perpanjangan',
            'permit.archived' => 'Mengarsipkan izin',
            'permit.unarchived' => 'Mengeluarkan dari arsip',
            'permit.paused' => 'Menjeda reminder',
            'permit.resumed' => 'Mengaktifkan reminder',
            'category.created' => 'Membuat kategori',
            'category.updated' => 'Mengubah kategori',
            'category.deactivated' => 'Menonaktifkan kategori',
            'contact.created' => 'Membuat kontak',
            'contact.updated' => 'Mengubah kontak',
            'contact.deactivated' => 'Menonaktifkan kontak',
            'user.created' => 'Membuat pengguna',
            'user.updated' => 'Mengubah pengguna',
            'user.deactivated' => 'Menonaktifkan pengguna',
            'user.password_reset' => 'Mengatur ulang kata sandi',
            'reminder.rule_created' => 'Membuat aturan reminder',
            'reminder.rule_updated' => 'Mengubah aturan reminder',
            'reminder.rule_deleted' => 'Menghapus aturan reminder',
            'reminder.template_created' => 'Membuat template',
            'reminder.template_updated' => 'Mengubah template',
            'reminder.template_deleted' => 'Menghapus template',
            'reminder.manual_sent' => 'Mengirim reminder manual',
            'reminder.retried' => 'Mengirim ulang reminder',
            'reminder.cancelled' => 'Membatalkan pengiriman',
            'reminder.webhook' => 'Status dari webhook WAHA',
            'settings.updated' => 'Mengubah pengaturan',
            'holiday.created' => 'Menambah hari libur',
            'holiday.deleted' => 'Menghapus hari libur',
            'waha.updated' => 'Mengubah konfigurasi WAHA',
            'waha.tested' => 'Menguji koneksi WAHA',
            default => str($action)->replace(['.', '_'], ' ')->title()->toString(),
        };
    }
}

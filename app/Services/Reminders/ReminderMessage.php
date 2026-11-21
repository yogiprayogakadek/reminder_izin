<?php

namespace App\Services\Reminders;

use App\Models\Contact;
use App\Models\Permit;
use App\Models\ReminderTemplate;
use App\Models\Setting;
use Carbon\CarbonImmutable;

class ReminderMessage
{
    public function render(Permit $permit, ?ReminderTemplate $template = null, ?Contact $contact = null): string
    {
        $permit->loadMissing(['category', 'pic']);

        $template ??= ReminderTemplate::forCategory($permit->category_id);
        $body = $template?->body ?: ReminderTemplate::defaultBody();

        $days = $permit->days_remaining;
        $remaining = match (true) {
            $days < 0 => 'sudah kedaluwarsa '.abs($days).' hari lalu',
            $days === 0 => 'kedaluwarsa hari ini',
            $days === 1 => 'tersisa 1 hari',
            default => 'tersisa '.$days.' hari',
        };

        $link = $contact?->user_id
            ? rtrim(Setting::appUrl(), '/').'/izin/'.$permit->getKey()
            : '';

        $replacements = [
            '{nama_izin}' => $permit->title,
            '{nomor_izin}' => $permit->number ?: '-',
            '{kategori}' => $permit->category?->name ?? '-',
            '{penerbit}' => $permit->issuer,
            '{tanggal_kedaluwarsa}' => $permit->expires_at->locale('id')->translatedFormat('d F Y'),
            '{sisa_hari}' => $remaining,
            '{pic}' => $permit->pic?->name ?? '-',
            '{link_aplikasi}' => $link,
        ];

        $message = strtr($body, $replacements);

        if ($link === '') {
            $message = preg_replace("/\n{3,}/", "\n\n", trim($message)) ?? $message;
        }

        return $message;
    }

    public static function previewData(): array
    {
        return [
            '{nama_izin}' => 'Izin Reklame',
            '{nomor_izin}' => '503/REK/2026',
            '{kategori}' => 'Reklame',
            '{penerbit}' => 'Dinas Penanaman Modal',
            '{tanggal_kedaluwarsa}' => CarbonImmutable::today()->addDays(14)->locale('id')->translatedFormat('d F Y'),
            '{sisa_hari}' => 'tersisa 14 hari',
            '{pic}' => 'Sekretaris',
            '{link_aplikasi}' => 'https://siperiz.perusahaan.com/izin/1',
        ];
    }
}

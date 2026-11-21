<?php

namespace App\Http\Controllers;

use App\Models\Permit;
use App\Models\ReminderDelivery;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    public function permits(Request $request): StreamedResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'kategori' => ['nullable', 'integer'],
            'status' => ['nullable', 'string', 'in:all,active,expiring,expired,renewing,archived'],
            'pic' => ['nullable', 'integer'],
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date'],
        ]);

        $query = Permit::query()
            ->with(['category', 'pic'])
            ->when(($filters['status'] ?? 'all') === 'all', fn (Builder $q) => $q->whereNull('archived_at'))
            ->when(($filters['status'] ?? '') === 'archived', fn (Builder $q) => $q->whereNotNull('archived_at'))
            ->when(
                filled($filters['status'] ?? '') && ! in_array($filters['status'], ['all', 'archived'], true),
                fn (Builder $q) => $q->whereNull('archived_at')->status($filters['status']),
            )
            ->when(filled($filters['kategori'] ?? ''), fn (Builder $q) => $q->where('category_id', $filters['kategori']))
            ->when(filled($filters['pic'] ?? ''), fn (Builder $q) => $q->where('pic_user_id', $filters['pic']))
            ->when(filled($filters['dari'] ?? ''), fn (Builder $q) => $q->whereDate('expires_at', '>=', $filters['dari']))
            ->when(filled($filters['sampai'] ?? ''), fn (Builder $q) => $q->whereDate('expires_at', '<=', $filters['sampai']))
            ->when(filled($filters['q'] ?? ''), function (Builder $q) use ($filters) {
                $term = '%'.$filters['q'].'%';
                $q->where(function (Builder $inner) use ($term) {
                    $inner->where('title', 'like', $term)
                        ->orWhere('number', 'like', $term)
                        ->orWhere('issuer', 'like', $term)
                        ->orWhere('location', 'like', $term);
                });
            })
            ->orderBy('expires_at')
            ->orderBy('title');

        return $this->csv(
            'izin-'.CarbonImmutable::now()->format('Ymd-His').'.csv',
            [
                'Nama Izin', 'Nomor', 'Kategori', 'Penerbit', 'Tanggal Terbit', 'Kedaluwarsa',
                'Sisa Hari', 'Status', 'PIC', 'Lokasi', 'Reminder', 'Diarsipkan',
            ],
            (function () use ($query) {
                foreach ($query->cursor() as $permit) {
                    yield [
                        $permit->title,
                        $permit->number,
                        $permit->category?->name,
                        $permit->issuer,
                        $permit->issued_at?->toDateString(),
                        $permit->expires_at->toDateString(),
                        $permit->days_remaining,
                        $permit->status->label(),
                        $permit->pic?->name,
                        $permit->location,
                        $permit->reminder_enabled ? ($permit->is_paused ? 'Dijeda' : 'Aktif') : 'Nonaktif',
                        $permit->archived_at?->toDateString(),
                    ];
                }
            })(),
        );
    }

    public function deliveries(Request $request): StreamedResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'string', 'max:20'],
            'mode' => ['nullable', 'string', 'in:manual,auto'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $query = ReminderDelivery::query()
            ->with(['permit', 'contact', 'rule', 'creator'])
            ->when(filled($filters['q'] ?? ''), function (Builder $q) use ($filters) {
                $term = '%'.$filters['q'].'%';
                $q->where(function (Builder $inner) use ($term) {
                    $inner->where('recipient_phone', 'like', $term)
                        ->orWhereHas('contact', fn (Builder $contact) => $contact->where('name', 'like', $term))
                        ->orWhereHas('permit', fn (Builder $permit) => $permit->where('title', 'like', $term)->orWhere('number', 'like', $term));
                });
            })
            ->when(filled($filters['status'] ?? ''), fn (Builder $q) => $q->where('status', $filters['status']))
            ->when(($filters['mode'] ?? '') === 'manual', fn (Builder $q) => $q->where('is_manual', true))
            ->when(($filters['mode'] ?? '') === 'auto', fn (Builder $q) => $q->where('is_manual', false))
            ->when(filled($filters['from'] ?? ''), fn (Builder $q) => $q->whereDate('created_at', '>=', $filters['from']))
            ->when(filled($filters['to'] ?? ''), fn (Builder $q) => $q->whereDate('created_at', '<=', $filters['to']))
            ->latest();

        return $this->csv(
            'riwayat-reminder-'.CarbonImmutable::now()->format('Ymd-His').'.csv',
            [
                'Waktu dibuat', 'Izin', 'Kontak', 'Nomor WhatsApp', 'Jenis', 'Aturan',
                'Status', 'Percobaan', 'Waktu terkirim', 'Pesan', 'Kesalahan', 'Dibuat oleh',
            ],
            (function () use ($query) {
                foreach ($query->cursor() as $delivery) {
                    yield [
                        $delivery->created_at?->toDateTimeString(),
                        $delivery->permit?->title,
                        $delivery->contact?->name,
                        $delivery->recipient_phone,
                        $delivery->is_manual ? 'Manual' : 'Otomatis',
                        $delivery->rule?->label(),
                        $delivery->status->label(),
                        $delivery->attempts,
                        $delivery->sent_at?->toDateTimeString(),
                        $delivery->message_body,
                        $delivery->error_message,
                        $delivery->creator?->name,
                    ];
                }
            })(),
        );
    }

    protected function csv(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers, ';');

            foreach ($rows as $row) {
                fputcsv($handle, array_map(fn ($value) => $this->clean($value), $row), ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    protected function clean(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $value = str_replace(["\r\n", "\r"], "\n", (string) $value);

        if (str_starts_with($value, '=') || str_starts_with($value, '+') || str_starts_with($value, '-') || str_starts_with($value, '@')) {
            return "'".$value;
        }

        return $value;
    }
}

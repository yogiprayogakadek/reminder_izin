@php
    $exportQuery = array_filter([
        'q' => $search,
        'status' => $status,
        'mode' => $mode,
        'from' => $from,
        'to' => $to,
    ], fn ($value) => $value !== '');
@endphp

<div class="space-y-5">
    <x-page-header title="Riwayat pengiriman" description="Catatan setiap pesan WhatsApp yang dibuat sistem, termasuk yang gagal. Data ini tidak dihapus saat izin diperbarui.">
        <x-slot name="actions">
            <a href="{{ route('reports.deliveries.export', $exportQuery) }}" class="btn-secondary">
                <x-icon name="download" :size="16" />
                Ekspor CSV
            </a>
        </x-slot>
    </x-page-header>

    <x-notification-tabs />

    <x-flash />

    <section class="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="Ringkasan pengiriman">
        <div class="card-flat p-4">
            <p class="micro-label">Total pada filter</p>
            <p class="stat-value mt-1">{{ number_format($summary['total'], 0, ',', '.') }}</p>
        </div>
        <div class="card-flat p-4">
            <p class="micro-label">Berhasil</p>
            <p class="stat-value mt-1 text-success">{{ $summary['success_rate'] === null ? '-' : $summary['success_rate'].'%' }}</p>
            <p class="mt-1 text-[10.5px] text-muted">{{ number_format($summary['success'], 0, ',', '.') }} pesan terkirim</p>
        </div>
        <div class="card-flat p-4">
            <p class="micro-label">Gagal</p>
            <p class="stat-value mt-1 {{ $summary['failed'] > 0 ? 'text-danger' : '' }}">{{ number_format($summary['failed'], 0, ',', '.') }}</p>
        </div>
        <div class="card-flat p-4">
            <p class="micro-label">Menunggu antrean</p>
            <p class="stat-value mt-1">{{ number_format($summary['pending'], 0, ',', '.') }}</p>
            <p class="mt-1 text-[10.5px] text-muted">{{ $wahaReady ? 'WAHA aktif' : 'WAHA belum aktif' }}</p>
        </div>
    </section>

    <div class="card p-4 sm:p-5">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-12">
            <div class="xl:col-span-4">
                <label for="delivery-search" class="micro-label mb-1 block">Pencarian</label>
                <div class="relative">
                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted"><x-icon name="search" :size="15" /></span>
                    <input id="delivery-search" type="search" wire:model.live.debounce.350ms="search" placeholder="Nama kontak, nomor, atau izin..." class="field pl-9" />
                </div>
            </div>
            <div class="xl:col-span-2">
                <label for="delivery-status" class="micro-label mb-1 block">Status</label>
                <select id="delivery-status" wire:model.live="status" class="field">
                    <option value="">Semua status</option>
                    @foreach ($statuses as $option)
                        <option value="{{ $option->value }}">{{ $option->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="xl:col-span-2">
                <label for="delivery-mode" class="micro-label mb-1 block">Jenis</label>
                <select id="delivery-mode" wire:model.live="mode" class="field">
                    <option value="">Semua</option>
                    <option value="auto">Otomatis</option>
                    <option value="manual">Manual</option>
                </select>
            </div>
            <div class="xl:col-span-2">
                <label for="delivery-from" class="micro-label mb-1 block">Dari tanggal</label>
                <input id="delivery-from" type="date" wire:model.live="from" class="field" />
            </div>
            <div class="xl:col-span-2">
                <label for="delivery-to" class="micro-label mb-1 block">Sampai tanggal</label>
                <input id="delivery-to" type="date" wire:model.live="to" class="field" />
            </div>
        </div>
        <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
            <p class="text-[11px] text-muted">{{ $deliveries->total() }} pengiriman cocok dengan filter.</p>
            <button type="button" wire:click="resetFilters" class="btn-ghost px-2 text-[11.5px]">Reset filter</button>
        </div>
    </div>

    <div class="card overflow-hidden" wire:loading.class="opacity-60" wire:target="search,status,mode,from,to,resetFilters">
        @if ($deliveries->isEmpty())
            <div class="p-5">
                <x-empty-state icon="send" title="Belum ada pengiriman" description="Reminder yang dibuat otomatis atau manual akan muncul di sini beserta status pengirimannya." />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] border-collapse text-left">
                    <thead class="bg-surface-2/70">
                        <tr>
                            <th class="table-head">Waktu</th>
                            <th class="table-head">Penerima</th>
                            <th class="table-head">Izin</th>
                            <th class="table-head">Aturan</th>
                            <th class="table-head">Jenis</th>
                            <th class="table-head">Status</th>
                            <th class="table-head text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($deliveries as $delivery)
                            <tr class="border-t border-line/70 align-top transition hover:bg-surface-2/40">
                                <td class="table-cell whitespace-nowrap">
                                    <span class="block text-[11.5px] font-semibold text-ink">{{ $delivery->created_at->translatedFormat('d M Y') }}</span>
                                    <span class="block text-[10.5px] text-muted">{{ $delivery->created_at->format('H:i') }} WITA</span>
                                </td>
                                <td class="table-cell">
                                    <span class="block text-[11.5px] font-semibold text-ink">{{ $delivery->contact?->name ?? 'Kontak dihapus' }}</span>
                                    <span class="block font-mono text-[10px] text-muted">{{ $delivery->recipient_phone }}</span>
                                </td>
                                <td class="table-cell">
                                    @if ($delivery->permit)
                                        <a href="{{ route('permits.show', $delivery->permit) }}" wire:navigate class="block max-w-[220px] truncate text-[11.5px] font-semibold text-ink hover:text-primary">{{ $delivery->permit->title }}</a>
                                    @else
                                        <span class="text-[11px] text-muted">Izin dihapus</span>
                                    @endif
                                    @if ($delivery->error_message)
                                        <span class="mt-0.5 block max-w-[240px] truncate text-[10px] text-danger" title="{{ $delivery->error_message }}">{{ $delivery->error_message }}</span>
                                    @endif
                                </td>
                                <td class="table-cell">
                                    <span class="block text-[11px] text-ink-2">{{ $delivery->rule?->label() ?? '-' }}</span>
                                    <span class="block text-[10px] text-muted">{{ $delivery->attempts }} percobaan</span>
                                </td>
                                <td class="table-cell">
                                    <span class="pill {{ $delivery->is_manual ? 'pill-info' : 'pill-neutral' }}">{{ $delivery->is_manual ? 'Manual' : 'Otomatis' }}</span>
                                </td>
                                <td class="table-cell"><x-delivery-pill :status="$delivery->status" /></td>
                                <td class="table-cell">
                                    <div class="flex justify-end gap-1">
                                        <button type="button" wire:click="viewMessage({{ $delivery->id }})" class="icon-button" aria-label="Lihat pesan untuk {{ $delivery->contact?->name ?? 'kontak' }}" title="Lihat pesan">
                                            <x-icon name="eye" :size="15" />
                                        </button>
                                        @if (in_array($delivery->status, [\App\Enums\DeliveryStatus::Failed, \App\Enums\DeliveryStatus::Cancelled], true))
                                            <button type="button" wire:click="retry({{ $delivery->id }})" wire:confirm="Kirim ulang pesan ini ke {{ $delivery->contact?->name ?? $delivery->recipient_phone }}?" class="icon-button" aria-label="Kirim ulang" title="Kirim ulang" wire:loading.attr="disabled" wire:target="retry({{ $delivery->id }})">
                                                <x-icon name="refresh" :size="15" />
                                            </button>
                                        @endif
                                        @if (! in_array($delivery->status, [\App\Enums\DeliveryStatus::Sent, \App\Enums\DeliveryStatus::Delivered, \App\Enums\DeliveryStatus::Read, \App\Enums\DeliveryStatus::Cancelled], true))
                                            <button type="button" wire:click="cancel({{ $delivery->id }})" wire:confirm="Batalkan pengiriman ini? Pesan tidak akan dikirim." class="icon-button text-danger hover:border-danger/40 hover:bg-danger-soft/60 hover:text-danger" aria-label="Batalkan pengiriman" title="Batalkan">
                                                <x-icon name="x" :size="15" />
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-line px-4 py-3">{{ $deliveries->links() }}</div>
        @endif
    </div>

    @if ($viewing)
        <div class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-labelledby="message-title" x-on:keydown.escape.window="$wire.closeMessage()">
            <div class="absolute inset-0 bg-ink/45 backdrop-blur-sm" wire:click="closeMessage" aria-hidden="true"></div>
            <div class="absolute inset-y-0 right-0 flex w-full max-w-lg flex-col overflow-y-auto bg-surface p-5 shadow-float sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 id="message-title" class="text-[18px] font-bold tracking-[-0.02em] text-ink">Isi pesan</h2>
                        <p class="mt-0.5 text-[11.5px] text-muted">Dikirim ke {{ $viewing->contact?->name ?? 'kontak' }} &middot; {{ $viewing->recipient_phone }}</p>
                    </div>
                    <button type="button" wire:click="closeMessage" class="icon-button" aria-label="Tutup pesan" data-autofocus><x-icon name="x" :size="18" /></button>
                </div>

                <div class="mt-5 rounded-2xl bg-[#e6ddd4] p-3 dark:bg-[#1f2c34]">
                    <div class="rounded-xl rounded-tl-sm bg-white px-3.5 py-3 shadow-sm dark:bg-[#005c4b]">
                        <p class="whitespace-pre-line text-[12px] leading-5 text-[#111b21] dark:text-[#e9edef]">{{ $viewing->message_body ?: 'Isi pesan tidak tersimpan.' }}</p>
                        <p class="mt-2 text-right text-[9px] text-[#667781] dark:text-[#c7e4dc]">{{ $viewing->created_at->format('H:i') }} &middot; {{ $viewing->status->label() }}</p>
                    </div>
                </div>

                <dl class="mt-5 space-y-2 text-[11.5px]">
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-muted">Status</dt>
                        <dd><x-delivery-pill :status="$viewing->status" /></dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-muted">Izin</dt>
                        <dd class="text-right font-semibold text-ink">{{ $viewing->permit?->title ?? '-' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-muted">Aturan</dt>
                        <dd class="font-semibold text-ink">{{ $viewing->rule?->label() ?? 'Manual' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-muted">Template</dt>
                        <dd class="text-right font-semibold text-ink">{{ $viewing->template?->name ?? 'Tidak tersimpan' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-muted">Percobaan</dt>
                        <dd class="font-semibold text-ink">{{ $viewing->attempts }}x</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-muted">Dibuat</dt>
                        <dd class="text-right font-semibold text-ink">{{ $viewing->created_at->translatedFormat('d M Y, H:i') }}</dd>
                    </div>
                    @if ($viewing->sent_at)
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-muted">Terkirim</dt>
                            <dd class="text-right font-semibold text-ink">{{ $viewing->sent_at->translatedFormat('d M Y, H:i') }}</dd>
                        </div>
                    @endif
                    @if ($viewing->creator)
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-muted">Dibuat oleh</dt>
                            <dd class="font-semibold text-ink">{{ $viewing->creator->name }}</dd>
                        </div>
                    @endif
                </dl>

                @if ($viewing->error_message)
                    <div class="mt-4 rounded-xl bg-danger-soft px-3 py-2.5 text-[11px] leading-5 text-danger">{{ $viewing->error_message }}</div>
                @endif

                <div class="mt-5 flex gap-2 border-t border-line pt-4">
                    <button type="button" wire:click="closeMessage" class="btn-secondary flex-1">Tutup</button>
                    @if (in_array($viewing->status, [\App\Enums\DeliveryStatus::Failed, \App\Enums\DeliveryStatus::Cancelled], true))
                        <button type="button" wire:click="retry({{ $viewing->id }})" wire:confirm="Kirim ulang pesan ini?" class="btn-primary flex-1">
                            <x-icon name="refresh" :size="15" />
                            Kirim ulang
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>

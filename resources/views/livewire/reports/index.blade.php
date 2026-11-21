@php
    $total = (int) ($statusCounts->total ?? 0);
    $expiringNow = (int) ($statusCounts->expiring ?? 0);
    $expiredNow = (int) ($statusCounts->expired ?? 0);
    $renewingNow = (int) ($statusCounts->renewing ?? 0);
@endphp

<div class="space-y-5">
    <x-page-header title="Laporan" description="Rekap izin, perpanjangan, dan pengiriman reminder pada periode tertentu. Semua angka berasal dari data aktual.">
        <x-slot name="actions">
            <a href="{{ $permitExportUrl }}" class="btn-secondary">
                <x-icon name="download" :size="16" />
                Ekspor izin
            </a>
            @if ($deliveryExportUrl)
                <a href="{{ $deliveryExportUrl }}" class="btn-primary">
                    <x-icon name="download" :size="16" />
                    Ekspor reminder
                </a>
            @endif
        </x-slot>
    </x-page-header>

    <div class="card p-4 sm:p-5">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-12">
            <div class="xl:col-span-3">
                <label for="report-from" class="micro-label mb-1 block">Dari tanggal</label>
                <input id="report-from" type="date" wire:model.live="from" class="field" />
            </div>
            <div class="xl:col-span-3">
                <label for="report-to" class="micro-label mb-1 block">Sampai tanggal</label>
                <input id="report-to" type="date" wire:model.live="to" class="field" />
            </div>
            <div class="xl:col-span-4">
                <label for="report-category" class="micro-label mb-1 block">Kategori</label>
                <select id="report-category" wire:model.live="categoryId" class="field">
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}{{ $category->is_active ? '' : ' (nonaktif)' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end xl:col-span-2">
                <button type="button" wire:click="resetFilters" class="btn-ghost h-11 w-full text-[11.5px]">Reset filter</button>
            </div>
        </div>

        <div class="mt-3 flex flex-wrap items-center gap-1.5">
            <span class="micro-label mr-1">Cepat:</span>
            <button type="button" wire:click="setPeriod('month')" class="inline-flex min-h-11 items-center rounded-full bg-surface-2 px-3.5 text-[10.5px] font-semibold text-ink-2 transition hover:bg-primary-soft hover:text-primary">Bulan ini</button>
            <button type="button" wire:click="setPeriod('quarter')" class="inline-flex min-h-11 items-center rounded-full bg-surface-2 px-3.5 text-[10.5px] font-semibold text-ink-2 transition hover:bg-primary-soft hover:text-primary">90 hari</button>
            <button type="button" wire:click="setPeriod('year')" class="inline-flex min-h-11 items-center rounded-full bg-surface-2 px-3.5 text-[10.5px] font-semibold text-ink-2 transition hover:bg-primary-soft hover:text-primary">Tahun ini</button>
            <button type="button" wire:click="setPeriod('twelve')" class="inline-flex min-h-11 items-center rounded-full bg-surface-2 px-3.5 text-[10.5px] font-semibold text-ink-2 transition hover:bg-primary-soft hover:text-primary">12 bulan</button>
            <span class="ml-auto text-[10.5px] text-muted">{{ $periodStart->translatedFormat('d M Y') }} &ndash; {{ $periodEnd->translatedFormat('d M Y') }}</span>
        </div>
    </div>

    <section class="grid grid-cols-2 gap-3 xl:grid-cols-4" aria-label="Ringkasan laporan" wire:loading.class="opacity-60" wire:target="from,to,categoryId,setPeriod,resetFilters">
        <div class="card-flat p-4">
            <p class="micro-label">Jatuh tempo pada periode</p>
            <p class="stat-value mt-1">{{ number_format($periodCount, 0, ',', '.') }}</p>
            <p class="mt-1 text-[10.5px] text-muted">Izin tidak diarsipkan</p>
        </div>
        <div class="card-flat p-4">
            <p class="micro-label">Perpanjangan</p>
            <p class="stat-value mt-1">{{ number_format($renewalCount, 0, ',', '.') }}</p>
            <p class="mt-1 text-[10.5px] text-muted">Dicatat pada periode</p>
        </div>
        <div class="card-flat p-4">
            <p class="micro-label">Akan kedaluwarsa</p>
            <p class="stat-value mt-1 text-warning">{{ number_format($expiringNow, 0, ',', '.') }}</p>
            <p class="mt-1 text-[10.5px] text-muted">Dalam 30 hari ke depan</p>
        </div>
        @if ($canSeeDeliveries && $deliverySummary)
            <div class="card-flat p-4">
                <p class="micro-label">Keberhasilan reminder</p>
                <p class="stat-value mt-1 {{ $deliverySummary['rate'] !== null && $deliverySummary['rate'] >= 90 ? 'text-success' : '' }}">{{ $deliverySummary['rate'] === null ? '-' : $deliverySummary['rate'].'%' }}</p>
                <p class="mt-1 text-[10.5px] text-muted">{{ number_format($deliverySummary['success'], 0, ',', '.') }} dari {{ number_format($deliverySummary['total'], 0, ',', '.') }} pesan</p>
            </div>
        @else
            <div class="card-flat p-4">
                <p class="micro-label">Kedaluwarsa saat ini</p>
                <p class="stat-value mt-1 {{ $expiredNow > 0 ? 'text-danger' : '' }}">{{ number_format($expiredNow, 0, ',', '.') }}</p>
                <p class="mt-1 text-[10.5px] text-muted">Belum diperpanjang</p>
            </div>
        @endif
    </section>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
        <section class="card p-4 sm:p-5 xl:col-span-7" aria-labelledby="report-category-title">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <h2 id="report-category-title" class="card-title">Izin per kategori</h2>
                    <p class="mt-0.5 text-[11px] text-muted">Jumlah izin yang jatuh tempo pada periode, per kategori.</p>
                </div>
                <span class="pill pill-neutral">{{ $total }} izin aktif saat ini</span>
            </div>

            @if ($categoryStats->isEmpty())
                <p class="mt-4 rounded-2xl bg-surface-2 px-3 py-3 text-[11.5px] text-muted">Belum ada kategori izin.</p>
            @else
                <div class="mt-4 space-y-3">
                    @foreach ($categoryStats as $category)
                        <div>
                            <div class="flex items-center justify-between gap-3 text-[11.5px]">
                                <span class="flex min-w-0 items-center gap-2 font-semibold text-ink">
                                    <span class="h-2 w-2 shrink-0 rounded-full" style="background-color: {{ $category->color }}"></span>
                                    <span class="truncate">{{ $category->name }}</span>
                                    @if (! $category->is_active)
                                        <span class="pill pill-neutral">Nonaktif</span>
                                    @endif
                                </span>
                                <span class="flex shrink-0 items-center gap-2">
                                    @if ($category->expiring_count > 0)
                                        <span class="text-[9.5px] font-semibold text-warning">{{ $category->expiring_count }} segera</span>
                                    @endif
                                    @if ($category->expired_count > 0)
                                        <span class="text-[9.5px] font-semibold text-danger">{{ $category->expired_count }} lewat</span>
                                    @endif
                                    <span class="w-8 text-right font-bold text-ink">{{ $category->period_count }}</span>
                                </span>
                            </div>
                            <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-neutral-soft" role="presentation">
                                <div class="h-full rounded-full" style="width: {{ $category->period_count > 0 ? max(4, round($category->period_count / $maxPeriod * 100)) : 0 }}%; background-color: {{ $category->color }}"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="mt-5 border-t border-line pt-3">
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[10px] text-muted">
                    <span>Status saat ini: <span class="font-semibold text-success">{{ (int) ($statusCounts->active ?? 0) }} aktif</span></span>
                    <span class="font-semibold text-warning">{{ $expiringNow }} akan berakhir</span>
                    <span class="font-semibold text-danger">{{ $expiredNow }} kedaluwarsa</span>
                    <span class="font-semibold text-info">{{ $renewingNow }} diperpanjang</span>
                </div>
            </div>
        </section>

        <section class="card p-4 sm:p-5 xl:col-span-5" aria-labelledby="report-month-title">
            <h2 id="report-month-title" class="card-title">Jatuh tempo per bulan</h2>
            <p class="mt-0.5 text-[11px] text-muted">Sebaran tanggal kedaluwarsa dalam periode.</p>

            @if (count($months) === 0 || $periodCount === 0)
                <div class="mt-4 flex h-40 flex-col items-center justify-center rounded-2xl bg-surface-2/60 text-center">
                    <x-icon name="chart" :size="22" class="text-muted" />
                    <p class="mt-2 text-[11.5px] font-semibold text-ink">Belum ada data pada periode ini</p>
                    <p class="mt-0.5 text-[10.5px] text-muted">Ubah rentang tanggal atau tambahkan izin.</p>
                </div>
            @else
                <div class="mt-4 flex h-40 items-end gap-1.5" role="img" aria-label="Grafik izin jatuh tempo per bulan">
                    @foreach ($months as $month)
                        <div class="group flex min-w-0 flex-1 flex-col items-center justify-end gap-1" title="{{ $month['label'] }}: {{ $month['count'] }} izin">
                            <span class="text-[9px] font-semibold tabular-nums {{ $month['count'] > 0 ? 'text-ink' : 'text-muted' }}">{{ $month['count'] > 0 ? $month['count'] : '' }}</span>
                            <div class="w-full rounded-t-md {{ $month['current'] ? 'bg-primary' : 'bg-primary/45' }} transition group-hover:bg-primary" style="height: {{ max(3, round($month['count'] / $maxMonth * 100)) }}%"></div>
                            <span class="max-w-full truncate text-[8.5px] {{ $month['current'] ? 'font-bold text-primary' : 'text-muted' }}">{{ $month['short'] }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
        <section class="card p-4 sm:p-5 xl:col-span-7" aria-labelledby="report-expiring-title">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <h2 id="report-expiring-title" class="card-title">Perlu perhatian</h2>
                    <p class="mt-0.5 text-[11px] text-muted">Izin yang kedaluwarsa dalam 30 hari ke depan.</p>
                </div>
                <span class="pill {{ $expiringNow > 0 ? 'pill-warning' : 'pill-neutral' }}">{{ $expiringNow }}</span>
            </div>

            <div class="mt-3 space-y-2">
                @forelse ($expiringSoon as $permit)
                    <a href="{{ route('permits.show', $permit) }}" wire:navigate class="flex items-center gap-3 rounded-xl border border-line/70 bg-surface-2/40 p-2.5 transition hover:border-primary/30 hover:bg-primary-soft/30">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-warning-soft text-[9px] font-bold text-warning">{{ $permit->days_remaining === 0 ? 'H-0' : 'H-'.$permit->days_remaining }}</span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-[11.5px] font-semibold text-ink">{{ $permit->title }}</span>
                            <span class="block truncate text-[10px] text-muted">{{ $permit->category->name }}{{ $permit->pic ? ' · PIC '.$permit->pic->name : '' }}</span>
                        </span>
                        <span class="shrink-0 text-right text-[10px]">
                            <span class="block font-semibold text-ink">{{ $permit->expiryLabel() }}</span>
                            <span class="block text-warning">{{ $permit->remainingLabel() }}</span>
                        </span>
                    </a>
                @empty
                    <x-empty-state icon="check-circle" title="Tidak ada izin yang mendesak" description="Semua izin aktif berjarak lebih dari 30 hari dari kedaluwarsa." class="!py-6" />
                @endforelse
            </div>
        </section>

        <section class="card p-4 sm:p-5 xl:col-span-5" aria-labelledby="report-renewal-title">
            <h2 id="report-renewal-title" class="card-title">Perpanjangan terbaru</h2>
            <p class="mt-0.5 text-[11px] text-muted">{{ $renewalCount }} perpanjangan pada periode.</p>

            <div class="mt-3 space-y-2.5">
                @forelse ($recentRenewals as $renewal)
                    <div class="flex items-start gap-2.5">
                        <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-success-soft text-success"><x-icon name="refresh" :size="13" /></span>
                        <div class="min-w-0">
                            @if ($renewal->permit)
                                <a href="{{ route('permits.show', $renewal->permit) }}" wire:navigate class="block truncate text-[11.5px] font-semibold text-ink hover:text-primary">{{ $renewal->permit->title }}</a>
                            @else
                                <p class="text-[11.5px] font-semibold text-ink">Izin dihapus</p>
                            @endif
                            <p class="text-[10px] text-muted">
                                {{ $renewal->old_expires_at->translatedFormat('d M Y') }} &rarr; {{ $renewal->new_expires_at->translatedFormat('d M Y') }}
                                @if ($renewal->creator) &middot; {{ $renewal->creator->name }} @endif
                            </p>
                        </div>
                    </div>
                @empty
                    <x-empty-state icon="refresh" title="Belum ada perpanjangan" description="Perpanjangan yang dicatat pada periode ini akan tampil di sini." class="!py-6" />
                @endforelse
            </div>
        </section>
    </div>

    @if ($canSeeDeliveries && $deliverySummary)
        <section class="card p-4 sm:p-5" aria-labelledby="report-delivery-title">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <h2 id="report-delivery-title" class="card-title">Kinerja pengiriman reminder</h2>
                    <p class="mt-0.5 text-[11px] text-muted">{{ number_format($deliverySummary['total'], 0, ',', '.') }} pesan dibuat pada periode, {{ number_format($deliverySummary['failed'], 0, ',', '.') }} gagal.</p>
                </div>
                @if ($deliveryExportUrl)
                    <a href="{{ $deliveryExportUrl }}" class="inline-flex min-h-11 items-center text-[11px] font-semibold text-primary hover:underline">Ekspor detail</a>
                @endif
            </div>

            @if ($deliverySummary['total'] === 0)
                <p class="mt-3 rounded-2xl bg-surface-2 px-3.5 py-3 text-[11.5px] text-muted">Belum ada pengiriman pada periode dan filter ini.</p>
            @else
                @php
                    $deliveryTone = [
                        'sent' => 'bg-success-soft text-success',
                        'delivered' => 'bg-success-soft text-success',
                        'read' => 'bg-success-soft text-success',
                        'failed' => 'bg-danger-soft text-danger',
                        'pending' => 'bg-warning-soft text-warning',
                        'queued' => 'bg-info-soft text-info',
                        'cancelled' => 'bg-neutral-soft text-ink-2',
                    ];
                @endphp
                <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-7">
                    @foreach (\App\Enums\DeliveryStatus::cases() as $status)
                        @php $count = (int) ($deliverySummary['counts'][$status->value] ?? 0); @endphp
                        <div class="rounded-xl border border-line/80 p-2.5">
                            <p class="text-[9.5px] font-semibold text-muted">{{ $status->label() }}</p>
                            <p class="mt-0.5 text-[18px] font-bold tabular-nums {{ $count > 0 ? ($deliveryTone[$status->value] ?? 'text-ink') : 'text-muted' }}">{{ $count }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    @endif
</div>

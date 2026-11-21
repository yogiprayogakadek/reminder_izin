@php
    $toneClasses = [
        'success' => ['bg-success-soft text-success', 'text-success'],
        'warning' => ['bg-warning-soft text-warning', 'text-warning'],
        'danger' => ['bg-danger-soft text-danger', 'text-danger'],
        'info' => ['bg-info-soft text-info', 'text-info'],
        'neutral' => ['bg-neutral-soft text-ink-2', 'text-ink-2'],
    ];
    [$stateBadge, $stateText] = $toneClasses[$wahaState['tone']] ?? $toneClasses['neutral'];
@endphp

<div class="space-y-5">
    <x-page-header title="Ringkasan izin" description="Pantau izin yang paling dekat kedaluwarsa dan pastikan jalur reminder tetap sehat.">
        <x-slot name="actions">
            <a href="{{ route('permits.index') }}" wire:navigate class="btn-secondary">Lihat semua izin</a>
            @can('manage-permits')
                @if ($hasCategories)
                    <a href="{{ route('permits.create') }}" wire:navigate class="btn-primary">
                        <x-icon name="plus" :size="16" />
                        Tambah izin
                    </a>
                @else
                    @can('manage-categories')
                        <a href="{{ route('categories.index') }}" wire:navigate class="btn-primary">
                            <x-icon name="plus" :size="16" />
                            Buat kategori dulu
                        </a>
                    @endcan
                @endif
            @endcan
        </x-slot>
    </x-page-header>

    @if ($wahaWarning)
        <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-warning/30 bg-warning-soft px-4 py-3 text-warning" role="status">
            <x-icon name="alert" :size="18" />
            <p class="min-w-0 flex-1 text-[12px] font-semibold">Reminder WhatsApp belum tentu terkirim. Status sesi WAHA: {{ strtolower($wahaState['label']) }}.</p>
            @can('manage-app')
                <a href="{{ route('settings.waha') }}" wire:navigate class="text-[11.5px] font-bold underline underline-offset-4">Periksa koneksi</a>
            @endcan
        </div>
    @endif

    <section aria-label="Ringkasan angka" class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="card-flat p-4">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <p class="micro-label">Izin aktif</p>
                    <p class="stat-value mt-1">{{ number_format($stats['active'], 0, ',', '.') }}</p>
                </div>
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-success-soft text-success"><x-icon name="check-circle" :size="18" /></span>
            </div>
            <p class="mt-2 text-[10.5px] text-muted">Berlaku lebih dari 30 hari</p>
        </div>

        <div class="card-flat p-4">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <p class="micro-label">Segera berakhir</p>
                    <p class="stat-value mt-1">{{ number_format($stats['expiring'], 0, ',', '.') }}</p>
                </div>
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-warning-soft text-warning"><x-icon name="clock" :size="18" /></span>
            </div>
            <p class="mt-2 text-[10.5px] text-muted">Kedaluwarsa dalam 30 hari</p>
        </div>

        <div class="card-flat p-4">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <p class="micro-label">Kedaluwarsa</p>
                    <p class="stat-value mt-1">{{ number_format($stats['expired'], 0, ',', '.') }}</p>
                </div>
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-danger-soft text-danger"><x-icon name="alert" :size="18" /></span>
            </div>
            <p class="mt-2 text-[10.5px] text-muted">Belum diperpanjang atau diarsipkan</p>
        </div>

        <div class="card-flat p-4">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <p class="micro-label">Reminder bulan ini</p>
                    <p class="stat-value mt-1">{{ number_format($stats['reminders'], 0, ',', '.') }}</p>
                </div>
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-info-soft text-info"><x-icon name="send" :size="18" /></span>
            </div>
            <p class="mt-2 text-[10.5px] text-muted">Pesan terkirim bulan berjalan</p>
        </div>
    </section>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
        <section class="card p-4 sm:p-5 xl:col-span-8" aria-labelledby="action-title">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2">
                        <h2 id="action-title" class="card-title">Perlu tindakan</h2>
                        @if ($actionTotal > 0)
                            <span class="pill pill-warning">{{ $actionTotal }}</span>
                        @endif
                    </div>
                    <p class="mt-0.5 text-[11px] text-muted">Izin terdekat yang harus segera ditangani, diurutkan dari tanggal kedaluwarsa.</p>
                </div>
                <a href="{{ route('permits.index', ['status' => 'expired']) }}" wire:navigate class="inline-flex min-h-11 items-center text-[11.5px] font-semibold text-primary hover:underline">Buka daftar izin</a>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-4">
                <div>
                    <label for="filter-category" class="micro-label mb-1 block">Kategori</label>
                    <select id="filter-category" wire:model.live="categoryId" class="field field-sm">
                        <option value="">Semua kategori</option>
                        @foreach ($categoryOptions as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="filter-status" class="micro-label mb-1 block">Status</label>
                    <select id="filter-status" wire:model.live="status" class="field field-sm">
                        <option value="">Semua status</option>
                        <option value="expiring">Akan kedaluwarsa</option>
                        <option value="expired">Kedaluwarsa</option>
                        <option value="renewing">Sedang diperpanjang</option>
                    </select>
                </div>
                <div>
                    <label for="filter-pic" class="micro-label mb-1 block">PIC</label>
                    <select id="filter-pic" wire:model.live="picId" class="field field-sm">
                        <option value="">Semua PIC</option>
                        @foreach ($picOptions as $pic)
                            <option value="{{ $pic->id }}">{{ $pic->name }}</option>
                        @endforeach
                    </select>
                </div>
                @if ($categoryId !== '' || $status !== '' || $picId !== '')
                    <div class="flex items-end">
                        <button type="button" wire:click="resetFilters" class="btn-ghost w-full px-2 text-[11.5px]">Reset filter</button>
                    </div>
                @endif
            </div>

            <div class="mt-4 space-y-2" wire:loading.class="opacity-60" wire:target="categoryId,status,picId,resetFilters">
                @forelse ($actionPermits as $permit)
                    <a href="{{ route('permits.show', $permit) }}" wire:navigate class="group flex flex-col gap-3 rounded-2xl border border-line/80 bg-surface-2/50 p-3 transition hover:border-primary/30 hover:bg-primary-soft/30 sm:flex-row sm:items-center">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-surface text-[10px] font-bold text-primary ring-1 ring-line/80">
                            {{ $permit->days_remaining < 0 ? 'H+'.abs($permit->days_remaining) : ($permit->days_remaining === 0 ? 'H-0' : 'H-'.$permit->days_remaining) }}
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="flex flex-wrap items-center gap-2">
                                <span class="truncate text-[13px] font-bold text-ink group-hover:text-primary">{{ $permit->title }}</span>
                                <x-status-pill :status="$permit->status" />
                                @if ($permit->is_paused)
                                    <span class="pill pill-neutral">Dijeda</span>
                                @endif
                            </span>
                            <span class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[11px] text-muted">
                                <span class="inline-flex items-center gap-1 font-medium" style="color: {{ $permit->category->color }}">
                                    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                    {{ $permit->category->name }}
                                </span>
                                <span aria-hidden="true">&middot;</span>
                                <span class="truncate">{{ $permit->issuer }}</span>
                                @if ($permit->pic)
                                    <span aria-hidden="true">&middot;</span>
                                    <span class="truncate">PIC {{ $permit->pic->name }}</span>
                                @endif
                            </span>
                        </span>
                        <span class="flex shrink-0 items-center gap-3 sm:text-right">
                            <span>
                                <span class="block text-[11.5px] font-semibold text-ink">{{ $permit->expiryLabel() }}</span>
                                <span class="block text-[10.5px] font-medium {{ $permit->days_remaining < 0 ? 'text-danger' : 'text-warning' }}">{{ $permit->remainingLabel() }}</span>
                            </span>
                            <x-icon name="chevron-right" :size="16" class="text-muted transition group-hover:text-primary" />
                        </span>
                    </a>
                @empty
                    @if ($totalPermits === 0)
                        <x-empty-state
                            icon="file-check"
                            title="Belum ada izin yang dipantau"
                            description="{{ auth()->user()->can('manage-categories') ? 'Mulai dengan membuat kategori izin, lalu tambahkan izin perusahaan beserta tanggal kedaluwarsanya.' : 'Admin belum menambahkan kategori izin. Hubungi admin agar izin perusahaan dapat dicatat.' }}"
                        >
                            <x-slot name="action">
                                @if ($hasCategories)
                                    @can('manage-permits')
                                        <a href="{{ route('permits.create') }}" wire:navigate class="btn-primary">Tambah izin pertama</a>
                                    @endcan
                                @else
                                    @can('manage-categories')
                                        <a href="{{ route('categories.index') }}" wire:navigate class="btn-primary">Buat kategori dulu</a>
                                    @endcan
                                @endif
                            </x-slot>
                        </x-empty-state>
                    @else
                        <x-empty-state icon="check-circle" title="Tidak ada izin pada filter ini" description="Ubah kategori, status, atau PIC untuk melihat izin lain yang perlu ditindaklanjuti." />
                    @endif
                @endforelse
            </div>

            @if ($actionTotal > $actionPermits->count())
                <div class="mt-3 text-center">
                    <a href="{{ route('permits.index') }}" wire:navigate class="inline-flex min-h-11 items-center text-[11.5px] font-semibold text-primary hover:underline">Lihat {{ $actionTotal - $actionPermits->count() }} izin lainnya</a>
                </div>
            @endif
        </section>

        <div class="space-y-4 xl:col-span-4">
            <section class="card p-4 sm:p-5" aria-labelledby="waha-title">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 id="waha-title" class="card-title">Kesehatan WAHA</h2>
                        <p class="mt-0.5 text-[11px] text-muted">Jalur pengiriman WhatsApp</p>
                    </div>
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-primary-soft text-primary"><x-icon name="whatsapp" :size="18" /></span>
                </div>

                <div class="mt-4 rounded-2xl border border-line/80 bg-surface-2/60 p-3.5">
                    <div class="flex items-center justify-between gap-2">
                        <span class="inline-flex rounded-full px-2.5 py-1 text-[10.5px] font-bold {{ $stateBadge }}">{{ $wahaState['label'] }}</span>
                        @if ($waha->last_tested_at)
                            <span class="text-[10px] text-muted">{{ $waha->last_tested_at->locale('id')->diffForHumans() }}</span>
                        @endif
                    </div>
                    <p class="mt-2 text-[11.5px] leading-5 text-ink-2">{{ $wahaState['description'] }}</p>
                    @if ($waha->sender_number)
                        <p class="mt-2 text-[10.5px] text-muted">Nomor pengirim: <span class="font-semibold text-ink">{{ $waha->sender_number }}</span></p>
                    @endif
                </div>

                @if ($wahaMessage)
                    <div class="mt-3 rounded-xl px-3 py-2 text-[11.5px] font-medium {{ $wahaOk ? 'bg-success-soft text-success' : 'bg-danger-soft text-danger' }}" role="status">
                        {{ $wahaMessage }}
                    </div>
                @endif

                @can('manage-app')
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button type="button" wire:click="testWaha" class="btn-secondary flex-1" wire:loading.attr="disabled" wire:target="testWaha">
                            <x-icon name="refresh" :size="15" />
                            <span wire:loading.remove wire:target="testWaha">Uji koneksi</span>
                            <span wire:loading wire:target="testWaha">Menguji...</span>
                        </button>
                        <a href="{{ route('settings.waha') }}" wire:navigate class="btn-ghost px-3">Atur</a>
                    </div>
                @endcan

                <div class="mt-4 space-y-1 border-t border-line pt-3">
                    <div class="flex items-center justify-between py-1 text-[11px]">
                        <span class="text-muted">Aturan aktif</span>
                        <span class="font-bold text-ink">{{ $activeRules }}</span>
                    </div>
                    <div class="flex items-center justify-between py-1 text-[11px]">
                        <span class="text-muted">Kontak aktif</span>
                        <span class="font-bold text-ink">{{ $activeContacts }}</span>
                    </div>
                    <div class="flex items-center justify-between py-1 text-[11px]">
                        <span class="text-muted">Gagal 30 hari terakhir</span>
                        <span class="font-bold {{ $recentFailures > 0 ? 'text-danger' : 'text-ink' }}">{{ $recentFailures }}</span>
                    </div>
                </div>
            </section>

            <section class="card p-4 sm:p-5" aria-labelledby="quick-title">
                <h2 id="quick-title" class="section-title">Ringkasan cepat</h2>
                <div class="mt-3 space-y-2">
                    <a href="{{ route('permits.index', ['status' => 'renewing']) }}" wire:navigate class="flex min-h-11 items-center justify-between rounded-xl bg-info-soft/70 px-3 text-[11.5px] font-semibold text-info transition hover:bg-info-soft">
                        <span class="flex items-center gap-2"><x-icon name="refresh" :size="15" /> Sedang diperpanjang</span>
                        <span class="font-bold">{{ $stats['renewing'] }}</span>
                    </a>
                    <a href="{{ route('permits.index') }}" wire:navigate class="flex min-h-11 items-center justify-between rounded-xl bg-neutral-soft/80 px-3 text-[11.5px] font-semibold text-ink-2 transition hover:bg-neutral-soft">
                        <span class="flex items-center gap-2"><x-icon name="file-check" :size="15" /> Total izin tercatat</span>
                        <span class="font-bold">{{ $totalPermits }}</span>
                    </a>
                    @can('manage-permits')
                        <a href="{{ route('permits.index', ['status' => 'expired']) }}" wire:navigate class="flex min-h-11 items-center justify-between rounded-xl bg-danger-soft/60 px-3 text-[11.5px] font-semibold text-danger transition hover:bg-danger-soft">
                            <span class="flex items-center gap-2"><x-icon name="alert" :size="15" /> Perlu perpanjangan</span>
                            <span class="font-bold">{{ $stats['expired'] }}</span>
                        </a>
                    @endcan
                </div>
            </section>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
        <section class="card p-4 sm:p-5 xl:col-span-8" aria-labelledby="calendar-title">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <h2 id="calendar-title" class="card-title">Kalender kedaluwarsa</h2>
                    <p class="mt-0.5 text-[11px] text-muted">90 hari ke depan &middot; {{ $calendarStart->translatedFormat('d M Y') }} &ndash; {{ $windowEnd->translatedFormat('d M Y') }}</p>
                </div>
                <div class="flex items-center gap-2 text-[9.5px] text-muted">
                    <span class="h-2.5 w-2.5 rounded bg-neutral-soft"></span> Kosong
                    <span class="h-2.5 w-2.5 rounded bg-primary/30"></span> 1
                    <span class="h-2.5 w-2.5 rounded bg-primary"></span> Ramai
                </div>
            </div>

            <div class="mt-4 overflow-x-auto">
                <div class="grid min-w-[300px] grid-cols-[20px_repeat(13,minmax(0,1fr))] gap-1">
                    @foreach ($calendarRows as $weekday => $cells)
                        <span class="flex h-5 items-center text-[9px] font-medium text-muted">{{ ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'][$weekday] }}</span>
                        @foreach ($cells as $cell)
                            @php
                                $inWindow = $cell['inWindow'];
                                $style = '';
                                if ($inWindow && $cell['count'] > 0) {
                                    $strength = min(100, 35 + ($cell['count'] * 20));
                                    $style = "background-color: color-mix(in oklab, var(--color-primary) {$strength}%, var(--color-surface-2));";
                                }
                            @endphp
                            <span
                                class="h-5 rounded-[4px] {{ $inWindow ? ($cell['count'] > 0 ? '' : 'bg-neutral-soft') : 'bg-surface-2/40' }} {{ $cell['isToday'] ? 'ring-2 ring-primary ring-offset-1 ring-offset-surface' : '' }}"
                                style="{{ $style }}"
                                title="{{ $cell['date']->translatedFormat('d M Y') }}: {{ $cell['count'] }} izin kedaluwarsa"
                                aria-label="{{ $cell['date']->translatedFormat('d F Y') }}, {{ $cell['count'] }} izin kedaluwarsa"
                            ></span>
                        @endforeach
                    @endforeach
                </div>
            </div>

            @if ($stats['expiring'] + $stats['expired'] === 0)
                <p class="mt-3 text-[11px] text-muted">Belum ada izin yang jatuh tempo dalam 90 hari ke depan.</p>
            @endif
        </section>

        <section class="card p-4 sm:p-5 xl:col-span-4" aria-labelledby="activity-title">
            <div class="flex items-center justify-between gap-2">
                <h2 id="activity-title" class="card-title">Aktivitas terbaru</h2>
                @can('view-audit')
                    <a href="{{ route('audit.index') }}" wire:navigate class="inline-flex min-h-11 min-w-11 items-center justify-center text-[11px] font-semibold text-primary hover:underline">Semua</a>
                @endcan
            </div>
            <div class="mt-3 space-y-3">
                @forelse ($activities as $activity)
                    <div class="flex items-start gap-2.5">
                        <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary-soft text-primary">
                            <x-icon name="{{ str_contains($activity->action, 'renew') ? 'refresh' : (str_contains($activity->action, 'created') ? 'plus' : 'edit') }}" :size="13" />
                        </span>
                        <span class="min-w-0">
                            <span class="block text-[11.5px] font-semibold text-ink">{{ $activity->description }}</span>
                            <span class="block text-[10px] text-muted">{{ $activity->user?->name ?? 'Sistem' }} &middot; {{ $activity->created_at->locale('id')->diffForHumans() }}</span>
                        </span>
                    </div>
                @empty
                    <x-empty-state icon="clipboard" title="Belum ada aktivitas" description="Perubahan data izin akan tercatat di sini." class="!py-6" />
                @endforelse
            </div>
        </section>
    </div>

    <section class="card p-4 sm:p-5" aria-labelledby="distribution-title">
        <div class="flex flex-wrap items-start justify-between gap-2">
            <div>
                <h2 id="distribution-title" class="card-title">Sebaran izin per kategori</h2>
                <p class="mt-0.5 text-[11px] text-muted">Jumlah izin yang belum diarsipkan.</p>
            </div>
            <a href="{{ route('permits.index') }}" wire:navigate class="inline-flex min-h-11 items-center text-[11px] font-semibold text-primary hover:underline">Kelola izin</a>
        </div>

        @if ($categories->isEmpty())
            <p class="mt-4 text-[11.5px] text-muted">Belum ada kategori izin.</p>
        @else
            <div class="mt-4 grid gap-3 md:grid-cols-2">
                @foreach ($categories as $category)
                    <div>
                        <div class="flex items-center justify-between gap-3 text-[11.5px]">
                            <span class="flex min-w-0 items-center gap-2 font-semibold text-ink">
                                <span class="h-2 w-2 shrink-0 rounded-full" style="background-color: {{ $category->color }}"></span>
                                <span class="truncate">{{ $category->name }}</span>
                                @if (! $category->is_active)
                                    <span class="pill pill-neutral">Nonaktif</span>
                                @endif
                            </span>
                            <span class="font-bold text-ink">{{ $category->permit_count }}</span>
                        </div>
                        <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-neutral-soft" role="presentation">
                            <div class="h-full rounded-full" style="width: {{ max(4, round($category->permit_count / $maxCategory * 100)) }}%; background-color: {{ $category->color }}"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>

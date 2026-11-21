@php
    $statusTabs = [
        'all' => ['label' => 'Semua', 'count' => $counts['all']],
        'active' => ['label' => 'Aktif', 'count' => $counts['active']],
        'expiring' => ['label' => 'Akan berakhir', 'count' => $counts['expiring']],
        'expired' => ['label' => 'Kedaluwarsa', 'count' => $counts['expired']],
        'renewing' => ['label' => 'Diperpanjang', 'count' => $counts['renewing']],
        'archived' => ['label' => 'Arsip', 'count' => $counts['archived']],
    ];

    $exportQuery = array_filter([
        'q' => $search,
        'kategori' => $categoryId,
        'status' => $status,
        'pic' => $picId,
        'dari' => $from,
        'sampai' => $to,
        'urut' => $sort,
    ], fn ($value) => $value !== '' && $value !== 'all' && $value !== 'expires_asc');
@endphp

<div class="space-y-5">
    <x-page-header title="Izin perusahaan" description="Satu sumber data untuk masa berlaku izin, file dokumen, dan riwayat perpanjangan.">
        <x-slot name="actions">
            <a href="{{ route('reports.permits.export', $exportQuery) }}" class="btn-secondary">
                <x-icon name="download" :size="16" />
                Ekspor
            </a>
            @can('manage-permits')
                @if ($hasActiveCategories)
                    <a href="{{ route('permits.create') }}" wire:navigate class="btn-primary">
                        <x-icon name="plus" :size="16" />
                        Tambah izin
                    </a>
                @elseif (auth()->user()->isAdmin())
                    <a href="{{ route('categories.index') }}" wire:navigate class="btn-primary">
                        <x-icon name="plus" :size="16" />
                        Buat kategori dulu
                    </a>
                @endif
            @endcan
        </x-slot>
    </x-page-header>

    <x-flash />

    <div class="-mx-1 flex flex-wrap gap-1 px-1 pb-1 sm:flex-nowrap sm:overflow-x-auto" role="group" aria-label="Filter status izin">
        @foreach ($statusTabs as $key => $tab)
            <button
                type="button"
                wire:click="setStatus('{{ $key }}')"
                class="inline-flex min-h-11 shrink-0 items-center gap-1.5 rounded-full px-3.5 text-[12px] font-semibold transition {{ $status === $key ? 'bg-primary text-on-primary shadow-sm' : 'bg-surface/80 text-ink-2 ring-1 ring-line/80 hover:bg-primary-soft/60 hover:text-primary' }}"
                aria-pressed="{{ $status === $key ? 'true' : 'false' }}"
            >
                {{ $tab['label'] }}
                <span class="rounded-full px-1.5 text-[10px] {{ $status === $key ? 'bg-black/15' : 'bg-neutral-soft text-muted' }}">{{ $tab['count'] }}</span>
            </button>
        @endforeach
    </div>

    <div class="card p-4 sm:p-5">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-12">
            <div class="xl:col-span-4">
                <label for="permit-search" class="micro-label mb-1 block">Pencarian</label>
                <div class="relative">
                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted"><x-icon name="search" :size="15" /></span>
                    <input id="permit-search" type="search" wire:model.live.debounce.350ms="search" placeholder="Nama, nomor, penerbit, atau PIC..." class="field pl-9" />
                </div>
            </div>
            <div class="xl:col-span-2">
                <label for="permit-category" class="micro-label mb-1 block">Kategori</label>
                <select id="permit-category" wire:model.live="categoryId" class="field">
                    <option value="">Semua</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}{{ $category->is_active ? '' : ' (nonaktif)' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="xl:col-span-2">
                <label for="permit-pic" class="micro-label mb-1 block">PIC</label>
                <select id="permit-pic" wire:model.live="picId" class="field">
                    <option value="">Semua</option>
                    @foreach ($pics as $pic)
                        <option value="{{ $pic->id }}">{{ $pic->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="xl:col-span-2">
                <label for="permit-from" class="micro-label mb-1 block">Kedaluwarsa dari</label>
                <input id="permit-from" type="date" wire:model.live="from" class="field" />
            </div>
            <div class="xl:col-span-2">
                <label for="permit-to" class="micro-label mb-1 block">Sampai</label>
                <input id="permit-to" type="date" wire:model.live="to" class="field" />
            </div>
        </div>

        <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <label for="permit-sort" class="micro-label">Urutkan</label>
                <select id="permit-sort" wire:model.live="sort" class="field field-sm w-auto">
                    <option value="expires_asc">Kedaluwarsa terdekat</option>
                    <option value="expires_desc">Kedaluwarsa terjauh</option>
                    <option value="name">Nama izin</option>
                    <option value="updated">Terakhir diubah</option>
                </select>
            </div>
            @if ($hasFilters)
                <button type="button" wire:click="resetFilters" class="btn-ghost px-2 text-[11.5px]">Reset semua filter</button>
            @endif
        </div>
    </div>

    <div class="card overflow-hidden" wire:loading.class="opacity-60" wire:target="search,categoryId,picId,from,to,sort,setStatus,resetFilters">
        @if ($permits->isEmpty())
            <div class="p-5">
                <x-empty-state
                    icon="file-check"
                    :title="$counts['all'] === 0 ? 'Belum ada izin tercatat' : 'Tidak ada izin pada filter ini'"
                    :description="$counts['all'] === 0 ? (auth()->user()->can('manage-categories') ? 'Tambahkan izin perusahaan beserta tanggal kedaluwarsanya. File dokumen bersifat opsional.' : 'Admin belum menambahkan kategori izin. Hubungi admin agar izin perusahaan dapat dicatat.') : 'Ubah kata kunci atau filter untuk menemukan izin yang dicari.'"
                >
                    <x-slot name="action">
                        @if ($counts['all'] === 0 && $hasActiveCategories)
                            @can('manage-permits')
                                <a href="{{ route('permits.create') }}" wire:navigate class="btn-primary">Tambah izin pertama</a>
                            @endcan
                        @elseif ($counts['all'] === 0)
                            @can('manage-categories')
                                <a href="{{ route('categories.index') }}" wire:navigate class="btn-primary">Buat kategori dulu</a>
                            @endcan
                        @elseif ($hasFilters)
                            <button type="button" wire:click="resetFilters" class="btn-secondary">Reset filter</button>
                        @endif
                    </x-slot>
                </x-empty-state>
            </div>
        @else
            <div class="hidden overflow-x-auto lg:block">
                <table class="w-full min-w-[900px] border-collapse text-left">
                    <thead class="bg-surface-2/70">
                        <tr>
                            <th class="table-head">Izin</th>
                            <th class="table-head">Kategori</th>
                            <th class="table-head">Penerbit</th>
                            <th class="table-head">Kedaluwarsa</th>
                            <th class="table-head">Status</th>
                            <th class="table-head">PIC</th>
                            <th class="table-head text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($permits as $permit)
                            <tr class="border-t border-line/70 transition hover:bg-surface-2/40">
                                <td class="table-cell">
                                    <a href="{{ route('permits.show', $permit) }}" wire:navigate class="group block min-w-0">
                                        <span class="block max-w-[280px] truncate text-[12.5px] font-bold text-ink group-hover:text-primary">{{ $permit->title }}</span>
                                        <span class="mt-0.5 flex flex-wrap items-center gap-1.5 text-[10.5px] text-muted">
                                            <span>{{ $permit->number ?: 'Tanpa nomor' }}</span>
                                            @if ($permit->is_paused)
                                                <span class="pill pill-neutral">Dijeda</span>
                                            @endif
                                        </span>
                                    </a>
                                </td>
                                <td class="table-cell">
                                    <span class="inline-flex items-center gap-1.5">
                                        <span class="h-1.5 w-1.5 rounded-full" style="background-color: {{ $permit->category->color }}"></span>
                                        {{ $permit->category->name }}
                                    </span>
                                </td>
                                <td class="table-cell">{{ $permit->issuer }}</td>
                                <td class="table-cell">
                                    <span class="block font-semibold">{{ $permit->expiryLabel() }}</span>
                                    <span class="block text-[10.5px] {{ $permit->days_remaining < 0 ? 'text-danger' : ($permit->days_remaining <= 30 ? 'text-warning' : 'text-muted') }}">{{ $permit->remainingLabel() }}</span>
                                </td>
                                <td class="table-cell"><x-status-pill :status="$permit->status" /></td>
                                <td class="table-cell">{{ $permit->pic?->name ?? '-' }}</td>
                                <td class="table-cell">
                                    <div class="flex justify-end gap-1">
                                        <a href="{{ route('permits.show', $permit) }}" wire:navigate class="icon-button" aria-label="Lihat {{ $permit->title }}" title="Lihat">
                                            <x-icon name="eye" :size="15" />
                                        </a>
                                        @can('manage-permits')
                                            <a href="{{ route('permits.edit', $permit) }}" wire:navigate class="icon-button" aria-label="Ubah {{ $permit->title }}" title="Ubah">
                                                <x-icon name="edit" :size="15" />
                                            </a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-line/70 lg:hidden">
                @foreach ($permits as $permit)
                    <a href="{{ route('permits.show', $permit) }}" wire:navigate class="block p-4 transition hover:bg-surface-2/40">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-[13px] font-bold text-ink">{{ $permit->title }}</p>
                                <p class="mt-0.5 text-[10.5px] text-muted">{{ $permit->category->name }} &middot; {{ $permit->number ?: 'Tanpa nomor' }}</p>
                            </div>
                            <x-status-pill :status="$permit->status" />
                        </div>
                        <div class="mt-2 flex flex-wrap items-center justify-between gap-2 text-[11px]">
                            <span class="text-muted">{{ $permit->issuer }} @if ($permit->pic) &middot; PIC {{ $permit->pic->name }} @endif</span>
                            <span class="text-right">
                                <span class="block font-semibold text-ink">{{ $permit->expiryLabel() }}</span>
                                <span class="{{ $permit->days_remaining <= 30 ? 'text-warning' : 'text-muted' }}">{{ $permit->remainingLabel() }}</span>
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="border-t border-line px-4 py-3">{{ $permits->links() }}</div>
        @endif
    </div>
</div>

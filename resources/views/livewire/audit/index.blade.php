<div class="space-y-5">
    <x-page-header title="Audit log" description="Jejak perubahan data dan tindakan penting. Log yang lebih lama dari masa retensi dihapus otomatis oleh scheduler." />

    <x-settings-tabs />

    <section class="grid grid-cols-2 gap-3 xl:grid-cols-4" aria-label="Ringkasan audit">
        <div class="card-flat p-4">
            <p class="micro-label">Total entri</p>
            <p class="stat-value mt-1">{{ number_format($summary['total'], 0, ',', '.') }}</p>
        </div>
        <div class="card-flat p-4">
            <p class="micro-label">Hari ini</p>
            <p class="stat-value mt-1">{{ number_format($summary['today'], 0, ',', '.') }}</p>
        </div>
        <div class="card-flat p-4">
            <p class="micro-label">Pengguna tercatat</p>
            <p class="stat-value mt-1">{{ number_format($summary['actors'], 0, ',', '.') }}</p>
        </div>
        <div class="card-flat p-4">
            <p class="micro-label">Retensi</p>
            <p class="stat-value mt-1">{{ $summary['retention'] }}</p>
            <p class="mt-1 text-[10.5px] text-muted">hari</p>
        </div>
    </section>

    <div class="card p-4 sm:p-5">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-12">
            <div class="xl:col-span-3">
                <label for="audit-search" class="micro-label mb-1 block">Pencarian</label>
                <div class="relative">
                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted"><x-icon name="search" :size="15" /></span>
                    <input id="audit-search" type="search" wire:model.live.debounce.350ms="search" placeholder="Deskripsi, pengguna, IP..." class="field pl-9" />
                </div>
            </div>
            <div class="xl:col-span-3">
                <label for="audit-action" class="micro-label mb-1 block">Tindakan</label>
                <select id="audit-action" wire:model.live="action" class="field">
                    <option value="">Semua tindakan</option>
                    @foreach ($actions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="xl:col-span-2">
                <label for="audit-user" class="micro-label mb-1 block">Pengguna</label>
                <select id="audit-user" wire:model.live="userId" class="field">
                    <option value="">Semua</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="xl:col-span-2">
                <label for="audit-from" class="micro-label mb-1 block">Dari tanggal</label>
                <input id="audit-from" type="date" wire:model.live="from" class="field" />
            </div>
            <div class="xl:col-span-2">
                <label for="audit-to" class="micro-label mb-1 block">Sampai tanggal</label>
                <input id="audit-to" type="date" wire:model.live="to" class="field" />
            </div>
        </div>
        <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
            <p class="text-[11px] text-muted">{{ $logs->total() }} entri cocok dengan filter.</p>
            <button type="button" wire:click="resetFilters" class="btn-ghost px-2 text-[11.5px]">Reset filter</button>
        </div>
    </div>

    <div class="card overflow-hidden" wire:loading.class="opacity-60" wire:target="search,action,userId,from,to,resetFilters">
        @if ($logs->isEmpty())
            <div class="p-5">
                <x-empty-state icon="clipboard" title="Tidak ada entri audit" description="Ubah filter atau lakukan perubahan data untuk mulai mencatat aktivitas." />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[840px] border-collapse text-left">
                    <thead class="bg-surface-2/70">
                        <tr>
                            <th class="table-head">Waktu</th>
                            <th class="table-head">Pengguna</th>
                            <th class="table-head">Tindakan</th>
                            <th class="table-head">Deskripsi</th>
                            <th class="table-head">IP</th>
                            <th class="table-head text-right">Rincian</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr class="border-t border-line/70 align-top transition hover:bg-surface-2/40 {{ $expandedId === $log->id ? 'bg-primary-soft/20' : '' }}">
                                <td class="table-cell whitespace-nowrap">
                                    <span class="block text-[11px] font-semibold text-ink">{{ $log->created_at->translatedFormat('d M Y') }}</span>
                                    <span class="block text-[10px] text-muted">{{ $log->created_at->format('H:i:s') }}</span>
                                </td>
                                <td class="table-cell">
                                    <span class="block text-[11.5px] font-semibold text-ink">{{ $log->user?->name ?? 'Sistem' }}</span>
                                    @if ($log->user)
                                        <span class="block max-w-[160px] truncate text-[10px] text-muted">{{ $log->user->email }}</span>
                                    @endif
                                </td>
                                <td class="table-cell">
                                    <span class="pill pill-neutral">{{ $log->actionLabel() }}</span>
                                </td>
                                <td class="table-cell">
                                    <span class="block max-w-[320px] text-[11.5px] leading-5 text-ink-2">{{ $log->description }}</span>
                                </td>
                                <td class="table-cell font-mono text-[10.5px] text-muted">{{ $log->ip_address ?? '-' }}</td>
                                <td class="table-cell">
                                    <div class="flex justify-end">
                                        <button type="button" wire:click="toggleDetails({{ $log->id }})" class="btn-ghost h-11 min-h-11 px-2.5 text-[10.5px]" aria-expanded="{{ $expandedId === $log->id ? 'true' : 'false' }}">
                                            {{ $expandedId === $log->id ? 'Tutup' : 'Lihat' }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @if ($expanded && $expanded->id === $log->id)
                                <tr class="border-t border-line/70 bg-surface-2/50">
                                    <td colspan="6" class="px-4 py-4 sm:px-5">
                                        <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                                            <div class="rounded-xl border border-line bg-surface p-3">
                                                <p class="text-[10px] font-bold uppercase tracking-wide text-muted">Sebelum</p>
                                                @if (count($oldValues) === 0)
                                                    <p class="mt-2 text-[11px] text-muted">Tidak ada nilai sebelumnya.</p>
                                                @else
                                                    <dl class="mt-2 space-y-1.5">
                                                        @foreach ($oldValues as $item)
                                                            <div class="flex items-start justify-between gap-3">
                                                                <dt class="text-[10.5px] text-muted">{{ $item['label'] }}</dt>
                                                                <dd class="max-w-[65%] break-words text-right text-[10.5px] font-semibold text-ink">{{ $item['value'] }}</dd>
                                                            </div>
                                                        @endforeach
                                                    </dl>
                                                @endif
                                            </div>
                                            <div class="rounded-xl border border-line bg-surface p-3">
                                                <p class="text-[10px] font-bold uppercase tracking-wide text-muted">Sesudah</p>
                                                @if (count($newValues) === 0)
                                                    <p class="mt-2 text-[11px] text-muted">Tidak ada rincian nilai baru.</p>
                                                @else
                                                    <dl class="mt-2 space-y-1.5">
                                                        @foreach ($newValues as $item)
                                                            <div class="flex items-start justify-between gap-3">
                                                                <dt class="text-[10.5px] text-muted">{{ $item['label'] }}</dt>
                                                                <dd class="max-w-[65%] break-words text-right text-[10.5px] font-semibold text-ink">{{ $item['value'] }}</dd>
                                                            </div>
                                                        @endforeach
                                                    </dl>
                                                @endif
                                            </div>
                                        </div>
                                        @if ($log->user_agent)
                                            <p class="mt-2 truncate text-[9.5px] text-muted" title="{{ $log->user_agent }}">Perangkat: {{ $log->user_agent }}</p>
                                        @endif
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-line px-4 py-3">{{ $logs->links() }}</div>
        @endif
    </div>
</div>

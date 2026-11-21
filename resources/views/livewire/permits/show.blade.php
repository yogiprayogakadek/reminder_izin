@php
    $days = $permit->days_remaining;
    $dayTone = $days < 0 ? 'text-danger' : ($days <= 30 ? 'text-warning' : 'text-success');
    $remaining = $permit->remainingLabel();
@endphp

<div class="space-y-5">
    <x-page-header :title="$permit->title" :description="'Izin ' . $permit->category->name . ' · ' . ($permit->number ?: 'Tanpa nomor izin')">
        <x-slot name="actions">
            <a href="{{ route('permits.index') }}" wire:navigate class="btn-secondary">
                <x-icon name="arrow-left" :size="16" />
                Daftar izin
            </a>
            @can('manage-permits')
                @if ($permit->archived_at === null)
                    <a href="{{ route('permits.edit', $permit) }}" wire:navigate class="btn-secondary">
                        <x-icon name="edit" :size="16" />
                        Ubah
                    </a>
                    @if ($permit->renewal_started_at)
                        <a href="{{ route('permits.renew', $permit) }}" wire:navigate class="btn-primary">
                            <x-icon name="refresh" :size="16" />
                            Lanjutkan perpanjangan
                        </a>
                    @else
                        <button type="button" wire:click="startRenewal" class="btn-primary">
                            <x-icon name="refresh" :size="16" />
                            Perpanjang izin
                        </button>
                    @endif
                @endif
            @endcan
        </x-slot>
    </x-page-header>

    <x-flash />

    <section class="card overflow-hidden">
        <div class="grid gap-0 md:grid-cols-[1fr_auto]">
            <div class="flex flex-wrap items-center gap-4 p-4 sm:p-5">
                <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-primary-soft text-primary">
                    <x-icon name="calendar" :size="24" />
                </span>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-status-pill :status="$permit->status" />
                        @if ($permit->is_paused)
                            <span class="pill pill-warning">Reminder dijeda</span>
                        @endif
                        @if ($permit->reminder_enabled)
                            <span class="pill pill-neutral">Reminder aktif</span>
                        @else
                            <span class="pill pill-neutral">Reminder nonaktif</span>
                        @endif
                    </div>
                    <p class="mt-2 text-[12px] text-muted">Kedaluwarsa</p>
                    <p class="text-[21px] font-bold tracking-[-0.02em] text-ink">{{ $permit->expires_at->locale('id')->translatedFormat('l, d F Y') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-5 border-t border-line/70 bg-surface-2/50 px-5 py-4 md:border-l md:border-t-0">
                <div>
                    <p class="micro-label">Sisa waktu</p>
                    <p class="text-[22px] font-bold tracking-[-0.02em] {{ $dayTone }}">{{ $remaining }}</p>
                </div>
                <div class="h-10 w-px bg-line"></div>
                <div>
                    <p class="micro-label">Penerbit</p>
                    <p class="max-w-[190px] truncate text-[12px] font-semibold text-ink">{{ $permit->issuer }}</p>
                    <p class="text-[10.5px] text-muted">{{ $permit->pic?->name ? 'PIC '.$permit->pic->name : 'PIC belum ditentukan' }}</p>
                </div>
            </div>
        </div>
        @if ($permit->is_paused)
            <div class="flex flex-wrap items-center gap-2 border-t border-warning/20 bg-warning-soft px-4 py-2.5 text-[11.5px] font-semibold text-warning sm:px-5">
                <x-icon name="pause" :size="14" />
                Reminder dijeda sampai {{ $permit->reminder_paused_until->translatedFormat('d M Y') }}.
                @can('manage-permits')
                    <button type="button" wire:click="resumeReminders" class="ml-auto inline-flex min-h-11 items-center underline underline-offset-4">Aktifkan sekarang</button>
                @endcan
            </div>
        @elseif ($permit->renewal_started_at)
            <div class="flex flex-wrap items-center gap-2 border-t border-info/20 bg-info-soft px-4 py-2.5 text-[11.5px] font-semibold text-info sm:px-5">
                <x-icon name="refresh" :size="14" />
                Sedang diperpanjang sejak {{ $permit->renewal_started_at->translatedFormat('d M Y') }}. Reminder otomatis dijeda sampai perpanjangan selesai.
            </div>
        @endif
    </section>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
        <div class="space-y-4 xl:col-span-8">
            <section class="card p-4 sm:p-5" aria-labelledby="permit-detail-title">
                <h2 id="permit-detail-title" class="card-title">Informasi izin</h2>

                <dl class="mt-4 grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                    <div>
                        <dt class="micro-label">Kategori</dt>
                        <dd class="mt-1 flex items-center gap-2 text-[13px] font-semibold text-ink">
                            <span class="h-2 w-2 rounded-full" style="background-color: {{ $permit->category->color }}"></span>
                            {{ $permit->category->name }}
                            @if (! $permit->category->is_active)
                                <span class="pill pill-neutral">Nonaktif</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="micro-label">Nomor izin</dt>
                        <dd class="mt-1 text-[13px] font-semibold text-ink">{{ $permit->number ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt class="micro-label">Tanggal terbit</dt>
                        <dd class="mt-1 text-[13px] font-semibold text-ink">{{ $permit->issued_at?->translatedFormat('d F Y') ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="micro-label">Tanggal kedaluwarsa</dt>
                        <dd class="mt-1 text-[13px] font-semibold text-ink">{{ $permit->expires_at->translatedFormat('d F Y') }}</dd>
                    </div>
                    <div>
                        <dt class="micro-label">Penerbit</dt>
                        <dd class="mt-1 text-[13px] font-semibold text-ink">{{ $permit->issuer }}</dd>
                    </div>
                    <div>
                        <dt class="micro-label">PIC internal</dt>
                        <dd class="mt-1 text-[13px] font-semibold text-ink">{{ $permit->pic?->name ?? 'Belum ditentukan' }}</dd>
                    </div>
                    <div>
                        <dt class="micro-label">Lokasi atau unit</dt>
                        <dd class="mt-1 text-[13px] font-semibold text-ink">{{ $permit->location ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt class="micro-label">Terakhir diperbarui</dt>
                        <dd class="mt-1 text-[13px] font-semibold text-ink">{{ $permit->updated_at->translatedFormat('d M Y, H:i') }}</dd>
                    </div>
                </dl>

                @if ($permit->notes)
                    <div class="mt-5 rounded-2xl bg-surface-2 p-3.5">
                        <p class="micro-label">Catatan</p>
                        <p class="mt-1 whitespace-pre-line text-[12.5px] leading-6 text-ink-2">{{ $permit->notes }}</p>
                    </div>
                @endif
            </section>

            <section class="card p-4 sm:p-5" aria-labelledby="permit-files-title">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <h2 id="permit-files-title" class="card-title">Dokumen</h2>
                        <p class="mt-0.5 text-[11px] text-muted">File privat. Unduhan memakai tautan bertanda tangan yang berlaku 30 menit.</p>
                    </div>
                    @can('manage-permits')
                        <a href="{{ route('permits.edit', $permit) }}" wire:navigate class="inline-flex min-h-11 items-center text-[11.5px] font-semibold text-primary hover:underline">Kelola dokumen</a>
                    @endcan
                </div>

                @if ($permit->currentFiles->isEmpty())
                    <p class="mt-3 rounded-2xl bg-surface-2 px-3.5 py-3 text-[11.5px] text-muted">Belum ada dokumen aktif untuk izin ini.</p>
                @else
                    <div class="mt-3 space-y-2">
                        @foreach ($permit->currentFiles as $file)
                            <div class="flex items-center gap-3 rounded-2xl border border-line/80 bg-surface-2/60 p-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary-soft text-primary">
                                    <x-icon :name="$file->isImage() ? 'eye' : 'file'" :size="18" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-[12.5px] font-semibold text-ink">{{ $file->original_name }}</p>
                                    <p class="text-[10.5px] text-muted">{{ $file->humanSize() }} &middot; diunggah {{ $file->created_at->translatedFormat('d M Y') }}</p>
                                </div>
                                <a href="{{ URL::temporarySignedRoute('permits.files.download', now()->addMinutes(30), $file) }}" class="btn-secondary px-3 text-[11px]">
                                    <x-icon name="download" :size="14" />
                                    Unduh
                                </a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="card p-4 sm:p-5" aria-labelledby="permit-renewals-title">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <h2 id="permit-renewals-title" class="card-title">Riwayat perpanjangan</h2>
                        <p class="mt-0.5 text-[11px] text-muted">{{ $permit->renewals->count() }} perpanjangan tercatat.</p>
                    </div>
                </div>

                @if ($permit->renewals->isEmpty())
                    <p class="mt-3 rounded-2xl bg-surface-2 px-3.5 py-3 text-[11.5px] text-muted">Belum ada perpanjangan. Riwayat akan tercatat saat Anda menyelesaikan proses perpanjangan.</p>
                @else
                    <ol class="mt-4 space-y-0">
                        @foreach ($permit->renewals as $renewal)
                            <li class="relative border-l border-line pb-5 pl-5 last:pb-0">
                                <span class="absolute -left-[5px] top-0.5 h-2.5 w-2.5 rounded-full bg-primary ring-4 ring-surface"></span>
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <div>
                                        <p class="text-[12.5px] font-bold text-ink">{{ $renewal->new_expires_at->translatedFormat('d M Y') }}</p>
                                        <p class="text-[10.5px] text-muted">
                                            Sebelumnya {{ $renewal->old_expires_at->translatedFormat('d M Y') }}
                                            &middot; dicatat {{ $renewal->created_at->translatedFormat('d M Y') }}
                                            @if ($renewal->creator) &middot; {{ $renewal->creator->name }} @endif
                                        </p>
                                    </div>
                                    @if ($renewal->new_issued_at)
                                        <span class="pill pill-neutral">Terbit {{ $renewal->new_issued_at->translatedFormat('d M Y') }}</span>
                                    @endif
                                </div>
                                @if ($renewal->notes)
                                    <p class="mt-1.5 whitespace-pre-line text-[11.5px] leading-5 text-ink-2">{{ $renewal->notes }}</p>
                                @endif
                                @if ($renewal->files->isNotEmpty())
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @foreach ($renewal->files as $file)
                                            <a href="{{ URL::temporarySignedRoute('permits.files.download', now()->addMinutes(30), $file) }}" class="inline-flex min-h-11 items-center gap-1.5 rounded-full bg-primary-soft px-2.5 text-[10.5px] font-semibold text-primary hover:underline">
                                                <x-icon name="file" :size="12" />
                                                {{ $file->original_name }}
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>
        </div>

        <div class="space-y-4 xl:col-span-4">
            @can('manage-permits')
                <section class="card p-4 sm:p-5" aria-labelledby="permit-actions-title">
                    <h2 id="permit-actions-title" class="card-title">Tindakan</h2>

                    <div class="mt-3 space-y-2">
                        @if ($permit->archived_at === null)
                            <a href="{{ route('permits.edit', $permit) }}" wire:navigate class="btn-secondary w-full justify-start">
                                <x-icon name="edit" :size="16" />
                                Ubah data izin
                            </a>
                            @if ($permit->renewal_started_at)
                                <a href="{{ route('permits.renew', $permit) }}" wire:navigate class="btn-primary w-full justify-start">
                                    <x-icon name="refresh" :size="16" />
                                    Lanjutkan perpanjangan
                                </a>
                                <button type="button" wire:click="cancelRenewal" wire:confirm="Batalkan proses perpanjangan? Reminder akan aktif kembali." class="btn-ghost w-full justify-start text-warning">
                                    <x-icon name="x" :size="16" />
                                    Batalkan perpanjangan
                                </button>
                            @else
                                <button type="button" wire:click="startRenewal" class="btn-primary w-full justify-start">
                                    <x-icon name="refresh" :size="16" />
                                    Mulai perpanjangan
                                </button>
                            @endif
                            <button type="button" wire:click="archive" wire:confirm="Arsipkan izin ini? Reminder otomatis berhenti dan izin dipindahkan dari daftar aktif." class="btn-ghost w-full justify-start">
                                <x-icon name="archive" :size="16" />
                                Arsipkan izin
                            </button>
                        @else
                            <div class="rounded-xl bg-neutral-soft px-3 py-2.5 text-[11.5px] text-ink-2">
                                Izin ini ada di arsip. Keluarkan dari arsip untuk mengaktifkan kembali pengingat dan perpanjangan.
                            </div>
                            <button type="button" wire:click="unarchive" class="btn-primary w-full justify-start">
                                <x-icon name="play" :size="16" />
                                Keluarkan dari arsip
                            </button>
                        @endif
                    </div>

                    @can('delete-permits')
                        <div class="mt-4 border-t border-line pt-3">
                            <button type="button" wire:click="delete" wire:confirm="Hapus izin ini secara permanen dari daftar? Riwayat pengiriman juga tidak akan dapat diakses." class="btn-ghost w-full justify-start text-danger hover:bg-danger-soft/70">
                                <x-icon name="trash" :size="16" />
                                Hapus izin
                            </button>
                        </div>
                    @endcan
                </section>
            @endcan

            <section class="card p-4 sm:p-5" aria-labelledby="permit-reminder-title">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <h2 id="permit-reminder-title" class="card-title">Reminder WhatsApp</h2>
                        <p class="mt-0.5 text-[11px] text-muted">Penerima dan jadwal pengingat.</p>
                    </div>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary-soft text-primary"><x-icon name="whatsapp" :size="18" /></span>
                </div>

                <div class="mt-3 space-y-2">
                    @forelse ($effectiveContacts as $contact)
                        <div class="flex items-center gap-2.5 rounded-xl bg-surface-2 px-3 py-2">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-soft text-[10px] font-bold text-primary">{{ $contact->initials() }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-[11.5px] font-semibold text-ink">{{ $contact->name }}</p>
                                <p class="truncate font-mono text-[10px] text-muted">{{ $contact->displayPhone() }}</p>
                            </div>
                            @if ($contact->user_id)
                                <span class="pill pill-neutral">Akun</span>
                            @endif
                        </div>
                    @empty
                        <p class="rounded-xl bg-surface-2 px-3 py-2.5 text-[11px] text-muted">Belum ada penerima aktif pada izin maupun kategorinya.</p>
                    @endforelse
                </div>

                @if ($effectiveContacts->isNotEmpty())
                    <p class="mt-2 text-[10px] text-muted">Penerima khusus izin didahulukan. Jika kosong, penerima default kategori yang dipakai.</p>
                @endif

                @can('manage-permits')
                    @if ($permit->archived_at === null && $effectiveContacts->isNotEmpty())
                        <button
                            type="button"
                            wire:click="sendManualReminder"
                            wire:confirm="Kirim reminder sekarang ke {{ $effectiveContacts->count() }} kontak? Pastikan informasi izin sudah benar."
                            class="btn-secondary mt-3 w-full"
                            wire:loading.attr="disabled"
                            wire:target="sendManualReminder"
                        >
                            <x-icon name="send" :size="15" />
                            <span wire:loading.remove wire:target="sendManualReminder">Kirim reminder sekarang</span>
                            <span wire:loading wire:target="sendManualReminder">Mengantrekan...</span>
                        </button>
                        @if (! $wahaReady)
                            <p class="mt-2 rounded-xl bg-warning-soft px-3 py-2 text-[10.5px] font-medium text-warning">
                                WAHA belum aktif atau belum dikonfigurasi. Pengiriman akan gagal sampai admin memperbaikinya.
                            </p>
                        @endif
                    @endif
                @endcan

                @if ($permit->archived_at === null)
                    <div class="mt-4 border-t border-line pt-3">
                        <p class="section-title text-[12px]">Jeda reminder</p>
                        @if ($permit->is_paused)
                            <button type="button" wire:click="resumeReminders" class="btn-secondary mt-2 w-full text-[11.5px]">Aktifkan lebih awal</button>
                        @else
                            <div class="mt-2 flex gap-2">
                                <div class="min-w-0 flex-1">
                                    <label for="pause-until" class="sr-only">Jeda reminder sampai tanggal</label>
                                    <input id="pause-until" type="date" wire:model="pauseUntil" class="field field-sm" />
                                </div>
                                @can('manage-permits')
                                    <button type="button" wire:click="pauseReminders" class="btn-secondary px-3 text-[11px]">Jeda</button>
                                @endcan
                            </div>
                            @error('pauseUntil') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                        @endif
                    </div>
                @endif
            </section>

            <section class="card p-4 sm:p-5" aria-labelledby="permit-schedule-title">
                <h2 id="permit-schedule-title" class="card-title">Jadwal pengingat</h2>
                <p class="mt-0.5 text-[11px] text-muted">Aturan yang berlaku untuk kategori ini.</p>

                @if ($schedule->isEmpty())
                    <p class="mt-3 rounded-xl bg-surface-2 px-3 py-2.5 text-[11px] text-muted">Belum ada aturan reminder aktif.</p>
                @else
                    <ul class="mt-3 space-y-1.5">
                        @foreach ($schedule as $item)
                            <li class="flex items-center justify-between gap-2 rounded-xl px-2.5 py-1.5 {{ $item['past'] ? 'bg-surface-2/60' : 'bg-surface-2' }}">
                                <div class="flex items-center gap-2">
                                    <span class="flex h-6 min-w-8 items-center justify-center rounded-md px-1 text-[9.5px] font-bold {{ $item['past'] ? 'bg-neutral-soft text-muted' : 'bg-primary-soft text-primary' }}">{{ $item['rule']->label() }}</span>
                                    <span class="text-[11px] font-medium {{ $item['past'] ? 'text-muted' : 'text-ink' }}">{{ $item['date']->translatedFormat('d M Y') }}</span>
                                </div>
                                <span class="text-[10px] font-semibold {{ $item['sent'] > 0 ? 'text-success' : 'text-muted' }}">
                                    {{ $item['sent'] > 0 ? $item['sent'].' terkirim' : 'Belum terkirim' }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <div class="mt-3 border-t border-line pt-3">
                    <div class="flex items-center justify-between">
                        <h3 class="section-title text-[12px]">Pengiriman terakhir</h3>
                        <a href="{{ route('notifications.deliveries', ['q' => $permit->title]) }}" wire:navigate class="inline-flex min-h-11 items-center text-[10.5px] font-semibold text-primary hover:underline">Semua</a>
                    </div>
                    <div class="mt-2 space-y-1.5">
                        @forelse ($deliveries as $delivery)
                            <div class="flex items-center justify-between gap-2 rounded-lg px-1 py-1">
                                <div class="min-w-0">
                                    <p class="truncate text-[10.5px] font-semibold text-ink">{{ $delivery->contact?->name ?? $delivery->recipient_phone ?? 'Kontak' }}</p>
                                    <p class="text-[9.5px] text-muted">{{ $delivery->created_at->translatedFormat('d M, H:i') }}{{ $delivery->rule ? ' · '.$delivery->rule->label() : ' · manual' }}</p>
                                </div>
                                <x-delivery-pill :status="$delivery->status" />
                            </div>
                        @empty
                            <p class="text-[10.5px] text-muted">Belum ada pengiriman untuk izin ini.</p>
                        @endforelse
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

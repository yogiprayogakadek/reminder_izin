<div class="space-y-5">
    <x-page-header title="Kontak notifikasi" description="Penerima reminder WhatsApp. Kontak tidak perlu memiliki akun aplikasi.">
        <x-slot name="actions">
            <button type="button" wire:click="openCreate" class="btn-primary">
                <x-icon name="plus" :size="16" />
                Tambah kontak
            </button>
        </x-slot>
    </x-page-header>

    <x-notification-tabs />

    <x-flash />

    <div class="card overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-3 sm:px-5">
            <div>
                <h2 class="section-title">Daftar kontak</h2>
                <p class="mt-0.5 text-[11px] text-muted">{{ $contacts->total() }} kontak pada filter ini</p>
            </div>
            <div class="flex w-full flex-wrap gap-2 sm:w-auto">
                <div class="relative min-w-0 flex-1 sm:w-60">
                    <label for="contact-search" class="sr-only">Cari kontak</label>
                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted"><x-icon name="search" :size="15" /></span>
                    <input id="contact-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Cari nama, nomor, label..." class="field field-sm pl-9" />
                </div>
                <div>
                    <label for="contact-filter" class="sr-only">Status kontak</label>
                    <select id="contact-filter" wire:model.live="filter" class="field field-sm w-full sm:w-36">
                        <option value="active">Aktif</option>
                        <option value="inactive">Nonaktif</option>
                        <option value="all">Semua</option>
                    </select>
                </div>
            </div>
        </div>

        @if ($contacts->isEmpty())
            <div class="p-5">
                <x-empty-state icon="users" title="Belum ada kontak" description="Tambahkan orang yang perlu menerima reminder, misalnya HRD, legal, atau manajemen. Nomor akan dinormalkan ke format 62.">
                    <x-slot name="action">
                        <button type="button" wire:click="openCreate" class="btn-primary">Tambah kontak pertama</button>
                    </x-slot>
                </x-empty-state>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[780px] border-collapse text-left">
                    <thead class="bg-surface-2/70">
                        <tr>
                            <th class="table-head">Kontak</th>
                            <th class="table-head">Nomor WhatsApp</th>
                            <th class="table-head">Label</th>
                            <th class="table-head">Akun terkait</th>
                            <th class="table-head">Dipakai</th>
                            <th class="table-head">Status</th>
                            <th class="table-head text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($contacts as $contact)
                            <tr class="border-t border-line/70 transition hover:bg-surface-2/40">
                                <td class="table-cell">
                                    <div class="flex items-center gap-2.5">
                                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-soft text-[10px] font-bold text-primary">{{ $contact->initials() }}</span>
                                        <div class="min-w-0">
                                            <p class="truncate text-[12.5px] font-bold text-ink">{{ $contact->name }}</p>
                                            <p class="max-w-[220px] truncate text-[10.5px] text-muted">{{ $contact->notes ?: 'Tidak ada catatan' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="table-cell font-mono text-[11.5px]">{{ $contact->displayPhone() }}</td>
                                <td class="table-cell">{{ $contact->label ?: '-' }}</td>
                                <td class="table-cell">{{ $contact->user?->name ?? '-' }}</td>
                                <td class="table-cell">
                                    <span class="text-[11px] text-muted">{{ $contact->categories_count }} kategori &middot; {{ $contact->permits_count }} izin</span>
                                </td>
                                <td class="table-cell">
                                    <span class="pill {{ $contact->is_active ? 'pill-success' : 'pill-neutral' }}">{{ $contact->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                </td>
                                <td class="table-cell">
                                    <div class="flex justify-end gap-1">
                                        @if ($wahaConfigured && $contact->is_active)
                                            <button type="button" wire:click="sendTest({{ $contact->id }})" class="btn-ghost px-2 text-[11px]" wire:loading.attr="disabled" wire:target="sendTest({{ $contact->id }})" title="Kirim pesan tes">
                                                <x-icon name="message-square" :size="14" />
                                                Tes
                                            </button>
                                        @endif
                                        <button type="button" wire:click="openEdit({{ $contact->id }})" class="icon-button" aria-label="Ubah {{ $contact->name }}" title="Ubah">
                                            <x-icon name="edit" :size="15" />
                                        </button>
                                        @can('manage-contacts')
                                            <button type="button" wire:click="toggleActive({{ $contact->id }})" class="icon-button" aria-label="{{ $contact->is_active ? 'Nonaktifkan' : 'Aktifkan' }} {{ $contact->name }}" title="{{ $contact->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                                <x-icon name="{{ $contact->is_active ? 'pause' : 'play' }}" :size="15" />
                                            </button>
                                        @endcan
                                        @can('delete-contacts')
                                            @if ($contact->categories_count === 0 && $contact->permits_count === 0 && $contact->deliveries_count === 0)
                                                <button type="button" wire:click="delete({{ $contact->id }})" wire:confirm="Hapus kontak {{ $contact->name }}? Tindakan ini tidak dapat dibatalkan." class="icon-button text-danger" aria-label="Hapus {{ $contact->name }}" title="Hapus">
                                                    <x-icon name="trash" :size="15" />
                                                </button>
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($contacts->hasPages())
                <div class="border-t border-line px-4 py-3">{{ $contacts->links() }}</div>
            @endif
        @endif
    </div>

    @if ($showForm)
        <div class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-labelledby="contact-form-title" x-on:keydown.escape.window="$wire.closeForm()">
            <div class="absolute inset-0 bg-ink/45 backdrop-blur-sm" wire:click="closeForm" aria-hidden="true"></div>
            <div class="absolute inset-y-0 right-0 flex w-full max-w-lg flex-col overflow-y-auto bg-surface p-5 shadow-float sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 id="contact-form-title" class="text-[18px] font-bold tracking-[-0.02em] text-ink">{{ $editingId ? 'Ubah kontak' : 'Tambah kontak' }}</h2>
                        <p class="mt-0.5 text-[11.5px] text-muted">Nomor 08xx otomatis diubah menjadi 628xx.</p>
                    </div>
                    <button type="button" wire:click="closeForm" class="icon-button" aria-label="Tutup formulir"><x-icon name="x" :size="18" /></button>
                </div>

                <form wire:submit="save" class="mt-5 space-y-4">
                    <div>
                        <label for="contact-name" class="field-label">Nama <span class="text-danger">*</span></label>
                        <input id="contact-name" type="text" wire:model.blur="name" required placeholder="Nama penerima" class="field" data-autofocus />
                        @error('name') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="contact-phone" class="field-label">Nomor WhatsApp <span class="text-danger">*</span></label>
                        <input id="contact-phone" type="tel" wire:model.blur="phone" required placeholder="0812 3456 7890" class="field" inputmode="tel" />
                        <p class="field-hint">Gunakan nomor aktif WhatsApp. Format 08xx atau +62 8xx diterima.</p>
                        @error('phone') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label for="contact-label" class="field-label">Label / kelompok</label>
                            <input id="contact-label" type="text" wire:model.blur="label" list="contact-labels" placeholder="HRD" class="field" />
                            <datalist id="contact-labels">
                                <option value="HRD"></option>
                                <option value="Legal"></option>
                                <option value="Manajemen"></option>
                                <option value="Keuangan"></option>
                                <option value="Operasional"></option>
                            </datalist>
                            @error('label') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="contact-user" class="field-label">Tautkan ke akun</label>
                            <select id="contact-user" wire:model="userId" class="field">
                                <option value="">Tidak ditautkan</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                            <p class="field-hint">Akun menerima notifikasi dalam aplikasi.</p>
                            @error('userId') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="contact-notes" class="field-label">Catatan</label>
                        <textarea id="contact-notes" wire:model.blur="notes" rows="3" maxlength="1000" placeholder="Catatan internal tentang kontak ini" class="field py-2.5"></textarea>
                        @error('notes') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                    </div>

                    <label class="flex min-h-11 cursor-pointer items-center gap-2.5 rounded-xl bg-surface-2 px-3 text-[12px] font-semibold text-ink">
                        <input type="checkbox" wire:model="isActive" class="h-4 w-4 rounded border-line-strong text-primary focus:ring-primary/30" />
                        Kontak aktif dan dapat menerima reminder
                    </label>

                    <div class="flex flex-col-reverse gap-2 border-t border-line pt-4 sm:flex-row sm:justify-end">
                        <button type="button" wire:click="closeForm" class="btn-secondary">Batal</button>
                        <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">
                            <span wire:loading.remove wire:target="save">Simpan kontak</span>
                            <span wire:loading wire:target="save">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>

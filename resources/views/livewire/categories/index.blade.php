<div class="space-y-5">
    <x-page-header title="Kategori izin" description="Kategori menentukan masa berlaku default, PIC, dan penerima reminder. Perubahan default tidak mengubah izin yang sudah ada.">
        <x-slot name="actions">
            <button type="button" wire:click="openCreate" class="btn-primary">
                <x-icon name="plus" :size="16" />
                Tambah kategori
            </button>
        </x-slot>
    </x-page-header>

    <x-flash />

    <div class="card overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-3 sm:px-5">
            <div>
                <h2 class="section-title">Daftar kategori</h2>
                <p class="mt-0.5 text-[11px] text-muted">{{ $categories->count() }} kategori tercatat</p>
            </div>
            <div class="relative w-full sm:w-64">
                <label for="category-search" class="sr-only">Cari kategori</label>
                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted"><x-icon name="search" :size="15" /></span>
                <input id="category-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Cari kategori..." class="field field-sm pl-9" />
            </div>
        </div>

        @if ($categories->isEmpty())
            <div class="p-5">
                <x-empty-state icon="tags" title="Belum ada kategori" description="Buat kategori pertama, misalnya Reklame, Lingkungan, atau Izin Usaha. Kategori wajib ada sebelum izin dapat dicatat.">
                    <x-slot name="action">
                        <button type="button" wire:click="openCreate" class="btn-primary">Buat kategori pertama</button>
                    </x-slot>
                </x-empty-state>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] border-collapse text-left">
                    <thead class="bg-surface-2/70">
                        <tr>
                            <th class="table-head">Kategori</th>
                            <th class="table-head">Masa berlaku</th>
                            <th class="table-head">PIC default</th>
                            <th class="table-head">Penerima</th>
                            <th class="table-head">Izin</th>
                            <th class="table-head">Status</th>
                            <th class="table-head text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($categories as $category)
                            <tr class="border-t border-line/70 transition hover:bg-surface-2/40">
                                <td class="table-cell">
                                    <div class="flex items-center gap-2.5">
                                        <span class="h-8 w-1 rounded-full" style="background-color: {{ $category->color }}"></span>
                                        <div class="min-w-0">
                                            <p class="truncate text-[12.5px] font-bold text-ink">{{ $category->name }}</p>
                                            <p class="max-w-[260px] truncate text-[10.5px] text-muted">{{ $category->description ?: 'Tanpa deskripsi' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="table-cell">{{ $category->default_validity_months ? $category->default_validity_months.' bulan' : '-' }}</td>
                                <td class="table-cell">{{ $category->defaultPic?->name ?? '-' }}</td>
                                <td class="table-cell">{{ $category->recipients_count }} kontak</td>
                                <td class="table-cell">{{ $category->permits_count }}</td>
                                <td class="table-cell">
                                    <span class="pill {{ $category->is_active ? 'pill-success' : 'pill-neutral' }}">{{ $category->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                </td>
                                <td class="table-cell">
                                    <div class="flex justify-end gap-1">
                                        <button type="button" wire:click="openEdit({{ $category->id }})" class="icon-button" aria-label="Ubah {{ $category->name }}" title="Ubah">
                                            <x-icon name="edit" :size="15" />
                                        </button>
                                        <button type="button" wire:click="toggleActive({{ $category->id }})" class="icon-button" aria-label="{{ $category->is_active ? 'Nonaktifkan' : 'Aktifkan' }} {{ $category->name }}" title="{{ $category->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                            <x-icon name="{{ $category->is_active ? 'pause' : 'play' }}" :size="15" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @if ($showForm)
        <div class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-labelledby="category-form-title" x-on:keydown.escape.window="$wire.closeForm()">
            <div class="absolute inset-0 bg-ink/45 backdrop-blur-sm" wire:click="closeForm" aria-hidden="true"></div>
            <div class="absolute inset-y-0 right-0 flex w-full max-w-lg flex-col overflow-y-auto bg-surface p-5 shadow-float sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 id="category-form-title" class="text-[18px] font-bold tracking-[-0.02em] text-ink">{{ $editingId ? 'Ubah kategori' : 'Tambah kategori' }}</h2>
                        <p class="mt-0.5 text-[11.5px] text-muted">Nilai default membantu pengisian izin baru.</p>
                    </div>
                    <button type="button" wire:click="closeForm" class="icon-button" aria-label="Tutup formulir">
                        <x-icon name="x" :size="18" />
                    </button>
                </div>

                <form wire:submit="save" class="mt-5 space-y-4">
                    <div>
                        <label for="category-name" class="field-label">Nama kategori <span class="text-danger">*</span></label>
                        <input id="category-name" type="text" wire:model.blur="name" required placeholder="Contoh: Reklame" class="field" data-autofocus />
                        @error('name') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="category-description" class="field-label">Deskripsi singkat</label>
                        <textarea id="category-description" wire:model.blur="description" rows="3" maxlength="500" placeholder="Jenis izin yang termasuk dalam kategori ini" class="field py-2.5"></textarea>
                        @error('description') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="category-validity" class="field-label">Masa berlaku (bulan)</label>
                            <input id="category-validity" type="number" wire:model.blur="defaultValidityMonths" min="1" max="120" placeholder="12" class="field" />
                            @error('defaultValidityMonths') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="category-color" class="field-label">Warna penanda</label>
                            <div class="flex items-center gap-2">
                                <input id="category-color" type="color" wire:model="color" class="h-11 w-14 cursor-pointer rounded-control border border-line-strong bg-surface p-1" />
                                <span class="text-[11px] font-mono text-muted">{{ strtoupper($color) }}</span>
                            </div>
                            @error('color') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="category-pic" class="field-label">PIC default</label>
                        <select id="category-pic" wire:model="defaultPicId" class="field">
                            <option value="">Tidak ada default</option>
                            @foreach ($pics as $pic)
                                <option value="{{ $pic->id }}">{{ $pic->name }}</option>
                            @endforeach
                        </select>
                        @error('defaultPicId') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <span class="field-label">Penerima default</span>
                        @if ($contacts->isEmpty())
                            <p class="rounded-xl bg-surface-2 px-3 py-2.5 text-[11px] text-muted">Belum ada kontak aktif. Tambahkan kontak di menu Notifikasi.</p>
                        @else
                            <div class="grid max-h-44 gap-1 overflow-y-auto rounded-xl border border-line p-2 sm:grid-cols-2">
                                @foreach ($contacts as $contact)
                                    <label class="flex min-h-11 cursor-pointer items-center gap-2 rounded-lg px-2 text-[11.5px] font-medium text-ink-2 hover:bg-primary-soft/50">
                                        <input type="checkbox" wire:model="recipientIds" value="{{ $contact->id }}" class="h-4 w-4 rounded border-line-strong text-primary focus:ring-primary/30" />
                                        <span class="min-w-0 truncate">{{ $contact->name }}<span class="block text-[9.5px] text-muted">{{ $contact->label ?: 'Tanpa label' }}</span></span>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                        @error('recipientIds.*') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                    </div>

                    <label class="flex min-h-11 cursor-pointer items-center gap-2.5 rounded-xl bg-surface-2 px-3 text-[12px] font-semibold text-ink">
                        <input type="checkbox" wire:model="isActive" class="h-4 w-4 rounded border-line-strong text-primary focus:ring-primary/30" />
                        Kategori aktif dan dapat dipilih pada izin baru
                    </label>

                    <div class="flex flex-col-reverse gap-2 border-t border-line pt-4 sm:flex-row sm:justify-end">
                        <button type="button" wire:click="closeForm" class="btn-secondary">Batal</button>
                        <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">
                            <span wire:loading.remove wire:target="save">Simpan kategori</span>
                            <span wire:loading wire:target="save">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>

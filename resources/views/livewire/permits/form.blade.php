@php
    $inputLabel = $editing ? 'Ubah izin' : 'Tambah izin';
@endphp

<div class="space-y-5">
    <x-page-header :title="$inputLabel" :description="$editing ? 'Perbarui data izin. Perubahan tanggal kedaluwarsa memulai siklus reminder baru.' : 'Catat izin perusahaan dan tanggal kedaluwarsanya. File dokumen bersifat opsional.'">
        <x-slot name="actions">
            <a href="{{ $editing ? route('permits.show', $permit) : route('permits.index') }}" wire:navigate class="btn-secondary">
                <x-icon name="arrow-left" :size="16" />
                Kembali
            </a>
        </x-slot>
    </x-page-header>

    <form wire:submit="save" class="grid grid-cols-1 gap-4 xl:grid-cols-12">
        <div class="space-y-4 xl:col-span-8">
            <section class="card p-4 sm:p-5" aria-labelledby="permit-info-title">
                <h2 id="permit-info-title" class="card-title">Informasi izin</h2>
                <p class="mt-0.5 text-[11px] text-muted">Field bertanda <span class="text-danger">*</span> wajib diisi.</p>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="permit-category" class="field-label">Kategori <span class="text-danger">*</span></label>
                        <select id="permit-category" wire:model.live="categoryId" class="field" required>
                            <option value="">Pilih kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}{{ $category->is_active ? '' : ' (nonaktif)' }}</option>
                            @endforeach
                        </select>
                        @error('categoryId') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                        <div class="mt-1.5 flex flex-wrap items-center gap-2">
                            @if ($selectedCategory?->default_validity_months)
                                <span class="text-[10.5px] text-muted">Masa berlaku default: {{ $selectedCategory->default_validity_months }} bulan</span>
                            @endif
                            @if ($selectedCategory)
                                <button type="button" wire:click="applyCategoryDefaults" class="inline-flex min-h-11 items-center text-[10.5px] font-semibold text-primary hover:underline">Terapkan default kategori</button>
                            @endif
                        </div>
                    </div>

                    <div>
                        <label for="permit-title" class="field-label">Nama izin <span class="text-danger">*</span></label>
                        <input id="permit-title" type="text" wire:model.blur="title" required maxlength="180" placeholder="Contoh: Izin Reklame Toko Pusat" class="field" />
                        @error('title') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="permit-number" class="field-label">Nomor izin</label>
                        <input id="permit-number" type="text" wire:model.blur="number" maxlength="100" placeholder="Nomor dokumen" class="field" />
                        @error('number') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="permit-issuer" class="field-label">Penerbit <span class="text-danger">*</span></label>
                        <input id="permit-issuer" type="text" wire:model.blur="issuer" required maxlength="150" placeholder="Instansi atau lembaga penerbit" class="field" />
                        @error('issuer') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="permit-issued" class="field-label">Tanggal terbit</label>
                        <input id="permit-issued" type="date" wire:model.blur="issuedAt" class="field" />
                        @error('issuedAt') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="permit-expires" class="field-label">Tanggal kedaluwarsa <span class="text-danger">*</span></label>
                        <input id="permit-expires" type="date" wire:model.blur="expiresAt" required class="field" />
                        @error('expiresAt') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="permit-pic" class="field-label">PIC internal</label>
                        <select id="permit-pic" wire:model="picId" class="field">
                            <option value="">Belum ditentukan</option>
                            @foreach ($pics as $pic)
                                <option value="{{ $pic->id }}">{{ $pic->name }}{{ $pic->is_active ? '' : ' (nonaktif)' }}</option>
                            @endforeach
                        </select>
                        @error('picId') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="permit-location" class="field-label">Lokasi atau unit</label>
                        <input id="permit-location" type="text" wire:model.blur="location" maxlength="150" placeholder="Contoh: Cabang Samarinda" class="field" />
                        @error('location') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="permit-notes" class="field-label">Catatan</label>
                        <textarea id="permit-notes" wire:model.blur="notes" rows="3" maxlength="2000" placeholder="Informasi tambahan, syarat perpanjangan, atau kontak instansi" class="field py-2.5"></textarea>
                        @error('notes') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            <section class="card p-4 sm:p-5" aria-labelledby="permit-recipients-title">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <h2 id="permit-recipients-title" class="card-title">Penerima reminder</h2>
                        <p class="mt-0.5 text-[11px] text-muted">Pilih penerima khusus untuk izin ini. Jika kosong, penerima default kategori yang dipakai.</p>
                    </div>
                    @if ($selectedCategory)
                        <button type="button" wire:click="applyCategoryDefaults" class="inline-flex min-h-11 items-center text-[11px] font-semibold text-primary hover:underline">Gunakan penerima default</button>
                    @endif
                </div>

                @if ($contacts->isEmpty())
                    <div class="mt-3 rounded-xl bg-surface-2 px-3 py-2.5 text-[11.5px] text-muted">
                        Belum ada kontak aktif. <a href="{{ route('contacts.index') }}" wire:navigate class="link inline-flex min-h-11 items-center">Tambahkan kontak</a> agar reminder dapat dikirim.
                    </div>
                @else
                    <div class="mt-3 grid max-h-52 gap-1 overflow-y-auto rounded-xl border border-line p-2 sm:grid-cols-2">
                        @foreach ($contacts as $contact)
                            <label class="flex min-h-11 cursor-pointer items-center gap-2 rounded-lg px-2 text-[11.5px] font-medium text-ink-2 hover:bg-primary-soft/50">
                                <input type="checkbox" wire:model="recipientIds" value="{{ $contact->id }}" class="h-4 w-4 rounded border-line-strong text-primary focus:ring-primary/30" />
                                <span class="min-w-0">
                                    <span class="block truncate">{{ $contact->name }}</span>
                                    <span class="block truncate text-[9.5px] text-muted">{{ $contact->label ?: 'Tanpa label' }}{{ $contact->is_active ? '' : ' · nonaktif' }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endif
                @error('recipientIds.*') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
            </section>
        </div>

        <div class="space-y-4 xl:col-span-4">
            <section class="card p-4 sm:p-5" aria-labelledby="permit-reminder-title">
                <h2 id="permit-reminder-title" class="card-title">Pengaturan reminder</h2>

                <label class="mt-3 flex cursor-pointer items-start gap-3 rounded-xl bg-surface-2 p-3">
                    <input type="checkbox" wire:model="reminderEnabled" class="mt-0.5 h-4 w-4 rounded border-line-strong text-primary focus:ring-primary/30" />
                    <span>
                        <span class="block text-[12px] font-semibold text-ink">Aktifkan reminder otomatis</span>
                        <span class="mt-0.5 block text-[10.5px] leading-4 text-muted">Reminder mengikuti aturan global dan penerima izin ini.</span>
                    </span>
                </label>

                <div class="mt-3">
                    <label for="permit-paused" class="field-label">Jeda reminder sampai</label>
                    <input id="permit-paused" type="date" wire:model.blur="reminderPausedUntil" class="field" />
                    <p class="field-hint">Kosongkan jika tidak dijeda. Reminder juga otomatis berhenti saat izin ditandai sedang diperpanjang.</p>
                    @error('reminderPausedUntil') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                </div>
            </section>

            <section class="card p-4 sm:p-5" aria-labelledby="permit-file-title">
                <h2 id="permit-file-title" class="card-title">Dokumen izin</h2>
                <p class="mt-0.5 text-[11px] text-muted">Opsional. PDF, JPG, atau PNG maksimal {{ $fileMaxMb }} MB.</p>

                @if ($editing && $permit->currentFiles->isNotEmpty())
                    <div class="mt-3 space-y-2">
                        @foreach ($permit->currentFiles as $file)
                            <div class="flex items-center gap-2 rounded-xl border border-line bg-surface-2 px-3 py-2">
                                <x-icon name="file" :size="16" class="text-primary" />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-[11.5px] font-semibold text-ink">{{ $file->original_name }}</p>
                                    <p class="text-[10px] text-muted">{{ $file->humanSize() }}</p>
                                </div>
                                <a href="{{ URL::temporarySignedRoute('permits.files.download', now()->addMinutes(30), $file) }}" class="inline-flex min-h-11 items-center text-[10.5px] font-semibold text-primary hover:underline">Unduh</a>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="mt-3">
                    <label for="permit-document" class="field-label">{{ $editing ? 'Ganti dokumen' : 'Unggah dokumen' }}</label>
                    <input
                        id="permit-document"
                        type="file"
                        wire:model="document"
                        accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                        class="block w-full cursor-pointer rounded-control border border-dashed border-line-strong bg-surface-2/60 p-3 text-[11px] text-muted file:mr-3 file:rounded-full file:border-0 file:bg-primary-soft file:px-3 file:py-1.5 file:text-[11px] file:font-semibold file:text-primary"
                    />
                    <div wire:loading wire:target="document" class="mt-1.5 text-[10.5px] font-medium text-primary">Mengunggah file...</div>
                    @error('document') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                </div>

                @if ($editing && $permit->currentFiles->isNotEmpty())
                    <label class="mt-3 flex min-h-11 cursor-pointer items-center gap-2 text-[11px] font-medium text-danger">
                        <input type="checkbox" wire:model="removeExistingFile" class="h-4 w-4 rounded border-line-strong text-danger focus:ring-danger/30" />
                        Hapus dokumen saat ini saat menyimpan
                    </label>
                    @error('removeExistingFile') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                @endif

                <p class="mt-3 text-[10px] leading-4 text-muted">File disimpan privat dan hanya dapat diunduh pengguna yang masuk.</p>
            </section>

            <div class="card p-4 sm:p-5">
                <button type="submit" class="btn-primary w-full" wire:loading.attr="disabled" wire:target="save,document">
                    <span wire:loading.remove wire:target="save">Simpan izin</span>
                    <span wire:loading wire:target="save">Menyimpan...</span>
                </button>
                <a href="{{ $editing ? route('permits.show', $permit) : route('permits.index') }}" wire:navigate class="btn-ghost mt-2 w-full">Batal</a>
            </div>
        </div>
    </form>
</div>

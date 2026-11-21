<div class="space-y-5">
    <x-page-header title="Perpanjang izin" :description="'Perbarui masa berlaku ' . $permit->title . '. Reminder untuk siklus lama otomatis dihentikan.'">
        <x-slot name="actions">
            <a href="{{ route('permits.show', $permit) }}" wire:navigate class="btn-secondary">
                <x-icon name="arrow-left" :size="16" />
                Kembali ke detail
            </a>
        </x-slot>
    </x-page-header>

    <x-flash />

    <form wire:submit="save" class="grid grid-cols-1 gap-4 xl:grid-cols-12">
        <div class="space-y-4 xl:col-span-8">
            <section class="card p-4 sm:p-5" aria-labelledby="renewal-form-title">
                <h2 id="renewal-form-title" class="card-title">Data perpanjangan</h2>
                <p class="mt-0.5 text-[11px] text-muted">Tanggal kedaluwarsa baru harus setelah {{ $permit->expires_at->translatedFormat('d F Y') }}.</p>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="renewal-issued" class="field-label">Tanggal terbit baru</label>
                        <input id="renewal-issued" type="date" wire:model.blur="newIssuedAt" class="field" />
                        @error('newIssuedAt') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="renewal-expires" class="field-label">Tanggal kedaluwarsa baru <span class="text-danger">*</span></label>
                        <input id="renewal-expires" type="date" wire:model.live.blur="newExpiresAt" required class="field" />
                        @error('newExpiresAt') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="renewal-notes" class="field-label">Catatan perpanjangan</label>
                        <textarea id="renewal-notes" wire:model.blur="notes" rows="3" maxlength="2000" placeholder="Nomor izin baru, syarat, atau catatan proses perpanjangan" class="field py-2.5"></textarea>
                        @error('notes') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            <section class="card p-4 sm:p-5" aria-labelledby="renewal-file-title">
                <h2 id="renewal-file-title" class="card-title">Dokumen perpanjangan</h2>
                <p class="mt-0.5 text-[11px] text-muted">Opsional. PDF, JPG, atau PNG maksimal {{ $fileMaxMb }} MB.</p>

                @if ($permit->currentFiles->isNotEmpty())
                    <div class="mt-3 rounded-xl border border-line bg-surface-2/60 px-3 py-2.5 text-[11px] leading-5 text-muted">
                        {{ $permit->currentFiles->count() }} dokumen aktif akan dipindahkan ke riwayat perpanjangan saat disimpan.
                        @if ($document)
                            Dokumen baru yang diunggah akan menjadi dokumen aktif.
                        @else
                            Unggah dokumen baru agar izin tetap memiliki dokumen aktif.
                        @endif
                    </div>
                @endif

                <div class="mt-3">
                    <label for="renewal-document" class="field-label">Unggah dokumen baru</label>
                    <input
                        id="renewal-document"
                        type="file"
                        wire:model="document"
                        accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                        class="block w-full cursor-pointer rounded-control border border-dashed border-line-strong bg-surface-2/60 p-3 text-[11px] text-muted file:mr-3 file:rounded-full file:border-0 file:bg-primary-soft file:px-3 file:py-1.5 file:text-[11px] file:font-semibold file:text-primary"
                    />
                    <div wire:loading wire:target="document" class="mt-1.5 text-[10.5px] font-medium text-primary">Mengunggah file...</div>
                    @error('document') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                </div>
            </section>
        </div>

        <div class="space-y-4 xl:col-span-4">
            <section class="card p-4 sm:p-5" aria-labelledby="renewal-summary-title">
                <h2 id="renewal-summary-title" class="card-title">Ringkasan</h2>

                <dl class="mt-3 space-y-3">
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-[11px] text-muted">Kedaluwarsa saat ini</dt>
                        <dd class="text-right text-[12px] font-semibold text-ink">{{ $permit->expires_at->translatedFormat('d M Y') }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-[11px] text-muted">Kedaluwarsa baru</dt>
                        <dd class="text-right text-[12px] font-bold text-primary">{{ $newExpiresAt ? \Carbon\CarbonImmutable::parse($newExpiresAt)->translatedFormat('d M Y') : '-' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-[11px] text-muted">Masa aktif baru</dt>
                        <dd class="text-right text-[11.5px] font-semibold {{ $previewTone }}">{{ $previewLabel ?? 'Pilih tanggal' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3 border-t border-line pt-3">
                        <dt class="text-[11px] text-muted">Kategori</dt>
                        <dd class="text-right text-[11.5px] font-semibold text-ink">{{ $permit->category->name }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-[11px] text-muted">PIC</dt>
                        <dd class="text-right text-[11.5px] font-semibold text-ink">{{ $permit->pic?->name ?? '-' }}</dd>
                    </div>
                </dl>

                <div class="mt-4 rounded-xl bg-info-soft px-3 py-2.5 text-[10.5px] leading-4 text-info">
                    Setelah disimpan, status izin dihitung ulang dan siklus reminder baru dimulai dari tanggal kedaluwarsa baru.
                </div>
            </section>

            <div class="card p-4 sm:p-5">
                <button type="submit" class="btn-primary w-full" wire:loading.attr="disabled" wire:target="save,document">
                    <span wire:loading.remove wire:target="save">Simpan perpanjangan</span>
                    <span wire:loading wire:target="save">Menyimpan...</span>
                </button>
                <a href="{{ route('permits.show', $permit) }}" wire:navigate class="btn-ghost mt-2 w-full">Batal</a>
                <button type="button" wire:click="cancel" wire:confirm="Batalkan proses perpanjangan? Reminder akan aktif kembali tanpa mengubah tanggal." class="btn-ghost mt-1 w-full text-[11.5px] text-warning">
                    Batalkan proses perpanjangan
                </button>
            </div>
        </div>
    </form>
</div>

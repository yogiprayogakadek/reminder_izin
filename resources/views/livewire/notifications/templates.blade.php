<div class="space-y-5">
    <x-page-header title="Template pesan" description="Susun isi pesan WhatsApp. Placeholder diisi otomatis dengan data izin saat reminder dibuat.">
        <x-slot name="actions">
            <button type="button" wire:click="openCreate" class="btn-primary">
                <x-icon name="plus" :size="16" />
                Tambah template
            </button>
        </x-slot>
    </x-page-header>

    <x-notification-tabs />

    <x-flash />

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
        <div class="space-y-4 xl:col-span-7">
            <section class="card overflow-hidden" aria-labelledby="template-list-title">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line px-4 py-3.5 sm:px-5">
                    <div>
                        <h2 id="template-list-title" class="section-title">Daftar template</h2>
                        <p class="mt-0.5 text-[11px] text-muted">{{ $activeCount }} template aktif. Template kategori diprioritaskan dari template global.</p>
                    </div>
                </div>

                @if ($templates->isEmpty())
                    <div class="p-5">
                        <x-empty-state icon="message-square" title="Belum ada template" description="Template menentukan isi pesan WhatsApp. Buat satu template standar sebelum reminder dijalankan.">
                            <x-slot name="action">
                                <button type="button" wire:click="openCreate" class="btn-primary">Buat template pertama</button>
                            </x-slot>
                        </x-empty-state>
                    </div>
                @else
                    <div class="divide-y divide-line/70">
                        @foreach ($templates as $template)
                            <article class="p-4 transition hover:bg-surface-2/30 sm:px-5">
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="text-[13px] font-bold text-ink">{{ $template->name }}</h3>
                                            <span class="pill {{ $template->is_active ? 'pill-success' : 'pill-neutral' }}">{{ $template->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                        </div>
                                        <p class="mt-0.5 text-[10.5px] text-muted">
                                            {{ $template->category?->name ?? 'Global, semua kategori' }}
                                            &middot; diperbarui {{ $template->updated_at->translatedFormat('d M Y') }}
                                        </p>
                                    </div>
                                    <div class="flex gap-1">
                                        <button type="button" wire:click="toggleActive({{ $template->id }})" class="icon-button" aria-label="{{ $template->is_active ? 'Nonaktifkan' : 'Aktifkan' }} {{ $template->name }}" title="{{ $template->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                            <x-icon name="{{ $template->is_active ? 'pause' : 'play' }}" :size="15" />
                                        </button>
                                        <button type="button" wire:click="openEdit({{ $template->id }})" class="icon-button" aria-label="Ubah {{ $template->name }}" title="Ubah">
                                            <x-icon name="edit" :size="15" />
                                        </button>
                                        <button type="button" wire:click="delete({{ $template->id }})" wire:confirm="Hapus template {{ $template->name }}? Riwayat pengiriman tetap menyimpan isi pesan yang sudah dikirim." class="icon-button text-danger hover:border-danger/40 hover:bg-danger-soft/60 hover:text-danger" aria-label="Hapus {{ $template->name }}" title="Hapus">
                                            <x-icon name="trash" :size="15" />
                                        </button>
                                    </div>
                                </div>
                                <p class="mt-2 rounded-xl bg-surface-2/70 px-3 py-2 text-[11px] leading-5 text-ink-2">{{ \Illuminate\Support\Str::limit(\Illuminate\Support\Str::squish($template->body), 180) }}</p>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        <div class="space-y-4 xl:col-span-5">
            <section class="card p-4 sm:p-5 xl:sticky xl:top-4" aria-labelledby="template-preview-title">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <h2 id="template-preview-title" class="card-title">Pratinjau pesan</h2>
                        <p class="mt-0.5 text-[11px] text-muted">Contoh data izin dan kontak.</p>
                    </div>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-success-soft text-success"><x-icon name="whatsapp" :size="18" /></span>
                </div>

                <div class="mt-4 rounded-2xl bg-[#e6ddd4] p-3 dark:bg-[#1f2c34]">
                    <div class="rounded-xl rounded-tl-sm bg-white px-3.5 py-3 shadow-sm dark:bg-[#005c4b]">
                        <p class="whitespace-pre-line text-[11.5px] leading-5 text-[#111b21] dark:text-[#e9edef]">{{ $preview }}</p>
                        <p class="mt-2 text-right text-[9px] text-[#667781] dark:text-[#c7e4dc]">08.00 &middot; terkirim</p>
                    </div>
                </div>

                <p class="mt-3 text-[10px] leading-4 text-muted">Pratinjau memakai data contoh. Pesan sebenarnya memakai data izin dan kontak pada saat pengiriman.</p>
            </section>

            <section class="card p-4 sm:p-5" aria-labelledby="placeholder-title">
                <h2 id="placeholder-title" class="section-title">Placeholder tersedia</h2>
                <div class="mt-2.5 flex flex-wrap gap-1.5">
                    @foreach ($placeholders as $key => $value)
                        <code class="rounded-lg bg-surface-2 px-2 py-1 font-mono text-[10px] text-ink-2">{{ $key }}</code>
                    @endforeach
                </div>
                <p class="mt-2.5 text-[10.5px] leading-4 text-muted">Gunakan placeholder persis seperti di atas. Placeholder yang tidak dikenal akan tampil apa adanya.</p>
            </section>
        </div>
    </div>

    @if ($showForm)
        <div class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-labelledby="template-form-title" x-on:keydown.escape.window="$wire.closeForm()">
            <div class="absolute inset-0 bg-ink/45 backdrop-blur-sm" wire:click="closeForm" aria-hidden="true"></div>
            <div class="absolute inset-y-0 right-0 flex w-full max-w-xl flex-col overflow-y-auto bg-surface p-5 shadow-float sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 id="template-form-title" class="text-[18px] font-bold tracking-[-0.02em] text-ink">{{ $editingId ? 'Ubah template' : 'Tambah template' }}</h2>
                        <p class="mt-0.5 text-[11.5px] text-muted">Tulis pesan yang jelas, singkat, dan menyebut nama izin serta tanggal kedaluwarsa.</p>
                    </div>
                    <button type="button" wire:click="closeForm" class="icon-button" aria-label="Tutup formulir"><x-icon name="x" :size="18" /></button>
                </div>

                <form wire:submit="save" class="mt-5 space-y-4">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label for="template-name" class="field-label">Nama template <span class="text-danger">*</span></label>
                            <input id="template-name" type="text" wire:model.blur="name" required maxlength="100" placeholder="Reminder standar" class="field" data-autofocus />
                            @error('name') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="template-category" class="field-label">Cakupan</label>
                            <select id="template-category" wire:model="categoryId" class="field">
                                <option value="">Global, semua kategori</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}{{ $category->is_active ? '' : ' (nonaktif)' }}</option>
                                @endforeach
                            </select>
                            @error('categoryId') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <div class="flex flex-wrap items-end justify-between gap-2">
                            <label for="template-body" class="field-label mb-0">Isi pesan <span class="text-danger">*</span></label>
                            <button type="button" wire:click="useDefaultBody" class="inline-flex min-h-11 items-center text-[10.5px] font-semibold text-primary hover:underline">Gunakan teks standar</button>
                        </div>
                        <textarea id="template-body" wire:model.live.debounce.500ms="body" rows="12" maxlength="5000" required class="field mt-1.5 py-2.5 font-normal leading-6"></textarea>
                        <div class="mt-1 flex items-center justify-between gap-2">
                            <p class="field-hint mt-0">Gunakan placeholder untuk data dinamis.</p>
                            <p class="text-[10px] tabular-nums text-muted">{{ mb_strlen($body) }}/5000</p>
                        </div>
                        @error('body') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <p class="micro-label mb-1.5">Sisipkan placeholder</p>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($placeholders as $key => $value)
                                <button type="button" wire:click="insertPlaceholder('{{ $key }}')" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-line bg-surface-2 px-2.5 font-mono text-[10px] text-ink-2 transition hover:border-primary/40 hover:text-primary">
                                    {{ $key }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <label class="flex min-h-11 cursor-pointer items-center gap-2.5 rounded-xl bg-surface-2 px-3 text-[12px] font-semibold text-ink">
                        <input type="checkbox" wire:model="isActive" class="h-4 w-4 rounded border-line-strong text-primary focus:ring-primary/30" />
                        Template aktif dan dapat dipakai pengiriman
                    </label>

                    <div class="flex flex-col-reverse gap-2 border-t border-line pt-4 sm:flex-row sm:justify-end">
                        <button type="button" wire:click="closeForm" class="btn-secondary">Batal</button>
                        <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">
                            <span wire:loading.remove wire:target="save">Simpan template</span>
                            <span wire:loading wire:target="save">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>

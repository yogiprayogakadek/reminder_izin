<div class="space-y-5">
    <x-page-header title="Aturan reminder" description="Tentukan kapan WhatsApp dikirim sebelum dan sesudah izin kedaluwarsa. Aturan global berlaku untuk semua kategori.">
        <x-slot name="actions">
            <button type="button" wire:click="openCreate" class="btn-primary">
                <x-icon name="plus" :size="16" />
                Tambah aturan
            </button>
        </x-slot>
    </x-page-header>

    <x-notification-tabs />

    <x-flash />

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
        <div class="space-y-4 xl:col-span-8">
            <section class="card overflow-hidden" aria-labelledby="global-rules-title">
                <div class="flex flex-wrap items-start justify-between gap-2 border-b border-line px-4 py-3.5 sm:px-5">
                    <div>
                        <h2 id="global-rules-title" class="section-title">Aturan global</h2>
                        <p class="mt-0.5 text-[11px] text-muted">Dipakai oleh kategori yang belum memiliki aturan sendiri.</p>
                    </div>
                    <span class="pill pill-neutral">{{ $globalRules->where('is_active', true)->count() }} aktif</span>
                </div>

                @if ($globalRules->isEmpty())
                    <div class="p-5">
                        <x-empty-state icon="clock" title="Belum ada aturan global" description="Tanpa aturan, tidak ada reminder otomatis yang dibuat. Tambahkan minimal satu aturan, misalnya H-30 dan H-7.">
                            <x-slot name="action">
                                <button type="button" wire:click="openCreate" class="btn-primary">Tambah aturan pertama</button>
                            </x-slot>
                        </x-empty-state>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[680px] border-collapse text-left">
                            <thead class="bg-surface-2/70">
                                <tr>
                                    <th class="table-head">Waktu kirim</th>
                                    <th class="table-head">Jam</th>
                                    <th class="table-head">Pengulangan</th>
                                    <th class="table-head">Penerima</th>
                                    <th class="table-head">Status</th>
                                    <th class="table-head text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($globalRules as $rule)
                                    <tr class="border-t border-line/70 transition hover:bg-surface-2/40">
                                        <td class="table-cell">
                                            <span class="flex items-center gap-2">
                                                <span class="flex h-7 min-w-10 items-center justify-center rounded-lg px-1.5 text-[10px] font-bold {{ $rule->is_active ? 'bg-primary-soft text-primary' : 'bg-neutral-soft text-muted' }}">{{ $rule->label() }}</span>
                                                <span class="text-[11.5px] text-ink-2">{{ $rule->offset_days === 0 ? 'Pada hari kedaluwarsa' : $rule->offset_days.' hari sebelum kedaluwarsa' }}</span>
                                            </span>
                                        </td>
                                        <td class="table-cell font-mono text-[11.5px]">{{ substr($rule->send_time, 0, 5) }}</td>
                                        <td class="table-cell text-[11px]">
                                            {{ $rule->repeat_overdue_days ? 'Tiap '.$rule->repeat_overdue_days.' hari, maks '.$rule->max_repeats.'x' : '-' }}
                                        </td>
                                        <td class="table-cell text-[11px]">{{ $rule->recipients->count() > 0 ? $rule->recipients->count().' kontak khusus' : 'Penerima izin/kategori' }}</td>
                                        <td class="table-cell">
                                            <span class="pill {{ $rule->is_active ? 'pill-success' : 'pill-neutral' }}">{{ $rule->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                        </td>
                                        <td class="table-cell">
                                            <div class="flex justify-end gap-1">
                                                <button type="button" wire:click="toggleActive({{ $rule->id }})" class="icon-button" aria-label="{{ $rule->is_active ? 'Nonaktifkan' : 'Aktifkan' }} aturan {{ $rule->label() }}" title="{{ $rule->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                                    <x-icon name="{{ $rule->is_active ? 'pause' : 'play' }}" :size="15" />
                                                </button>
                                                <button type="button" wire:click="openEdit({{ $rule->id }})" class="icon-button" aria-label="Ubah aturan {{ $rule->label() }}" title="Ubah">
                                                    <x-icon name="edit" :size="15" />
                                                </button>
                                                <button type="button" wire:click="delete({{ $rule->id }})" wire:confirm="Hapus aturan {{ $rule->label() }}? Riwayat pengiriman yang sudah ada tetap tersimpan." class="icon-button text-danger hover:border-danger/40 hover:bg-danger-soft/60 hover:text-danger" aria-label="Hapus aturan {{ $rule->label() }}" title="Hapus">
                                                    <x-icon name="trash" :size="15" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <section class="card p-4 sm:p-5" aria-labelledby="category-rules-title">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <h2 id="category-rules-title" class="card-title">Aturan per kategori</h2>
                        <p class="mt-0.5 text-[11px] text-muted">Jika sebuah kategori memiliki aturan aktif, aturan global tidak dipakai untuk kategori tersebut.</p>
                    </div>
                    <button type="button" wire:click="openCreate" class="btn-secondary px-3 text-[11.5px]">Tambah aturan kategori</button>
                </div>

                @if ($categoryRules->isEmpty())
                    <div class="mt-3 rounded-2xl border border-dashed border-line-strong/80 bg-surface-2/60 p-4 text-center">
                        <p class="text-[12px] font-semibold text-ink">Semua kategori memakai aturan global</p>
                        <p class="mt-1 text-[11px] text-muted">Tambahkan aturan kategori bila jadwal pengingatnya perlu berbeda.</p>
                    </div>
                @else
                    <div class="mt-4 space-y-3">
                        @foreach ($categoryRules as $categoryId => $rules)
                            @php $category = $categories->firstWhere('id', (int) $categoryId); @endphp
                            <div class="rounded-2xl border border-line/80 bg-surface-2/40">
                                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line/70 px-3.5 py-2.5">
                                    <div class="flex items-center gap-2">
                                        <span class="h-2 w-2 rounded-full" style="background-color: {{ $category?->color ?? '#60778B' }}"></span>
                                        <p class="text-[12px] font-bold text-ink">{{ $category?->name ?? 'Kategori dihapus' }}</p>
                                        @if ($category && ! $category->is_active)
                                            <span class="pill pill-neutral">Nonaktif</span>
                                        @endif
                                    </div>
                                    <button type="button" wire:click="openCreate({{ (int) $categoryId }})" class="inline-flex min-h-11 items-center text-[10.5px] font-semibold text-primary hover:underline">Tambah aturan</button>
                                </div>
                                <div class="flex flex-wrap gap-2 p-3.5">
                                    @foreach ($rules as $rule)
                                        <div class="flex items-center gap-2 rounded-xl border border-line/80 bg-surface px-2.5 py-1.5">
                                            <span class="text-[10px] font-bold {{ $rule->is_active ? 'text-primary' : 'text-muted' }}">{{ $rule->label() }}</span>
                                            <span class="text-[10px] text-muted">&middot; {{ substr($rule->send_time, 0, 5) }}</span>
                                            @if (! $rule->is_active)
                                                <span class="text-[9px] font-semibold uppercase text-muted">off</span>
                                            @endif
                                            <button type="button" wire:click="openEdit({{ $rule->id }})" class="inline-flex h-11 w-11 items-center justify-center rounded-lg text-muted transition hover:text-primary" aria-label="Ubah aturan {{ $rule->label() }} {{ $category?->name }}">
                                                <x-icon name="edit" :size="13" />
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        <div class="space-y-4 xl:col-span-4">
            <section class="card p-4 sm:p-5" aria-labelledby="rule-how-title">
                <h2 id="rule-how-title" class="card-title">Cara kerja</h2>
                <ol class="mt-3 space-y-3">
                    <li class="flex gap-2.5">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary-soft text-[10px] font-bold text-primary">1</span>
                        <p class="text-[11.5px] leading-5 text-ink-2">Setiap hari scheduler memeriksa izin yang belum diarsipkan dan tidak sedang diperpanjang.</p>
                    </li>
                    <li class="flex gap-2.5">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary-soft text-[10px] font-bold text-primary">2</span>
                        <p class="text-[11.5px] leading-5 text-ink-2">Aturan yang tanggal H-nya sudah tiba dipakai satu kali per siklus kedaluwarsa untuk tiap kontak.</p>
                    </li>
                    <li class="flex gap-2.5">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary-soft text-[10px] font-bold text-primary">3</span>
                        <p class="text-[11.5px] leading-5 text-ink-2">Penerima diambil dari izin, lalu kategori, lalu aturan. Kontak nonaktif tidak dikirimi pesan.</p>
                    </li>
                    <li class="flex gap-2.5">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary-soft text-[10px] font-bold text-primary">4</span>
                        <p class="text-[11.5px] leading-5 text-ink-2">Pengiriman ganda dicegah dengan kunci unik per izin, siklus, aturan, dan kontak.</p>
                    </li>
                </ol>

                <dl class="mt-4 space-y-1.5 border-t border-line pt-3">
                    <div class="flex items-center justify-between gap-2 text-[11px]">
                        <dt class="text-muted">Zona waktu</dt>
                        <dd class="font-semibold text-ink">{{ config('app.timezone') }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-2 text-[11px]">
                        <dt class="text-muted">Hari kerja saja</dt>
                        <dd class="font-semibold {{ $workdaysOnly ? 'text-success' : 'text-ink' }}">{{ $workdaysOnly ? 'Ya' : 'Tidak' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-2 text-[11px]">
                        <dt class="text-muted">Lewati hari libur</dt>
                        <dd class="font-semibold {{ $skipHolidays ? 'text-success' : 'text-ink' }}">{{ $skipHolidays ? 'Ya' : 'Tidak' }}</dd>
                    </div>
                </dl>

                @can('manage-app')
                    <a href="{{ route('settings.application') }}" wire:navigate class="btn-secondary mt-3 w-full text-[11.5px]">Atur kalender pengiriman</a>
                @endcan
            </section>

            <section class="card p-4 sm:p-5">
                <h2 class="card-title">Pengingat berulang</h2>
                <p class="mt-1 text-[11.5px] leading-5 text-muted">
                    Aturan H-0 dapat diulang setelah izin kedaluwarsa. Isi interval hari dan batas pengulangan saat membuat atau mengubah aturan. Contoh: setiap 3 hari, maksimal 5 kali.
                </p>
                <div class="mt-3 rounded-xl bg-warning-soft px-3 py-2.5 text-[10.5px] leading-4 text-warning">
                    Pengulangan berhenti otomatis saat izin ditandai sedang diperpanjang, diarsipkan, atau reminder dijeda.
                </div>
            </section>
        </div>
    </div>

    @if ($showForm)
        <div class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-labelledby="rule-form-title" x-on:keydown.escape.window="$wire.closeForm()">
            <div class="absolute inset-0 bg-ink/45 backdrop-blur-sm" wire:click="closeForm" aria-hidden="true"></div>
            <div class="absolute inset-y-0 right-0 flex w-full max-w-lg flex-col overflow-y-auto bg-surface p-5 shadow-float sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 id="rule-form-title" class="text-[18px] font-bold tracking-[-0.02em] text-ink">{{ $editingId ? 'Ubah aturan reminder' : 'Tambah aturan reminder' }}</h2>
                        <p class="mt-0.5 text-[11.5px] text-muted">Jadwal dihitung mundur dari tanggal kedaluwarsa izin.</p>
                    </div>
                    <button type="button" wire:click="closeForm" class="icon-button" aria-label="Tutup formulir"><x-icon name="x" :size="18" /></button>
                </div>

                <form wire:submit="save" class="mt-5 space-y-4">
                    <div>
                        <label for="rule-scope" class="field-label">Cakupan</label>
                        <select id="rule-scope" wire:model="categoryId" class="field" data-autofocus>
                            <option value="">Global, semua kategori</option>
                            @foreach ($activeCategories as $category)
                                <option value="{{ $category->id }}">Khusus {{ $category->name }}</option>
                            @endforeach
                        </select>
                        <p class="field-hint">Aturan kategori menggantikan seluruh aturan global untuk kategori tersebut.</p>
                        @error('categoryId') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="rule-offset" class="field-label">H- berapa hari <span class="text-danger">*</span></label>
                            <input id="rule-offset" type="number" wire:model.blur="offsetDays" min="0" max="365" required class="field" />
                            <p class="field-hint">0 berarti hari kedaluwarsa.</p>
                            @error('offsetDays') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="rule-time" class="field-label">Jam kirim <span class="text-danger">*</span></label>
                            <input id="rule-time" type="time" wire:model.blur="sendTime" required class="field" />
                            @error('sendTime') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="rounded-xl border border-line bg-surface-2/60 p-3">
                        <p class="text-[12px] font-semibold text-ink">Pengulangan setelah kedaluwarsa</p>
                        <p class="mt-0.5 text-[10.5px] text-muted">Opsional. Berguna untuk aturan H-0 agar izin yang belum diperpanjang terus diingatkan.</p>
                        <div class="mt-2.5 grid grid-cols-2 gap-3">
                            <div>
                                <label for="rule-repeat" class="field-label text-[11px]">Setiap (hari)</label>
                                <input id="rule-repeat" type="number" wire:model.blur="repeatOverdueDays" min="1" max="60" placeholder="3" class="field" />
                            </div>
                            <div>
                                <label for="rule-max" class="field-label text-[11px]">Maksimal kali</label>
                                <input id="rule-max" type="number" wire:model.blur="maxRepeats" min="1" max="20" placeholder="5" class="field" />
                            </div>
                        </div>
                        @error('repeatOverdueDays') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                        @error('maxRepeats') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <span class="field-label">Penerima khusus aturan</span>
                        @if ($contacts->isEmpty())
                            <p class="rounded-xl bg-surface-2 px-3 py-2.5 text-[11px] text-muted">Belum ada kontak aktif. Penerima akan mengikuti izin atau kategori.</p>
                        @else
                            <div class="grid max-h-40 gap-1 overflow-y-auto rounded-xl border border-line p-2 sm:grid-cols-2">
                                @foreach ($contacts as $contact)
                                    <label class="flex min-h-11 cursor-pointer items-center gap-2 rounded-lg px-2 text-[11.5px] font-medium text-ink-2 hover:bg-primary-soft/50">
                                        <input type="checkbox" wire:model="recipientIds" value="{{ $contact->id }}" class="h-4 w-4 rounded border-line-strong text-primary focus:ring-primary/30" />
                                        <span class="min-w-0 truncate">{{ $contact->name }}<span class="block text-[9.5px] text-muted">{{ $contact->label ?: 'Tanpa label' }}</span></span>
                                    </label>
                                @endforeach
                            </div>
                            <p class="field-hint">Kosongkan agar penerima mengikuti data izin atau kategori.</p>
                        @endif
                        @error('recipientIds.*') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                    </div>

                    <label class="flex min-h-11 cursor-pointer items-center gap-2.5 rounded-xl bg-surface-2 px-3 text-[12px] font-semibold text-ink">
                        <input type="checkbox" wire:model="isActive" class="h-4 w-4 rounded border-line-strong text-primary focus:ring-primary/30" />
                        Aturan aktif
                    </label>

                    <div class="flex flex-col-reverse gap-2 border-t border-line pt-4 sm:flex-row sm:justify-end">
                        <button type="button" wire:click="closeForm" class="btn-secondary">Batal</button>
                        <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">
                            <span wire:loading.remove wire:target="save">Simpan aturan</span>
                            <span wire:loading wire:target="save">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>

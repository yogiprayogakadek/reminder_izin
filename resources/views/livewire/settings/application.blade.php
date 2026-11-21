<div class="space-y-5">
    <x-page-header title="Pengaturan aplikasi" description="Identitas, zona waktu, batas file, dan kalender pengiriman reminder. Perubahan berlaku untuk pengiriman berikutnya." />

    <x-settings-tabs />

    <x-flash />

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
        <div class="space-y-4 xl:col-span-8">
            <form wire:submit="save" class="card p-4 sm:p-5">
                <h2 class="card-title">Identitas dan waktu</h2>
                <p class="mt-0.5 text-[11px] text-muted">Nama tampilan, tautan aplikasi, dan zona waktu operasional.</p>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="app-name" class="field-label">Nama aplikasi <span class="text-danger">*</span></label>
                        <input id="app-name" type="text" wire:model.blur="appName" required maxlength="60" class="field" />
                        @error('appName') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="app-url" class="field-label">URL publik aplikasi</label>
                        <input id="app-url" type="url" wire:model.blur="appUrl" placeholder="https://siperiz.perusahaan.co.id" class="field" />
                        <p class="field-hint">Dipakai pada tautan detail izin di pesan WhatsApp.</p>
                        @error('appUrl') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="app-timezone" class="field-label">Zona waktu <span class="text-danger">*</span></label>
                        <select id="app-timezone" wire:model="timezone" class="field" required>
                            @foreach ($timezones as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('timezone') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="app-locale" class="field-label">Bahasa antarmuka <span class="text-danger">*</span></label>
                        <select id="app-locale" wire:model="locale" class="field" required>
                            <option value="id">Bahasa Indonesia</option>
                            <option value="en">English</option>
                        </select>
                        @error('locale') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-5 border-t border-line pt-4">
                    <h3 class="section-title">Kalender pengiriman</h3>
                    <p class="mt-0.5 text-[11px] text-muted">Scheduler berjalan setiap menit, tetapi pengiriman mengikuti aturan dan opsi di bawah.</p>

                    <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="app-reminder-time" class="field-label">Jam reminder default <span class="text-danger">*</span></label>
                            <input id="app-reminder-time" type="time" wire:model.blur="reminderTime" required class="field" />
                            <p class="field-hint">Dipakai sebagai nilai awal saat membuat aturan baru.</p>
                            @error('reminderTime') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex flex-col justify-end gap-2">
                            <label class="flex min-h-11 cursor-pointer items-center gap-2.5 rounded-xl bg-surface-2 px-3 text-[11.5px] font-semibold text-ink">
                                <input type="checkbox" wire:model="workdaysOnly" class="h-4 w-4 rounded border-line-strong text-primary focus:ring-primary/30" />
                                Kirim hanya pada hari kerja
                            </label>
                            <label class="flex min-h-11 cursor-pointer items-center gap-2.5 rounded-xl bg-surface-2 px-3 text-[11.5px] font-semibold text-ink">
                                <input type="checkbox" wire:model="skipHolidays" class="h-4 w-4 rounded border-line-strong text-primary focus:ring-primary/30" />
                                Lewati hari libur pada daftar
                            </label>
                        </div>
                    </div>
                </div>

                <div class="mt-5 border-t border-line pt-4">
                    <h3 class="section-title">Batas dan retensi</h3>

                    <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="app-file-max" class="field-label">Ukuran file maksimal (MB) <span class="text-danger">*</span></label>
                            <input id="app-file-max" type="number" wire:model.blur="fileMaxMb" min="1" max="50" required class="field" />
                            <p class="field-hint">Berlaku untuk unggahan PDF, JPG, dan PNG.</p>
                            @error('fileMaxMb') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="app-retention" class="field-label">Retensi audit log (hari) <span class="text-danger">*</span></label>
                            <input id="app-retention" type="number" wire:model.blur="logRetentionDays" min="7" max="3650" required class="field" />
                            <p class="field-hint">Log lebih lama dari batas ini dihapus otomatis.</p>
                            @error('logRetentionDays') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex justify-end border-t border-line pt-4">
                    <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">Simpan pengaturan</span>
                        <span wire:loading wire:target="save">Menyimpan...</span>
                    </button>
                </div>
            </form>

            <section class="card p-4 sm:p-5" aria-labelledby="operations-title">
                <h2 id="operations-title" class="card-title">Operasional pengiriman</h2>
                <p class="mt-0.5 text-[11px] text-muted">Reminder tidak dikirim langsung oleh halaman web. Server perlu menjalankan scheduler dan worker antrean.</p>

                <div class="mt-3 space-y-2">
                    <div class="rounded-xl border border-line bg-surface-2/60 p-3">
                        <p class="text-[10.5px] font-bold uppercase tracking-wide text-muted">Scheduler, setiap menit</p>
                        <code class="mt-1 block overflow-x-auto font-mono text-[10.5px] text-ink">php artisan schedule:run</code>
                    </div>
                    <div class="rounded-xl border border-line bg-surface-2/60 p-3">
                        <p class="text-[10.5px] font-bold uppercase tracking-wide text-muted">Worker antrean, driver {{ $queueDriver }}</p>
                        <code class="mt-1 block overflow-x-auto font-mono text-[10.5px] text-ink">php artisan queue:work --tries=3</code>
                    </div>
                </div>
                <p class="mt-2 text-[10px] leading-4 text-muted">Tanpa scheduler, tidak ada reminder otomatis yang dibuat. Tanpa worker, pesan tetap menunggu di antrean.</p>
            </section>
        </div>

        <div class="space-y-4 xl:col-span-4">
            <section class="card p-4 sm:p-5" aria-labelledby="holiday-title">
                <h2 id="holiday-title" class="card-title">Hari libur</h2>
                <p class="mt-0.5 text-[11px] text-muted">Dipakai saat opsi lewati hari libur aktif.</p>

                <form wire:submit="addHoliday" class="mt-3 space-y-2.5">
                    <div>
                        <label for="holiday-date" class="field-label text-[11px]">Tanggal <span class="text-danger">*</span></label>
                        <input id="holiday-date" type="date" wire:model.blur="holidayDate" required class="field field-sm" />
                        @error('holidayDate') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="holiday-name" class="field-label text-[11px]">Nama hari libur <span class="text-danger">*</span></label>
                        <input id="holiday-name" type="text" wire:model.blur="holidayName" required maxlength="100" placeholder="Contoh: Hari Raya Idul Fitri" class="field field-sm" />
                        @error('holidayName') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="btn-secondary w-full text-[11.5px]" wire:loading.attr="disabled" wire:target="addHoliday">
                        <x-icon name="plus" :size="14" />
                        Tambah hari libur
                    </button>
                </form>

                <div class="mt-3 space-y-1.5 border-t border-line pt-3">
                    @forelse ($holidays as $holiday)
                        <div class="flex items-center justify-between gap-2 rounded-lg px-1 py-1">
                            <div class="min-w-0">
                                <p class="truncate text-[11px] font-semibold text-ink">{{ $holiday->name }}</p>
                                <p class="text-[9.5px] text-muted">{{ $holiday->date->translatedFormat('l, d M Y') }}</p>
                            </div>
                            <button type="button" wire:click="deleteHoliday({{ $holiday->id }})" wire:confirm="Hapus hari libur {{ $holiday->name }}?" class="inline-flex h-11 w-11 items-center justify-center rounded-lg text-muted transition hover:text-danger" aria-label="Hapus {{ $holiday->name }}">
                                <x-icon name="trash" :size="14" />
                            </button>
                        </div>
                    @empty
                        <p class="text-[10.5px] text-muted">Belum ada hari libur mendatang.</p>
                    @endforelse
                </div>
            </section>

            <section class="card p-4 sm:p-5">
                <h2 class="section-title">Ringkasan sistem</h2>
                <dl class="mt-3 space-y-2">
                    <div class="flex items-center justify-between gap-2 text-[11px]">
                        <dt class="text-muted">Zona waktu aktif</dt>
                        <dd class="font-semibold text-ink">{{ config('app.timezone') }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-2 text-[11px]">
                        <dt class="text-muted">Locale aktif</dt>
                        <dd class="font-semibold text-ink">{{ app()->getLocale() }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-2 text-[11px]">
                        <dt class="text-muted">Penyimpanan file</dt>
                        <dd class="font-semibold text-ink">Privat lokal</dd>
                    </div>
                    <div class="flex items-center justify-between gap-2 text-[11px]">
                        <dt class="text-muted">Antrean</dt>
                        <dd class="font-semibold text-ink">{{ config('queue.default') }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-2 text-[11px]">
                        <dt class="text-muted">Versi Laravel</dt>
                        <dd class="font-semibold text-ink">{{ app()->version() }}</dd>
                    </div>
                </dl>
            </section>
        </div>
    </div>
</div>

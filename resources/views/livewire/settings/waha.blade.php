@php
    $statusClasses = match ($statusTone) {
        'success' => 'bg-success-soft text-success',
        'warning' => 'bg-warning-soft text-warning',
        'danger' => 'bg-danger-soft text-danger',
        default => 'bg-neutral-soft text-ink-2',
    };
@endphp

<div class="space-y-5">
    <x-page-header title="Koneksi WAHA" description="Hubungkan SIPERIZ ke server WhatsApp HTTP API (WAHA). Kredensial disimpan terenkripsi dan tidak pernah ditampilkan penuh." />

    <x-settings-tabs />

    <x-flash />

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
        <div class="space-y-4 xl:col-span-8">
            <form wire:submit="save" class="card p-4 sm:p-5">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <h2 class="card-title">Konfigurasi server</h2>
                        <p class="mt-0.5 text-[11px] text-muted">Nilai di sini menggantikan konfigurasi environment.</p>
                    </div>
                    <span class="pill {{ $isEnabled ? 'pill-success' : 'pill-neutral' }}">{{ $isEnabled ? 'Pengiriman aktif' : 'Pengiriman nonaktif' }}</span>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="waha-base-url" class="field-label">Base URL WAHA</label>
                        <input id="waha-base-url" type="url" wire:model.blur="baseUrl" placeholder="http://127.0.0.1:3000" class="field" autocomplete="off" spellcheck="false" />
                        <p class="field-hint">Alamat server WAHA tanpa garis miring di akhir. Sertakan skema http:// atau https://.</p>
                        @error('baseUrl') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="waha-session" class="field-label">Nama sesi <span class="text-danger">*</span></label>
                        <input id="waha-session" type="text" wire:model.blur="session" required maxlength="80" placeholder="default" class="field" autocomplete="off" spellcheck="false" />
                        <p class="field-hint">Nama sesi pada dashboard WAHA.</p>
                        @error('session') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="waha-country" class="field-label">Kode negara default <span class="text-danger">*</span></label>
                        <input id="waha-country" type="text" wire:model.blur="defaultCountryCode" required maxlength="5" inputmode="numeric" class="field" />
                        <p class="field-hint">Dipakai saat menormalkan nomor kontak.</p>
                        @error('defaultCountryCode') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="waha-api-key" class="field-label">API key</label>
                        <div class="relative" x-data="{ show: false }">
                            <input id="waha-api-key" :type="show ? 'text' : 'password'" wire:model.blur="apiKey" maxlength="500" placeholder="{{ $settings->maskedApiKey() }}" class="field pr-20" autocomplete="new-password" spellcheck="false" />
                            <button type="button" x-on:click="show = !show" class="absolute inset-y-0 right-1 flex items-center rounded-full px-3 text-[10.5px] font-semibold text-primary hover:bg-primary-soft" x-text="show ? 'Sembunyikan' : 'Tampilkan'">Tampilkan</button>
                        </div>
                        <p class="field-hint">Kosongkan untuk mempertahankan API key yang tersimpan. Isi hanya saat mengganti kredensial.</p>
                        @error('apiKey') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                    </div>

                    @if ($settings->api_key)
                        <label class="flex min-h-11 cursor-pointer items-center gap-2 text-[11px] font-medium text-danger sm:col-span-2">
                            <input type="checkbox" wire:model="clearApiKey" class="h-4 w-4 rounded border-line-strong text-danger focus:ring-danger/30" />
                            Hapus API key tersimpan saat menyimpan
                        </label>
                    @endif

                    <div>
                        <label for="waha-limit" class="field-label">Batas kirim harian <span class="text-danger">*</span></label>
                        <input id="waha-limit" type="number" wire:model.blur="dailyLimit" min="1" max="10000" required class="field" />
                        <p class="field-hint">Mencegah pengiriman melebihi kapasitas sesi.</p>
                        @error('dailyLimit') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-end">
                        <label class="flex min-h-11 w-full cursor-pointer items-center gap-2.5 rounded-xl bg-surface-2 px-3 text-[12px] font-semibold text-ink">
                            <input type="checkbox" wire:model="isEnabled" class="h-4 w-4 rounded border-line-strong text-primary focus:ring-primary/30" />
                            Aktifkan pengiriman otomatis
                        </label>
                    </div>
                    @error('isEnabled') <p class="field-hint !text-danger sm:col-span-2">{{ $message }}</p> @enderror
                </div>

                <div class="mt-4 rounded-xl border border-line bg-surface-2/70 p-3">
                    <p class="text-[12px] font-semibold text-ink">Webhook status pengiriman</p>
                    <p class="mt-0.5 text-[10.5px] leading-4 text-muted">Daftarkan URL ini di pengaturan global WAHA agar status terkirim, diterima, dan dibaca ikut diperbarui.</p>
                    <div class="mt-2 flex items-center gap-2">
                        <code class="min-w-0 flex-1 truncate rounded-lg bg-surface px-2.5 py-2 font-mono text-[10px] text-ink-2 ring-1 ring-line">{{ $webhookUrl }}</code>
                        <button type="button" class="btn-secondary shrink-0 px-3 text-[10.5px]" x-data x-on:click="navigator.clipboard.writeText('{{ $webhookUrl }}'); $el.textContent = 'Tersalin'" title="Salin URL webhook">Salin</button>
                    </div>
                    <p class="mt-2 text-[10px] leading-4 text-muted">Event: <code class="font-mono">session.status</code> dan <code class="font-mono">message.ack</code>. Kirim secret melalui header <code class="font-mono">X-Webhook-Secret</code> atau parameter <code class="font-mono">?secret=</code>.</p>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 border-t border-line pt-4 sm:grid-cols-2">
                    <div>
                        <label for="waha-secret" class="field-label">Webhook secret</label>
                        <input id="waha-secret" type="password" wire:model.blur="webhookSecret" maxlength="500" placeholder="{{ $settings->maskedWebhookSecret() }}" class="field" autocomplete="new-password" spellcheck="false" />
                        <p class="field-hint">Kosongkan untuk mempertahankan secret yang tersimpan.</p>
                        @error('webhookSecret') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-end">
                        @if ($settings->webhook_secret)
                            <label class="flex min-h-11 cursor-pointer items-center gap-2 text-[11px] font-medium text-danger">
                                <input type="checkbox" wire:model="clearWebhookSecret" class="h-4 w-4 rounded border-line-strong text-danger focus:ring-danger/30" />
                                Hapus webhook secret tersimpan
                            </label>
                        @endif
                    </div>
                </div>

                <div class="mt-4 flex flex-col-reverse gap-2 border-t border-line pt-4 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="testConnection" class="btn-secondary" wire:loading.attr="disabled" wire:target="testConnection">
                        <x-icon name="refresh" :size="15" />
                        <span wire:loading.remove wire:target="testConnection">Simpan &amp; uji koneksi</span>
                        <span wire:loading wire:target="testConnection">Menguji...</span>
                    </button>
                    <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">Simpan konfigurasi</span>
                        <span wire:loading wire:target="save">Menyimpan...</span>
                    </button>
                </div>
            </form>

            <section class="card p-4 sm:p-5">
                <h2 class="card-title">Persiapan di sisi WAHA</h2>
                <ol class="mt-3 space-y-2.5">
                    <li class="flex gap-2.5 text-[11.5px] leading-5 text-ink-2">
                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary-soft text-[9.5px] font-bold text-primary">1</span>
                        Jalankan container WAHA dan pastikan dapat diakses dari server aplikasi.
                    </li>
                    <li class="flex gap-2.5 text-[11.5px] leading-5 text-ink-2">
                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary-soft text-[9.5px] font-bold text-primary">2</span>
                        Buat sesi sesuai nama di formulir, lalu pindai QR melalui dashboard WAHA hingga status <span class="font-semibold">WORKING</span>.
                    </li>
                    <li class="flex gap-2.5 text-[11.5px] leading-5 text-ink-2">
                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary-soft text-[9.5px] font-bold text-primary">3</span>
                        Ambil API key dari konfigurasi WAHA dan simpan di formulir ini.
                    </li>
                    <li class="flex gap-2.5 text-[11.5px] leading-5 text-ink-2">
                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary-soft text-[9.5px] font-bold text-primary">4</span>
                        Uji koneksi. SIPERIZ hanya membuat antrean reminder bila konfigurasi lengkap dan pengiriman diaktifkan.
                    </li>
                </ol>
            </section>
        </div>

        <div class="space-y-4 xl:col-span-4">
            <section class="card p-4 sm:p-5" aria-labelledby="waha-status-title">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <h2 id="waha-status-title" class="card-title">Status koneksi</h2>
                        <p class="mt-0.5 text-[11px] text-muted">Hasil pemeriksaan terakhir</p>
                    </div>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary-soft text-primary"><x-icon name="whatsapp" :size="18" /></span>
                </div>

                <span class="mt-3 inline-flex rounded-full px-2.5 py-1 text-[10.5px] font-bold {{ $statusClasses }}">{{ $statusLabel }}</span>

                <dl class="mt-3 space-y-2">
                    <div class="flex items-start justify-between gap-3 text-[11px]">
                        <dt class="text-muted">Pengiriman</dt>
                        <dd class="font-semibold {{ $settings->is_enabled ? 'text-success' : 'text-ink' }}">{{ $settings->is_enabled ? 'Aktif' : 'Nonaktif' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3 text-[11px]">
                        <dt class="text-muted">Sesi</dt>
                        <dd class="font-mono font-semibold text-ink">{{ $settings->resolvedSession() }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3 text-[11px]">
                        <dt class="text-muted">Nomor pengirim</dt>
                        <dd class="font-mono font-semibold text-ink">{{ $settings->sender_number ?? 'Belum diketahui' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3 text-[11px]">
                        <dt class="text-muted">Terakhir diuji</dt>
                        <dd class="font-semibold text-ink">{{ $settings->last_tested_at?->locale('id')->diffForHumans() ?? 'Belum pernah' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3 text-[11px]">
                        <dt class="text-muted">Batas harian</dt>
                        <dd class="font-semibold text-ink">{{ number_format($settings->daily_limit, 0, ',', '.') }} pesan</dd>
                    </div>
                </dl>

                @if ($settings->last_error)
                    <p class="mt-3 rounded-xl bg-danger-soft px-3 py-2 text-[10.5px] leading-4 text-danger">{{ $settings->last_error }}</p>
                @endif

                @if ($settings->is_enabled)
                    <button type="button" wire:click="disable" wire:confirm="Nonaktifkan pengiriman WhatsApp? Reminder otomatis baru tidak akan dibuat sampai diaktifkan kembali." class="btn-ghost mt-3 w-full text-[11.5px] text-warning">
                        Nonaktifkan pengiriman
                    </button>
                @endif
            </section>

            <section class="card p-4 sm:p-5">
                <h2 class="section-title">Catatan keamanan</h2>
                <ul class="mt-2.5 space-y-2 text-[10.5px] leading-4 text-muted">
                    <li class="flex gap-2"><x-icon name="lock" :size="13" class="mt-0.5 shrink-0 text-primary" /> API key dan webhook secret dienkripsi di database.</li>
                    <li class="flex gap-2"><x-icon name="shield" :size="13" class="mt-0.5 shrink-0 text-primary" /> Nilai penuh tidak pernah dikirim ke browser setelah disimpan.</li>
                    <li class="flex gap-2"><x-icon name="info" :size="13" class="mt-0.5 shrink-0 text-primary" /> Gunakan jaringan privat untuk komunikasi ke WAHA bila memungkinkan.</li>
                    <li class="flex gap-2"><x-icon name="refresh" :size="13" class="mt-0.5 shrink-0 text-primary" /> Perubahan konfigurasi berlaku pada pengiriman berikutnya.</li>
                </ul>
            </section>
        </div>
    </div>
</div>

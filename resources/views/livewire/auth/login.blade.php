<div class="w-full max-w-[436px]">
    <div class="card p-6 sm:p-8">
        <div class="flex flex-col items-center text-center">
            <x-brand class="h-14 w-14" />
            <h1 class="mt-4 text-[24px] font-bold tracking-[-0.03em] text-ink">Masuk ke SIPERIZ</h1>
            <p class="mt-1 max-w-xs text-[12.5px] leading-5 text-muted">Pantau masa berlaku izin perusahaan dan kirim reminder tepat waktu.</p>
        </div>

        @if (session('status'))
            <div class="mt-5 rounded-xl bg-success-soft px-3.5 py-2.5 text-[12px] font-medium text-success" role="status">
                {{ session('status') }}
            </div>
        @endif

        <form wire:submit="login" class="mt-6 space-y-4">
            <div>
                <label for="email" class="field-label">Alamat email</label>
                <div class="relative">
                    <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-muted">
                        <x-icon name="mail" :size="16" />
                    </span>
                    <input
                        id="email"
                        type="email"
                        wire:model.blur="email"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="nama@perusahaan.com"
                        class="field rounded-control pl-10"
                        @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                    />
                </div>
                @error('email')
                    <p id="email-error" class="mt-1.5 text-[11.5px] font-medium text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <div class="flex items-center justify-between">
                    <label for="password" class="field-label">Kata sandi</label>
                    <a href="{{ route('password.request') }}" wire:navigate class="inline-flex min-h-11 items-center text-[11.5px] font-semibold text-primary hover:underline">Lupa kata sandi?</a>
                </div>
                <div class="relative" x-data="{ visible: false }">
                    <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-muted">
                        <x-icon name="lock" :size="16" />
                    </span>
                    <input
                        id="password"
                        :type="visible ? 'text' : 'password'"
                        wire:model.blur="password"
                        required
                        autocomplete="current-password"
                        placeholder="Masukkan kata sandi"
                        class="field rounded-control pl-10 pr-11"
                        @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                    />
                    <button
                        type="button"
                        x-on:click="visible = !visible"
                        class="absolute inset-y-0 right-1 flex w-11 items-center justify-center rounded-full text-muted transition hover:bg-primary-soft hover:text-primary"
                        :aria-label="visible ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                    >
                        <x-icon name="eye" x-show="!visible" :size="16" />
                        <x-icon name="eye-off" x-show="visible" x-cloak :size="16" />
                    </button>
                </div>
                @error('password')
                    <p id="password-error" class="mt-1.5 text-[11.5px] font-medium text-danger">{{ $message }}</p>
                @enderror
            </div>

            <label class="flex min-h-11 cursor-pointer items-center gap-2.5 text-[12px] font-medium text-ink-2">
                <input type="checkbox" wire:model="remember" class="h-4 w-4 rounded border-line-strong text-primary focus:ring-primary/30" />
                Ingat saya di perangkat ini
            </label>

            <button type="submit" class="btn-primary w-full" wire:loading.attr="disabled" wire:target="login">
                <span wire:loading.remove wire:target="login">Masuk ke SIPERIZ</span>
                <span wire:loading wire:target="login">Memeriksa...</span>
                <x-icon name="arrow-right" :size="16" wire:loading.remove wire:target="login" />
            </button>
        </form>

        <div class="mt-6 flex items-center justify-center gap-2 border-t border-line pt-5 text-[11px] text-muted">
            <x-icon name="shield" :size="14" class="text-primary" />
            Khusus Admin, Sekretaris, dan HRD
        </div>
    </div>

    <p class="mt-4 text-center text-[11px] text-muted">Akun dibuat oleh Admin. Hubungi Admin jika Anda belum memiliki akses.</p>
</div>

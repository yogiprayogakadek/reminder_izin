<div class="w-full max-w-[436px]">
    <div class="card p-6 sm:p-8">
        <div class="flex flex-col items-center text-center">
            <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-primary-soft text-primary">
                <x-icon name="mail" :size="22" />
            </span>
            <h1 class="mt-4 text-[22px] font-bold tracking-[-0.03em] text-ink">Lupa kata sandi?</h1>
            <p class="mt-1 max-w-xs text-[12.5px] leading-5 text-muted">Masukkan email akun Anda. Kami akan mengirim tautan untuk mengatur kata sandi baru.</p>
        </div>

        @if ($status)
            <div class="mt-5 rounded-xl bg-success-soft px-3.5 py-2.5 text-[12px] font-medium text-success" role="status">
                {{ $status }}
            </div>
        @endif

        <form wire:submit="send" class="mt-6 space-y-4">
            <div>
                <label for="email" class="field-label">Alamat email</label>
                <div class="relative">
                    <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-muted">
                        <x-icon name="mail" :size="16" />
                    </span>
                    <input id="email" type="email" wire:model.blur="email" required autofocus autocomplete="username" placeholder="nama@perusahaan.com" class="field rounded-control pl-10" />
                </div>
                @error('email')
                    <p class="mt-1.5 text-[11.5px] font-medium text-danger">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="btn-primary w-full" wire:loading.attr="disabled" wire:target="send">
                <span wire:loading.remove wire:target="send">Kirim tautan</span>
                <span wire:loading wire:target="send">Mengirim...</span>
                <x-icon name="send" :size="15" wire:loading.remove wire:target="send" />
            </button>
        </form>

        <div class="mt-5 border-t border-line pt-4 text-center">
            <a href="{{ route('login') }}" wire:navigate class="inline-flex min-h-11 items-center gap-1.5 text-[12px] font-semibold text-primary hover:underline">
                <x-icon name="arrow-left" :size="14" />
                Kembali ke halaman masuk
            </a>
        </div>
    </div>
</div>

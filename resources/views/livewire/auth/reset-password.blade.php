<div class="w-full max-w-[436px]">
    <div class="card p-6 sm:p-8">
        <div class="flex flex-col items-center text-center">
            <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-primary-soft text-primary">
                <x-icon name="lock" :size="22" />
            </span>
            <h1 class="mt-4 text-[22px] font-bold tracking-[-0.03em] text-ink">Atur kata sandi baru</h1>
            <p class="mt-1 max-w-xs text-[12.5px] leading-5 text-muted">Gunakan minimal 8 karakter dan kata sandi yang tidak dipakai di layanan lain.</p>
        </div>

        @if (session('status'))
            <div class="mt-5 rounded-xl bg-success-soft px-3.5 py-2.5 text-[12px] font-medium text-success" role="status">{{ session('status') }}</div>
        @endif

        <form wire:submit="resetPassword" class="mt-6 space-y-4">
            <div>
                <label for="email" class="field-label">Alamat email</label>
                <input id="email" type="email" wire:model.blur="email" required autocomplete="username" class="field" />
                @error('email') <p class="mt-1.5 text-[11.5px] font-medium text-danger">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password" class="field-label">Kata sandi baru</label>
                <input id="password" type="password" wire:model.blur="password" required autocomplete="new-password" class="field" />
                @error('password') <p class="mt-1.5 text-[11.5px] font-medium text-danger">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password_confirmation" class="field-label">Ulangi kata sandi baru</label>
                <input id="password_confirmation" type="password" wire:model.blur="password_confirmation" required autocomplete="new-password" class="field" />
            </div>

            <button type="submit" class="btn-primary w-full" wire:loading.attr="disabled" wire:target="resetPassword">
                <span wire:loading.remove wire:target="resetPassword">Simpan kata sandi</span>
                <span wire:loading wire:target="resetPassword">Menyimpan...</span>
            </button>
        </form>
    </div>
</div>

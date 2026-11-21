@php
    $rolePill = match ($user->role->tone()) {
        'success' => 'bg-success-soft text-success',
        'info' => 'bg-info-soft text-info',
        default => 'bg-neutral-soft text-ink-2',
    };
@endphp

<div class="space-y-5">
    <x-page-header title="Profil dan kata sandi" description="Perbarui informasi akun yang Anda gunakan untuk masuk ke SIPERIZ." />

    <x-flash />

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
        <div class="space-y-4 xl:col-span-8">
            <form wire:submit="updateProfile" class="card p-4 sm:p-5">
                <h2 class="card-title">Informasi profil</h2>
                <p class="mt-0.5 text-[11px] text-muted">Nama tampil pada aktivitas dan jejak audit.</p>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="profile-name" class="field-label">Nama lengkap <span class="text-danger">*</span></label>
                        <input id="profile-name" type="text" wire:model.blur="name" required maxlength="100" class="field" />
                        @error('name') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="profile-email" class="field-label">Email <span class="text-danger">*</span></label>
                        <input id="profile-email" type="email" wire:model.blur="email" required maxlength="150" class="field" autocomplete="email" />
                        @error('email') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="profile-phone" class="field-label">Telepon</label>
                        <input id="profile-phone" type="tel" wire:model.blur="phone" maxlength="30" class="field" autocomplete="tel" />
                        @error('phone') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-4 flex justify-end border-t border-line pt-4">
                    <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="updateProfile">
                        <span wire:loading.remove wire:target="updateProfile">Simpan profil</span>
                        <span wire:loading wire:target="updateProfile">Menyimpan...</span>
                    </button>
                </div>
            </form>

            <form wire:submit="updatePassword" class="card p-4 sm:p-5">
                <h2 class="card-title">Kata sandi</h2>
                <p class="mt-0.5 text-[11px] text-muted">Minimal 10 karakter, kombinasi huruf besar, huruf kecil, dan angka.</p>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="profile-current-password" class="field-label">Kata sandi saat ini <span class="text-danger">*</span></label>
                        <input id="profile-current-password" type="password" wire:model.blur="currentPassword" required class="field" autocomplete="current-password" />
                        @error('currentPassword') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="profile-password" class="field-label">Kata sandi baru <span class="text-danger">*</span></label>
                        <input id="profile-password" type="password" wire:model.blur="password" required class="field" autocomplete="new-password" />
                        @error('password') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="profile-password-confirmation" class="field-label">Ulangi kata sandi baru <span class="text-danger">*</span></label>
                        <input id="profile-password-confirmation" type="password" wire:model.blur="password_confirmation" required class="field" autocomplete="new-password" />
                    </div>
                </div>

                <div class="mt-4 flex justify-end border-t border-line pt-4">
                    <button type="submit" class="btn-secondary" wire:loading.attr="disabled" wire:target="updatePassword">
                        <x-icon name="lock" :size="15" />
                        <span wire:loading.remove wire:target="updatePassword">Ubah kata sandi</span>
                        <span wire:loading wire:target="updatePassword">Menyimpan...</span>
                    </button>
                </div>
            </form>
        </div>

        <div class="space-y-4 xl:col-span-4">
            <section class="card p-4 sm:p-5" aria-labelledby="profile-account-title">
                <div class="flex flex-col items-center text-center">
                    <span class="flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-sky-700 to-sky-900 text-[18px] font-bold text-white shadow-sm">{{ $user->initials() }}</span>
                    <h2 id="profile-account-title" class="mt-3 text-[16px] font-bold tracking-[-0.02em] text-ink">{{ $user->name }}</h2>
                    <p class="text-[11px] text-muted">{{ $user->email }}</p>
                    <span class="mt-2 rounded-full px-2.5 py-1 text-[10.5px] font-semibold {{ $rolePill }}">{{ $user->role->label() }}</span>
                </div>

                <dl class="mt-4 space-y-2 border-t border-line pt-3">
                    <div class="flex items-center justify-between gap-2 text-[11px]">
                        <dt class="text-muted">Status akun</dt>
                        <dd class="font-semibold {{ $user->is_active ? 'text-success' : 'text-danger' }}">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-2 text-[11px]">
                        <dt class="text-muted">Login terakhir</dt>
                        <dd class="font-semibold text-ink">{{ $user->last_login_at?->locale('id')->diffForHumans() ?? 'Belum pernah' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-2 text-[11px]">
                        <dt class="text-muted">Terdaftar sejak</dt>
                        <dd class="font-semibold text-ink">{{ $user->created_at->translatedFormat('d M Y') }}</dd>
                    </div>
                </dl>

                <a href="{{ route('notifications.inbox') }}" wire:navigate class="btn-secondary mt-3 w-full text-[11.5px]">
                    <x-icon name="inbox" :size="15" />
                    Notifikasi saya
                </a>
            </section>

            <section class="card p-4 sm:p-5" aria-labelledby="profile-security-title">
                <h2 id="profile-security-title" class="section-title">Keamanan akun</h2>
                <ul class="mt-2.5 space-y-2 text-[10.5px] leading-4 text-muted">
                    <li class="flex gap-2"><x-icon name="shield" :size="13" class="mt-0.5 shrink-0 text-primary" /> Gunakan kata sandi unik yang tidak dipakai di layanan lain.</li>
                    <li class="flex gap-2"><x-icon name="lock" :size="13" class="mt-0.5 shrink-0 text-primary" /> Sesi berakhir saat Anda keluar dari aplikasi.</li>
                    <li class="flex gap-2"><x-icon name="user" :size="13" class="mt-0.5 shrink-0 text-primary" /> Perubahan peran dan status akun hanya dapat dilakukan Admin.</li>
                </ul>
            </section>
        </div>
    </div>
</div>

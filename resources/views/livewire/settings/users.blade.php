@php
    $rolePill = fn ($role) => match ($role->tone()) {
        'success' => 'bg-success-soft text-success',
        'info' => 'bg-info-soft text-info',
        default => 'bg-neutral-soft text-ink-2',
    };
@endphp

<div class="space-y-5">
    <x-page-header title="Pengguna & akses" description="Kelola akun internal dan hak aksesnya. Kontak penerima WhatsApp tidak memerlukan akun aplikasi.">
        <x-slot name="actions">
            <button type="button" wire:click="openCreate" class="btn-primary">
                <x-icon name="plus" :size="16" />
                Tambah pengguna
            </button>
        </x-slot>
    </x-page-header>

    <x-settings-tabs />

    <x-flash />

    <section class="grid grid-cols-3 gap-3" aria-label="Ringkasan pengguna">
        <div class="card-flat p-4">
            <p class="micro-label">Total pengguna</p>
            <p class="stat-value mt-1">{{ $counts['total'] }}</p>
        </div>
        <div class="card-flat p-4">
            <p class="micro-label">Aktif</p>
            <p class="stat-value mt-1 text-success">{{ $counts['active'] }}</p>
        </div>
        <div class="card-flat p-4">
            <p class="micro-label">Admin</p>
            <p class="stat-value mt-1">{{ $counts['admin'] }}</p>
        </div>
    </section>

    <div class="card overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-3 sm:px-5">
            <div>
                <h2 class="section-title">Daftar pengguna</h2>
                <p class="mt-0.5 text-[11px] text-muted">{{ $users->total() }} akun pada filter ini.</p>
            </div>
            <div class="flex w-full flex-wrap gap-2 sm:w-auto">
                <div class="relative min-w-0 flex-1 sm:w-56">
                    <label for="user-search" class="sr-only">Cari pengguna</label>
                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted"><x-icon name="search" :size="15" /></span>
                    <input id="user-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Nama, email, telepon..." class="field field-sm pl-9" />
                </div>
                <div>
                    <label for="user-role" class="sr-only">Filter peran</label>
                    <select id="user-role" wire:model.live="roleFilter" class="field field-sm w-full sm:w-36">
                        <option value="">Semua peran</option>
                        @foreach ($roles as $roleOption)
                            <option value="{{ $roleOption->value }}">{{ $roleOption->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="user-status" class="sr-only">Filter status</label>
                    <select id="user-status" wire:model.live="statusFilter" class="field field-sm w-full sm:w-32">
                        <option value="">Semua status</option>
                        <option value="active">Aktif</option>
                        <option value="inactive">Nonaktif</option>
                    </select>
                </div>
            </div>
        </div>

        @if ($users->isEmpty())
            <div class="p-5">
                <x-empty-state icon="users" title="Tidak ada pengguna" description="Ubah filter pencarian atau tambahkan akun internal baru.">
                    <x-slot name="action">
                        <button type="button" wire:click="openCreate" class="btn-primary">Tambah pengguna</button>
                    </x-slot>
                </x-empty-state>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[860px] border-collapse text-left">
                    <thead class="bg-surface-2/70">
                        <tr>
                            <th class="table-head">Pengguna</th>
                            <th class="table-head">Peran</th>
                            <th class="table-head">Izin terkait</th>
                            <th class="table-head">Login terakhir</th>
                            <th class="table-head">Status</th>
                            <th class="table-head text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr class="border-t border-line/70 transition hover:bg-surface-2/40">
                                <td class="table-cell">
                                    <div class="flex items-center gap-2.5">
                                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-sky-700 to-sky-900 text-[10px] font-bold text-white">{{ $user->initials() }}</span>
                                        <div class="min-w-0">
                                            <p class="flex items-center gap-1.5 text-[12.5px] font-bold text-ink">
                                                <span class="max-w-[190px] truncate">{{ $user->name }}</span>
                                                @if ($user->id === auth()->id())
                                                    <span class="pill pill-info">Anda</span>
                                                @endif
                                            </p>
                                            <p class="max-w-[220px] truncate text-[10.5px] text-muted">{{ $user->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="table-cell">
                                    <span class="rounded-full px-2.5 py-1 text-[10.5px] font-semibold {{ $rolePill($user->role) }}">{{ $user->role->label() }}</span>
                                </td>
                                <td class="table-cell">
                                    <span class="text-[11px] text-ink-2">{{ $user->permits_count }} sebagai PIC</span>
                                    <span class="block text-[10px] text-muted">{{ $user->created_permits_count }} izin dibuat</span>
                                </td>
                                <td class="table-cell">
                                    <span class="text-[11px] text-ink-2">{{ $user->last_login_at?->locale('id')->diffForHumans() ?? 'Belum pernah' }}</span>
                                </td>
                                <td class="table-cell">
                                    <span class="pill {{ $user->is_active ? 'pill-success' : 'pill-neutral' }}">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                </td>
                                <td class="table-cell">
                                    <div class="flex justify-end gap-1">
                                        <button type="button" wire:click="openEdit({{ $user->id }})" class="icon-button" aria-label="Ubah {{ $user->name }}" title="Ubah dan atur ulang kata sandi">
                                            <x-icon name="edit" :size="15" />
                                        </button>
                                        @if ($user->id !== auth()->id())
                                            <button type="button" wire:click="toggleActive({{ $user->id }})" class="icon-button" aria-label="{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }} {{ $user->name }}" title="{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                                <x-icon name="{{ $user->is_active ? 'pause' : 'play' }}" :size="15" />
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-line px-4 py-3">{{ $users->links() }}</div>
        @endif
    </div>

    <section class="card p-4 sm:p-5" aria-labelledby="role-matrix-title">
        <h2 id="role-matrix-title" class="card-title">Matriks akses</h2>
        <div class="mt-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-line bg-surface-2/50 p-3">
                <p class="text-[12px] font-bold text-ink">Admin</p>
                <p class="mt-1 text-[10.5px] leading-4 text-muted">Semua fitur: izin, kategori, kontak, aturan, template, pengiriman, pengguna, WAHA, pengaturan, audit.</p>
            </div>
            <div class="rounded-2xl border border-line bg-surface-2/50 p-3">
                <p class="text-[12px] font-bold text-ink">Sekretaris</p>
                <p class="mt-1 text-[10.5px] leading-4 text-muted">Mengelola izin dan kontak, melihat riwayat pengiriman dan laporan. Tidak dapat mengubah pengaturan sistem.</p>
            </div>
            <div class="rounded-2xl border border-line bg-surface-2/50 p-3">
                <p class="text-[12px] font-bold text-ink">HRD</p>
                <p class="mt-1 text-[10.5px] leading-4 text-muted">Mengelola izin dan kontak, melihat laporan. Riwayat pengiriman dan pengaturan tidak dapat diakses.</p>
            </div>
            <div class="rounded-2xl border border-line bg-surface-2/50 p-3">
                <p class="text-[12px] font-bold text-ink">Viewer</p>
                <p class="mt-1 text-[10.5px] leading-4 text-muted">Hanya melihat dashboard, daftar izin, detail izin, dan laporan. Tidak dapat mengubah data apa pun.</p>
            </div>
        </div>
    </section>

    @if ($showForm)
        <div class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-labelledby="user-form-title" x-on:keydown.escape.window="$wire.closeForm()">
            <div class="absolute inset-0 bg-ink/45 backdrop-blur-sm" wire:click="closeForm" aria-hidden="true"></div>
            <div class="absolute inset-y-0 right-0 flex w-full max-w-lg flex-col overflow-y-auto bg-surface p-5 shadow-float sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 id="user-form-title" class="text-[18px] font-bold tracking-[-0.02em] text-ink">{{ $editingId ? 'Ubah pengguna' : 'Tambah pengguna' }}</h2>
                        <p class="mt-0.5 text-[11.5px] text-muted">{{ $editingId ? 'Kosongkan kata sandi jika tidak ingin mengubahnya.' : 'Akun baru dapat langsung digunakan setelah disimpan.' }}</p>
                    </div>
                    <button type="button" wire:click="closeForm" class="icon-button" aria-label="Tutup formulir"><x-icon name="x" :size="18" /></button>
                </div>

                <form wire:submit="save" class="mt-5 space-y-4">
                    <div>
                        <label for="user-name" class="field-label">Nama lengkap <span class="text-danger">*</span></label>
                        <input id="user-name" type="text" wire:model.blur="name" required maxlength="100" class="field" data-autofocus />
                        @error('name') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label for="user-email" class="field-label">Email <span class="text-danger">*</span></label>
                            <input id="user-email" type="email" wire:model.blur="email" required maxlength="150" class="field" autocomplete="off" />
                            @error('email') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="user-phone" class="field-label">Telepon</label>
                            <input id="user-phone" type="tel" wire:model.blur="phone" maxlength="30" class="field" />
                            @error('phone') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="user-role-field" class="field-label">Peran <span class="text-danger">*</span></label>
                        <select id="user-role-field" wire:model="role" class="field" required>
                            @foreach ($roles as $roleOption)
                                <option value="{{ $roleOption->value }}">{{ $roleOption->label() }}</option>
                            @endforeach
                        </select>
                        <p class="field-hint">Peran menentukan menu dan tindakan yang dapat diakses.</p>
                        @error('role') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div class="rounded-xl border border-line bg-surface-2/60 p-3">
                        <p class="text-[12px] font-semibold text-ink">{{ $editingId ? 'Atur ulang kata sandi' : 'Kata sandi awal' }}</p>
                        <p class="mt-0.5 text-[10.5px] text-muted">{{ $editingId ? 'Opsional. Isi hanya jika pengguna lupa kata sandi.' : 'Minimal 10 karakter dengan huruf besar, huruf kecil, dan angka.' }}</p>
                        <div class="mt-2.5 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label for="user-password" class="field-label text-[11px]">Kata sandi {{ $editingId ? 'baru' : '' }}</label>
                                <input id="user-password" type="password" wire:model.blur="password" class="field" autocomplete="new-password" />
                                @error('password') <p class="field-hint !text-danger">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="user-password-confirmation" class="field-label text-[11px]">Ulangi kata sandi</label>
                                <input id="user-password-confirmation" type="password" wire:model.blur="password_confirmation" class="field" autocomplete="new-password" />
                            </div>
                        </div>
                    </div>

                    <label class="flex min-h-11 cursor-pointer items-center gap-2.5 rounded-xl bg-surface-2 px-3 text-[12px] font-semibold text-ink">
                        <input type="checkbox" wire:model="isActive" class="h-4 w-4 rounded border-line-strong text-primary focus:ring-primary/30" />
                        Akun aktif dan dapat masuk ke aplikasi
                    </label>
                    @error('isActive') <p class="field-hint !text-danger">{{ $message }}</p> @enderror

                    <div class="flex flex-col-reverse gap-2 border-t border-line pt-4 sm:flex-row sm:justify-end">
                        <button type="button" wire:click="closeForm" class="btn-secondary">Batal</button>
                        <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">
                            <span wire:loading.remove wire:target="save">Simpan pengguna</span>
                            <span wire:loading wire:target="save">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>

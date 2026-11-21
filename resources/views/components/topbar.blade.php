@php
    $user = auth()->user();
    $unread = $user->unreadNotifications()->count();
    $sidebarClosed = request()->cookie('siperiz_sidebar') === 'closed';
@endphp

<header class="mx-auto w-full max-w-[1800px] px-4 pb-2 pt-5 sm:px-6 lg:px-8">
    <div class="flex items-center gap-3">
        <button type="button" class="icon-button lg:hidden" x-on:click="mobileOpen = true" aria-label="Buka navigasi">
            <x-icon name="menu" :size="20" />
        </button>

        <button
            type="button"
            data-sidebar-toggle
            class="icon-button hidden lg:inline-flex"
            aria-controls="app-sidebar"
            aria-expanded="{{ $sidebarClosed ? 'false' : 'true' }}"
            aria-label="{{ $sidebarClosed ? 'Tampilkan navigasi' : 'Sembunyikan navigasi' }}"
        >
            <x-icon name="menu" :size="20" />
        </button>

        <div class="ml-auto flex items-center gap-2">
            <form method="GET" action="{{ route('permits.index') }}" role="search" class="relative hidden md:block">
                <label for="global-search" class="sr-only">Cari izin</label>
                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted">
                    <x-icon name="search" :size="16" />
                </span>
                <input
                    id="global-search"
                    type="search"
                    name="q"
                    value="{{ request()->routeIs('permits.index') ? request('q') : '' }}"
                    placeholder="Cari izin..."
                    class="field h-11 min-h-11 w-[210px] rounded-full pl-9 pr-3 text-[12px] xl:w-[250px]"
                />
            </form>

            <button
                type="button"
                x-data="{ dark: document.documentElement.classList.contains('dark') }"
                x-on:click="dark = !dark; document.documentElement.classList.toggle('dark', dark); localStorage.setItem('theme', dark ? 'dark' : 'light')"
                :aria-label="dark ? 'Aktifkan tema terang' : 'Aktifkan tema gelap'"
                class="icon-button"
            >
                <x-icon name="sun" x-show="!dark" :size="18" />
                <x-icon name="moon" x-show="dark" x-cloak :size="18" />
            </button>

            <a href="{{ route('notifications.inbox') }}" class="icon-button relative" aria-label="Notifikasi saya{{ $unread ? ', '.$unread.' belum dibaca' : '' }}">
                <x-icon name="bell" :size="18" />
                @if ($unread)
                    <span class="absolute -right-0.5 -top-0.5 flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-danger px-1 text-[9px] font-bold text-white ring-2 ring-page">{{ $unread > 9 ? '9+' : $unread }}</span>
                @endif
            </a>

            <span class="pill hidden bg-info-soft text-info sm:inline-flex">{{ $user->role->label() }}</span>

            <div x-data="{ open: false }" x-on:click.outside="open = false" x-on:keydown.escape.window="open = false" class="relative">
                <button
                    type="button"
                    x-on:click="open = !open"
                    class="flex min-h-11 items-center gap-2 rounded-full border border-line/80 bg-surface p-1 pr-2.5 shadow-sm transition hover:border-primary/40"
                    :aria-expanded="open"
                    aria-haspopup="true"
                    aria-label="Menu profil"
                >
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-sky-700 to-sky-900 text-[10px] font-bold text-white">{{ $user->initials() }}</span>
                    <span class="hidden max-w-[110px] truncate text-[11.5px] font-semibold text-ink xl:block">{{ $user->name }}</span>
                    <x-icon name="chevron-down" :size="14" class="text-muted" />
                </button>

                <div
                    x-show="open"
                    x-cloak
                    x-transition
                    class="absolute right-0 z-50 mt-2 w-60 rounded-2xl border border-line bg-surface p-2 shadow-float"
                >
                    <div class="border-b border-line px-3 py-2.5">
                        <p class="truncate text-[13px] font-bold text-ink">{{ $user->name }}</p>
                        <p class="truncate text-[11px] text-muted">{{ $user->email }}</p>
                        <span class="mt-1.5 inline-flex rounded-full bg-info-soft px-2 py-0.5 text-[10px] font-semibold text-info">{{ $user->role->label() }}</span>
                    </div>
                    <a href="{{ route('profile') }}" class="mt-1 flex min-h-11 items-center gap-2.5 rounded-xl px-3 text-[12px] font-semibold text-ink-2 hover:bg-primary-soft/60 hover:text-primary">
                        <x-icon name="user" :size="16" />
                        Profil dan kata sandi
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex min-h-11 w-full items-center gap-2.5 rounded-xl px-3 text-[12px] font-semibold text-danger hover:bg-danger-soft/70">
                            <x-icon name="logout" :size="16" />
                            Keluar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <form method="GET" action="{{ route('permits.index') }}" role="search" class="relative mt-3 md:hidden">
        <label for="mobile-search" class="sr-only">Cari izin</label>
        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted">
            <x-icon name="search" :size="16" />
        </span>
        <input id="mobile-search" type="search" name="q" value="{{ request()->routeIs('permits.index') ? request('q') : '' }}" placeholder="Cari izin, nomor, penerbit, atau PIC..." class="field rounded-full pl-9 text-[12px]" />
    </form>
</header>

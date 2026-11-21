@php
    $user = auth()->user();

    $primary = [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'dashboard', 'active' => ['dashboard']],
        ['label' => 'Izin', 'route' => 'permits.index', 'icon' => 'file-check', 'active' => ['permits.*']],
    ];

    if ($user?->isAdmin()) {
        $primary[] = ['label' => 'Kategori', 'route' => 'categories.index', 'icon' => 'tags', 'active' => ['categories.*']];
    }

    if ($user?->canManageContacts()) {
        $primary[] = ['label' => 'Notifikasi', 'route' => 'contacts.index', 'icon' => 'bell', 'active' => ['contacts.*', 'notifications.*']];
    }

    $primary[] = ['label' => 'Laporan', 'route' => 'reports.index', 'icon' => 'chart', 'active' => ['reports.*']];

    if ($user?->isAdmin()) {
        $primary[] = ['label' => 'Pengaturan', 'route' => 'settings.waha', 'icon' => 'settings', 'active' => ['settings.*', 'audit.*']];
    }

    $mobileGroups = [
        ['title' => null, 'items' => array_values(array_filter([
            ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'dashboard', 'active' => ['dashboard']],
            ['label' => 'Izin', 'route' => 'permits.index', 'icon' => 'file-check', 'active' => ['permits.*']],
            $user?->isAdmin() ? ['label' => 'Kategori izin', 'route' => 'categories.index', 'icon' => 'tags', 'active' => ['categories.*']] : null,
            ['label' => 'Laporan', 'route' => 'reports.index', 'icon' => 'chart', 'active' => ['reports.*']],
        ]))],
        ['title' => 'Notifikasi', 'items' => array_values(array_filter([
            $user?->canManageContacts() ? ['label' => 'Kontak', 'route' => 'contacts.index', 'icon' => 'users', 'active' => ['contacts.*']] : null,
            $user?->isAdmin() ? ['label' => 'Aturan reminder', 'route' => 'notifications.rules', 'icon' => 'clock', 'active' => ['notifications.rules']] : null,
            $user?->isAdmin() ? ['label' => 'Template pesan', 'route' => 'notifications.templates', 'icon' => 'message-square', 'active' => ['notifications.templates']] : null,
            $user && \Illuminate\Support\Facades\Gate::allows('view-deliveries') ? ['label' => 'Riwayat pengiriman', 'route' => 'notifications.deliveries', 'icon' => 'send', 'active' => ['notifications.deliveries']] : null,
        ]))],
        ['title' => 'Pengaturan', 'items' => array_values(array_filter([
            $user?->isAdmin() ? ['label' => 'WAHA', 'route' => 'settings.waha', 'icon' => 'whatsapp', 'active' => ['settings.waha']] : null,
            $user?->isAdmin() ? ['label' => 'Pengguna & akses', 'route' => 'settings.users', 'icon' => 'shield', 'active' => ['settings.users']] : null,
            $user?->isAdmin() ? ['label' => 'Aplikasi', 'route' => 'settings.application', 'icon' => 'settings', 'active' => ['settings.application']] : null,
            $user?->isAdmin() ? ['label' => 'Audit log', 'route' => 'audit.index', 'icon' => 'clipboard', 'active' => ['audit.*']] : null,
        ]))],
    ];
@endphp

{{-- Desktop floating rail --}}
<aside id="app-sidebar" class="app-rail fixed inset-y-4 left-4 z-40 hidden w-[76px] flex-col items-center rounded-[30px] border border-line/80 bg-surface/95 py-4 shadow-float backdrop-blur lg:flex" aria-label="Navigasi utama">
    <a href="{{ route('dashboard') }}" class="rounded-2xl" aria-label="SIPERIZ, dashboard">
        <x-brand class="h-11 w-11" />
    </a>

    <nav class="mt-6 flex flex-1 flex-col items-center gap-1.5">
        @foreach ($primary as $item)
            @php $isActive = request()->routeIs($item['active']); @endphp
            <a
                href="{{ route($item['route']) }}"
                wire:navigate
                class="group relative flex h-11 w-11 items-center justify-center rounded-2xl transition duration-150 {{ $isActive ? 'bg-primary text-on-primary shadow-sm' : 'text-ink-2 hover:bg-primary-soft/70 hover:text-primary' }}"
                @if ($isActive) aria-current="page" @endif
                title="{{ $item['label'] }}"
            >
                <x-icon :name="$item['icon']" :size="20" />
                <span class="pointer-events-none absolute left-full z-50 ml-3 hidden whitespace-nowrap rounded-xl bg-ink px-2.5 py-1.5 text-[11px] font-semibold text-surface shadow-lg group-hover:block group-focus-visible:block">{{ $item['label'] }}</span>
                <span class="sr-only">{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>

    <div class="mt-4 flex flex-col items-center gap-2 border-t border-line pt-4">
        <a href="{{ route('notifications.inbox') }}" class="group relative flex h-11 w-11 items-center justify-center rounded-2xl text-ink-2 transition hover:bg-primary-soft/70 hover:text-primary" title="Notifikasi saya">
            <x-icon name="inbox" :size="19" />
            @php $unread = auth()->user()->unreadNotifications()->count(); @endphp
            @if ($unread > 0)
                <span class="absolute right-1.5 top-1.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-danger px-1 text-[9px] font-bold text-white">{{ $unread > 9 ? '9+' : $unread }}</span>
            @endif
            <span class="pointer-events-none absolute left-full z-50 ml-3 hidden whitespace-nowrap rounded-xl bg-ink px-2.5 py-1.5 text-[11px] font-semibold text-surface shadow-lg group-hover:block">Notifikasi saya</span>
            <span class="sr-only">Notifikasi saya</span>
        </a>
        <a href="{{ route('profile') }}" class="group relative flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-br from-sky-700 to-sky-900 text-[11px] font-bold text-white shadow-sm" title="Profil {{ auth()->user()->name }}">
            {{ auth()->user()->initials() }}
            <span class="pointer-events-none absolute left-full z-50 ml-3 hidden whitespace-nowrap rounded-xl bg-ink px-2.5 py-1.5 text-[11px] font-semibold text-surface shadow-lg group-hover:block">Profil</span>
        </a>
    </div>
</aside>

{{-- Mobile slide-over --}}
<div x-show="mobileOpen" x-cloak class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="Navigasi utama">
    <div
        x-show="mobileOpen"
        x-transition.opacity
        class="absolute inset-0 bg-ink/45 backdrop-blur-sm"
        x-on:click="mobileOpen = false"
        aria-hidden="true"
    ></div>

    <aside
        x-show="mobileOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        class="absolute inset-y-0 left-0 flex w-[286px] max-w-[85vw] flex-col overflow-y-auto border-r border-line bg-surface px-4 py-5 shadow-float"
    >
        <div class="flex items-center justify-between">
            <a href="{{ route('dashboard') }}" class="rounded-xl" aria-label="SIPERIZ, dashboard">
                <x-brand :with-text="true" class="h-10 w-10" />
            </a>
            <button type="button" class="icon-button" x-on:click="mobileOpen = false" aria-label="Tutup navigasi">
                <x-icon name="x" :size="18" />
            </button>
        </div>

        <nav class="mt-6 flex flex-1 flex-col gap-5" aria-label="Navigasi seluler">
            @foreach ($mobileGroups as $group)
                @if (count($group['items']) > 0)
                    <div>
                        @if ($group['title'])
                            <p class="micro-label mb-1.5 px-3 uppercase tracking-[0.12em]">{{ $group['title'] }}</p>
                        @endif
                        <div class="space-y-0.5">
                            @foreach ($group['items'] as $item)
                                @php $isActive = request()->routeIs($item['active']); @endphp
                                <a
                                    href="{{ route($item['route']) }}"
                                    wire:navigate
                                    class="flex min-h-11 items-center gap-3 rounded-xl px-3 text-[13px] font-semibold transition {{ $isActive ? 'bg-primary text-on-primary' : 'text-ink-2 hover:bg-primary-soft/70 hover:text-primary' }}"
                                    @if ($isActive) aria-current="page" @endif
                                >
                                    <x-icon :name="$item['icon']" :size="18" />
                                    {{ $item['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </nav>

        <div class="mt-6 border-t border-line pt-4">
            <a href="{{ route('profile') }}" class="flex items-center gap-3 rounded-xl px-2 py-2 hover:bg-primary-soft/60">
                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-sky-700 to-sky-900 text-[11px] font-bold text-white">{{ auth()->user()->initials() }}</span>
                <span class="min-w-0">
                    <span class="block truncate text-[13px] font-bold text-ink">{{ auth()->user()->name }}</span>
                    <span class="block truncate text-[11px] text-muted">{{ auth()->user()->role->label() }}</span>
                </span>
            </a>
            <form method="POST" action="{{ route('logout') }}" class="mt-2">
                @csrf
                <button type="submit" class="btn-ghost w-full justify-start px-3">
                    <x-icon name="logout" :size="17" />
                    Keluar
                </button>
            </form>
        </div>
    </aside>
</div>

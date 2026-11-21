@php
    $tabs = array_values(array_filter([
        \Illuminate\Support\Facades\Gate::allows('manage-contacts') ? ['label' => 'Kontak', 'route' => 'contacts.index', 'active' => ['contacts.*']] : null,
        \Illuminate\Support\Facades\Gate::allows('manage-reminders') ? ['label' => 'Aturan reminder', 'route' => 'notifications.rules', 'active' => ['notifications.rules']] : null,
        \Illuminate\Support\Facades\Gate::allows('manage-reminders') ? ['label' => 'Template pesan', 'route' => 'notifications.templates', 'active' => ['notifications.templates']] : null,
        \Illuminate\Support\Facades\Gate::allows('view-deliveries') ? ['label' => 'Riwayat pengiriman', 'route' => 'notifications.deliveries', 'active' => ['notifications.deliveries']] : null,
    ]));
@endphp

@if (count($tabs) > 1)
    <nav class="-mx-1 flex flex-wrap gap-1 pb-1 sm:flex-nowrap sm:overflow-x-auto" aria-label="Menu notifikasi">
        @foreach ($tabs as $tab)
            <a
                href="{{ route($tab['route']) }}"
                wire:navigate
                class="inline-flex min-h-11 shrink-0 items-center rounded-full px-3.5 text-[12px] font-semibold transition {{ request()->routeIs($tab['active']) ? 'bg-primary text-on-primary shadow-sm' : 'text-ink-2 hover:bg-primary-soft/70 hover:text-primary' }}"
                @if (request()->routeIs($tab['active'])) aria-current="page" @endif
            >
                {{ $tab['label'] }}
            </a>
        @endforeach
    </nav>
@endif

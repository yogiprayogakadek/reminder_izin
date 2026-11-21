@php $user = auth()->user(); @endphp

<div class="space-y-5">
    <x-page-header title="Notifikasi saya" description="Pengingat yang ditujukan ke akun Anda. Tetap tersedia meskipun pengiriman WhatsApp gagal.">
        <x-slot name="actions">
            @if ($user->unreadNotifications()->exists())
                <button type="button" wire:click="markAllRead" class="btn-secondary">Tandai semua dibaca</button>
            @endif
        </x-slot>
    </x-page-header>

    <div class="card overflow-hidden">
        @forelse ($notifications as $notification)
            @php $data = $notification->data; @endphp
            <button
                type="button"
                wire:click="markRead('{{ $notification->id }}')"
                class="flex w-full items-start gap-3 border-b border-line/70 px-4 py-4 text-left transition last:border-0 hover:bg-primary-soft/40 sm:px-5"
            >
                <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $notification->read_at ? 'bg-neutral-soft text-ink-2' : 'bg-primary-soft text-primary' }}">
                    <x-icon name="bell" :size="17" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="flex flex-wrap items-center gap-2">
                        <span class="text-[12.5px] font-bold text-ink">{{ $data['title'] ?? 'Pengingat izin' }}</span>
                        @if (! $notification->read_at)
                            <span class="rounded-full bg-primary-soft px-2 py-0.5 text-[9px] font-bold uppercase tracking-wide text-primary">Baru</span>
                        @endif
                    </span>
                    <span class="mt-0.5 block text-[12px] leading-5 text-ink-2">{{ $data['body'] ?? '' }}</span>
                    <span class="mt-1 block text-[10.5px] text-muted">{{ $notification->created_at->locale('id')->diffForHumans() }}</span>
                </span>
                <x-icon name="chevron-right" :size="16" class="mt-2 text-muted" />
            </button>
        @empty
            <div class="p-5">
                <x-empty-state
                    icon="inbox"
                    title="Belum ada notifikasi"
                    description="Saat reminder izin dikirim ke kontak yang terhubung dengan akun Anda, riwayatnya muncul di sini."
                />
            </div>
        @endforelse
    </div>

    @if ($notifications->hasPages())
        <div>{{ $notifications->links() }}</div>
    @endif
</div>

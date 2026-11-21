@props(['title', 'description', 'icon' => 'inbox'])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center rounded-panel border border-dashed border-line-strong/80 bg-surface-2/60 px-6 py-10 text-center']) }}>
    <span class="mb-3 inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-primary-soft text-primary">
        <x-icon :name="$icon" :size="22" />
    </span>
    <p class="text-[14px] font-bold text-ink">{{ $title }}</p>
    <p class="mt-1 max-w-sm text-[12px] leading-5 text-muted">{{ $description }}</p>
    @if (isset($action))
        <div class="mt-4">{{ $action }}</div>
    @endif
</div>

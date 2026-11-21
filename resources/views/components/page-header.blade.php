@props(['title', 'description' => null])

<div {{ $attributes->merge(['class' => 'flex flex-wrap items-end justify-between gap-3']) }}>
    <div class="min-w-0">
        <h1 class="text-[23px] font-bold leading-tight tracking-[-0.03em] text-ink sm:text-[27px]">{{ $title }}</h1>
        @if ($description)
            <p class="mt-1 max-w-2xl text-[12.5px] leading-5 text-muted">{{ $description }}</p>
        @endif
    </div>
    @if (isset($actions))
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endif
</div>

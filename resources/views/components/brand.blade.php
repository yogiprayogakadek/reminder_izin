@props(['class' => 'h-10 w-10', 'withText' => false])

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <span class="{{ $class }} relative inline-flex shrink-0 items-center justify-center overflow-hidden rounded-[14px] bg-gradient-to-br from-sky-500 to-sky-800 text-white shadow-sm">
        <svg viewBox="0 0 32 32" fill="none" class="h-[62%] w-[62%]" aria-hidden="true">
            <path d="M16 4.5 25 8v7.4c0 5.6-3.5 9.4-9 11.6-5.5-2.2-9-6-9-11.6V8l9-3.5Z" stroke="currentColor" stroke-width="2.1" stroke-linejoin="round" />
            <path d="m11.8 15.8 2.8 2.9 5.7-6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
    </span>
    @if ($withText)
        <span class="text-[17px] font-bold tracking-[-0.03em] text-ink">SIPERIZ</span>
    @endif
</span>

@props(['status'])

@php
    $tone = $status->tone();
    $classes = match ($tone) {
        'success' => 'bg-success-soft text-success',
        'warning' => 'bg-warning-soft text-warning',
        'danger' => 'bg-danger-soft text-danger',
        'info' => 'bg-info-soft text-info',
        default => 'bg-neutral-soft text-ink-2',
    };
@endphp

<span {{ $attributes->merge(['class' => "pill $classes"]) }}>
    <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
    {{ $status->label() }}
</span>

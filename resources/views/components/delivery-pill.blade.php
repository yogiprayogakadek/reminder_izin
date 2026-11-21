@props(['status'])

@php
    $classes = match ($status->tone()) {
        'success' => 'bg-success-soft text-success',
        'warning' => 'bg-warning-soft text-warning',
        'danger' => 'bg-danger-soft text-danger',
        'info' => 'bg-info-soft text-info',
        default => 'bg-neutral-soft text-ink-2',
    };
@endphp

<span {{ $attributes->merge(['class' => "pill $classes"]) }}>
    {{ $status->label() }}
</span>

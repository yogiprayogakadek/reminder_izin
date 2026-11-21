@if (session('success'))
    <div class="flex items-start gap-2.5 rounded-2xl border border-success/20 bg-success-soft px-4 py-3 text-success" role="status">
        <x-icon name="check-circle" :size="17" class="mt-0.5" />
        <p class="text-[12px] font-semibold">{{ session('success') }}</p>
    </div>
@endif

@if (session('error'))
    <div class="flex items-start gap-2.5 rounded-2xl border border-danger/20 bg-danger-soft px-4 py-3 text-danger" role="alert">
        <x-icon name="alert" :size="17" class="mt-0.5" />
        <p class="text-[12px] font-semibold">{{ session('error') }}</p>
    </div>
@endif

@if (session('warning'))
    <div class="flex items-start gap-2.5 rounded-2xl border border-warning/20 bg-warning-soft px-4 py-3 text-warning" role="status">
        <x-icon name="alert" :size="17" class="mt-0.5" />
        <p class="text-[12px] font-semibold">{{ session('warning') }}</p>
    </div>
@endif

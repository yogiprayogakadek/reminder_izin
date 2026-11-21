<?php

namespace App\Livewire\Reports;

use App\Enums\DeliveryStatus;
use App\Models\Permit;
use App\Models\PermitCategory;
use App\Models\PermitRenewal;
use App\Models\ReminderDelivery;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

#[Title('Laporan')]
class Index extends Component
{
    public string $from = '';

    public string $to = '';

    public string $categoryId = '';

    public function mount(): void
    {
        $this->from = CarbonImmutable::today()->subMonths(11)->startOfMonth()->toDateString();
        $this->to = CarbonImmutable::today()->toDateString();
    }

    public function setPeriod(string $period): void
    {
        $today = CarbonImmutable::today();

        [$this->from, $this->to] = match ($period) {
            'month' => [$today->startOfMonth()->toDateString(), $today->toDateString()],
            'quarter' => [$today->subDays(89)->toDateString(), $today->toDateString()],
            'year' => [$today->startOfYear()->toDateString(), $today->toDateString()],
            default => [$today->subMonths(11)->startOfMonth()->toDateString(), $today->toDateString()],
        };
    }

    public function resetFilters(): void
    {
        $this->categoryId = '';
        $this->setPeriod('twelve');
    }

    protected function period(): array
    {
        try {
            $from = CarbonImmutable::parse($this->from)->startOfDay();
        } catch (Throwable) {
            $from = CarbonImmutable::today()->subMonths(11)->startOfMonth();
        }

        try {
            $to = CarbonImmutable::parse($this->to)->endOfDay();
        } catch (Throwable) {
            $to = CarbonImmutable::today()->endOfDay();
        }

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->startOfDay(), $from->endOfDay()];
        }

        return [$from, $to];
    }

    public function render(): View
    {
        [$from, $to] = $this->period();
        $canSeeDeliveries = Gate::allows('view-deliveries');

        $periodPermits = Permit::query()
            ->whereNull('archived_at')
            ->whereDate('expires_at', '>=', $from->toDateString())
            ->whereDate('expires_at', '<=', $to->toDateString())
            ->when($this->categoryId !== '', fn ($q) => $q->where('category_id', $this->categoryId));

        $renewalQuery = PermitRenewal::query()
            ->whereBetween('created_at', [$from, $to])
            ->when($this->categoryId !== '', fn ($q) => $q->whereHas('permit', fn ($permit) => $permit->where('category_id', $this->categoryId)));

        $statusCounts = Permit::query()
            ->whereNull('archived_at')
            ->when($this->categoryId !== '', fn ($q) => $q->where('category_id', $this->categoryId))
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN renewal_started_at IS NOT NULL THEN 1 ELSE 0 END) as renewing,
                SUM(CASE WHEN renewal_started_at IS NULL AND expires_at < ? THEN 1 ELSE 0 END) as expired,
                SUM(CASE WHEN renewal_started_at IS NULL AND expires_at >= ? AND expires_at <= ? THEN 1 ELSE 0 END) as expiring,
                SUM(CASE WHEN renewal_started_at IS NULL AND expires_at > ? THEN 1 ELSE 0 END) as active
            ', [
                CarbonImmutable::today()->toDateString(),
                CarbonImmutable::today()->toDateString(),
                CarbonImmutable::today()->addDays(30)->toDateString(),
                CarbonImmutable::today()->addDays(30)->toDateString(),
            ])
            ->first();

        $categoryStats = PermitCategory::query()
            ->when($this->categoryId !== '', fn ($q) => $q->whereKey($this->categoryId))
            ->withCount([
                'permits as period_count' => function ($q) use ($from, $to) {
                    $q->whereNull('archived_at')
                        ->whereDate('expires_at', '>=', $from->toDateString())
                        ->whereDate('expires_at', '<=', $to->toDateString());
                },
                'permits as expiring_count' => fn ($q) => $q->whereNull('archived_at')->status('expiring'),
                'permits as expired_count' => fn ($q) => $q->whereNull('archived_at')->status('expired'),
            ])
            ->orderByDesc('period_count')
            ->orderBy('name')
            ->get();

        $maxPeriod = max(1, (int) $categoryStats->max('period_count'));

        $monthlyCounts = (clone $periodPermits)
            ->selectRaw("DATE_FORMAT(expires_at, '%Y-%m') as period, COUNT(*) as total")
            ->groupBy('period')
            ->pluck('total', 'period');

        $months = [];
        $cursor = $from->startOfMonth();
        $lastMonth = $to->startOfMonth();
        $guard = 0;

        while ($cursor->lessThanOrEqualTo($lastMonth) && $guard < 24) {
            $key = $cursor->format('Y-m');
            $months[] = [
                'label' => $cursor->locale('id')->translatedFormat('M Y'),
                'short' => $cursor->locale('id')->translatedFormat('M'),
                'count' => (int) ($monthlyCounts[$key] ?? 0),
                'current' => $cursor->isSameMonth(CarbonImmutable::today()),
            ];
            $cursor = $cursor->addMonth();
            $guard++;
        }

        $maxMonth = max(1, collect($months)->max('count'));

        $expiringSoon = Permit::query()
            ->whereNull('archived_at')
            ->whereNull('renewal_started_at')
            ->status('expiring')
            ->when($this->categoryId !== '', fn ($q) => $q->where('category_id', $this->categoryId))
            ->with(['category', 'pic'])
            ->orderBy('expires_at')
            ->limit(8)
            ->get();

        $recentRenewals = PermitRenewal::query()
            ->with(['permit.category', 'creator'])
            ->whereBetween('created_at', [$from, $to])
            ->when($this->categoryId !== '', fn ($q) => $q->whereHas('permit', fn ($permit) => $permit->where('category_id', $this->categoryId)))
            ->latest()
            ->limit(6)
            ->get();

        $deliverySummary = null;

        if ($canSeeDeliveries) {
            $deliveryBase = ReminderDelivery::query()
                ->whereBetween('created_at', [$from, $to])
                ->when($this->categoryId !== '', fn ($q) => $q->whereHas('permit', fn ($permit) => $permit->where('category_id', $this->categoryId)));

            $deliveryCounts = (clone $deliveryBase)
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            $deliveryTotal = (int) $deliveryCounts->sum();
            $deliverySuccess = (int) ($deliveryCounts[DeliveryStatus::Sent->value] ?? 0)
                + (int) ($deliveryCounts[DeliveryStatus::Delivered->value] ?? 0)
                + (int) ($deliveryCounts[DeliveryStatus::Read->value] ?? 0);

            $deliverySummary = [
                'total' => $deliveryTotal,
                'success' => $deliverySuccess,
                'failed' => (int) ($deliveryCounts[DeliveryStatus::Failed->value] ?? 0),
                'rate' => $deliveryTotal > 0 ? round($deliverySuccess / $deliveryTotal * 100) : null,
                'counts' => $deliveryCounts,
            ];
        }

        $exportBase = array_filter([
            'dari' => $from->toDateString(),
            'sampai' => $to->toDateString(),
            'kategori' => $this->categoryId,
        ], fn ($value) => $value !== '');

        return view('livewire.reports.index', [
            'periodStart' => $from,
            'periodEnd' => $to,
            'categories' => PermitCategory::query()->orderBy('name')->get(['id', 'name', 'color', 'is_active']),
            'periodCount' => (clone $periodPermits)->count(),
            'renewalCount' => $renewalQuery->count(),
            'statusCounts' => $statusCounts,
            'categoryStats' => $categoryStats,
            'maxPeriod' => $maxPeriod,
            'months' => $months,
            'maxMonth' => $maxMonth,
            'expiringSoon' => $expiringSoon,
            'recentRenewals' => $recentRenewals,
            'deliverySummary' => $deliverySummary,
            'canSeeDeliveries' => $canSeeDeliveries,
            'permitExportUrl' => route('reports.permits.export', $exportBase),
            'deliveryExportUrl' => $canSeeDeliveries
                ? route('reports.deliveries.export', array_filter([
                    'from' => $from->toDateString(),
                    'to' => $to->toDateString(),
                ], fn ($value) => $value !== ''))
                : null,
        ]);
    }
}

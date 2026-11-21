<?php

namespace App\Services\Reminders;

use App\Enums\DeliveryStatus;
use App\Jobs\SendReminderJob;
use App\Models\Contact;
use App\Models\Holiday;
use App\Models\Permit;
use App\Models\ReminderDelivery;
use App\Models\ReminderRule;
use App\Models\ReminderTemplate;
use App\Models\Setting;
use App\Models\WahaSetting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class ReminderDispatcher
{
    public function dispatchDue(?CarbonImmutable $today = null): array
    {
        Setting::applyRuntime();

        $today = ($today ?? CarbonImmutable::now())->startOfDay();
        $now = CarbonImmutable::now();

        $summary = [
            'created' => 0,
            'duplicate' => 0,
            'skipped' => 0,
            'reason' => null,
        ];

        if (! $this->isSendingDay($today)) {
            $summary['reason'] = 'Hari ini bukan hari pengiriman.';

            return $summary;
        }

        $waha = WahaSetting::current();

        if (! $waha->is_enabled || ! $waha->isConfigured()) {
            $summary['reason'] = 'WAHA belum aktif atau belum dikonfigurasi.';

            return $summary;
        }

        $createdToday = ReminderDelivery::query()
            ->whereDate('created_at', $today->toDateString())
            ->whereNot('status', DeliveryStatus::Cancelled->value)
            ->count();

        $remainingQuota = max(0, $waha->daily_limit - $createdToday);

        if ($remainingQuota === 0) {
            $summary['reason'] = 'Batas harian pengiriman sudah tercapai.';

            return $summary;
        }

        $templates = [];

        $permits = Permit::query()
            ->whereNull('archived_at')
            ->whereNull('renewal_started_at')
            ->where('reminder_enabled', true)
            ->where(function ($query) use ($today) {
                $query->whereNull('reminder_paused_until')
                    ->orWhereDate('reminder_paused_until', '<', $today->toDateString());
            })
            ->with(['category.recipients', 'recipients', 'pic'])
            ->get();

        foreach ($permits as $permit) {
            if ($remainingQuota <= 0) {
                break;
            }

            $rules = ReminderRule::forCategory($permit->category_id);

            if ($rules->isEmpty()) {
                continue;
            }

            if ($permit->expires_at->startOfDay()->gte($today)) {
                $remainingQuota -= $this->dispatchApproaching($permit, $rules, $today, $now, $templates, $summary, $remainingQuota);
            } else {
                $remainingQuota -= $this->dispatchOverdue($permit, $rules, $today, $now, $templates, $summary, $remainingQuota);
            }
        }

        return $summary;
    }

    /**
     * @param  Collection<int, ReminderRule>  $rules
     */
    protected function dispatchApproaching(
        Permit $permit,
        Collection $rules,
        CarbonImmutable $today,
        CarbonImmutable $now,
        array &$templates,
        array &$summary,
        int $remainingQuota,
    ): int {
        $due = $rules
            ->filter(fn (ReminderRule $rule) => $rule->thresholdDate($permit->expires_at)->lte($today))
            ->sortByDesc(fn (ReminderRule $rule) => $rule->thresholdDate($permit->expires_at)->timestamp)
            ->values();

        if ($due->isEmpty()) {
            return 0;
        }

        /** @var ReminderRule $rule */
        $rule = $due->first();
        $targetDate = $rule->thresholdDate($permit->expires_at);

        if (! $this->sendTimeReached($rule, $now, $targetDate, $today)) {
            return 0;
        }

        $created = 0;

        foreach ($this->contactsFor($permit, $rule) as $contact) {
            if ($created >= $remainingQuota) {
                break;
            }

            $key = "permit:{$permit->id}:cycle:{$permit->expires_at->toDateString()}:rule:{$rule->id}:contact:{$contact->id}";
            $result = $this->createDelivery($permit, $rule, $contact, $key, $templates);

            if ($result === 'created') {
                $summary['created']++;
                $created++;
            } else {
                $summary['duplicate']++;
            }
        }

        return $created;
    }

    /**
     * @param  Collection<int, ReminderRule>  $rules
     */
    protected function dispatchOverdue(
        Permit $permit,
        Collection $rules,
        CarbonImmutable $today,
        CarbonImmutable $now,
        array &$templates,
        array &$summary,
        int $remainingQuota,
    ): int {
        $rule = $rules
            ->filter(fn (ReminderRule $rule) => $rule->repeat_overdue_days && $rule->max_repeats)
            ->sortBy('offset_days')
            ->first();

        if (! $rule) {
            return 0;
        }

        $expires = $permit->expires_at->startOfDay();
        $daysPast = (int) $expires->diffInDays($today);
        $repeatEvery = (int) $rule->repeat_overdue_days;
        $maxRepeats = (int) $rule->max_repeats;

        if ($repeatEvery <= 0 || $maxRepeats <= 0) {
            return 0;
        }

        $repeatNumber = intdiv($daysPast, $repeatEvery);

        if ($repeatNumber < 1 || $repeatNumber > $maxRepeats) {
            return 0;
        }

        $targetDate = $expires->addDays($repeatNumber * $repeatEvery);

        if (! $this->sendTimeReached($rule, $now, $targetDate, $today)) {
            return 0;
        }

        $created = 0;

        foreach ($this->contactsFor($permit, $rule) as $contact) {
            if ($created >= $remainingQuota) {
                break;
            }

            $key = "permit:{$permit->id}:cycle:{$permit->expires_at->toDateString()}:overdue:{$repeatNumber}:contact:{$contact->id}";
            $result = $this->createDelivery($permit, $rule, $contact, $key, $templates);

            if ($result === 'created') {
                $summary['created']++;
                $created++;
            } else {
                $summary['duplicate']++;
            }
        }

        return $created;
    }

    /**
     * @param  array<int, ReminderTemplate|null>  $templates
     */
    protected function createDelivery(
        Permit $permit,
        ReminderRule $rule,
        Contact $contact,
        string $key,
        array &$templates,
    ): string {
        $template = $templates[$permit->category_id] ??= ReminderTemplate::forCategory($permit->category_id);
        $message = app(ReminderMessage::class)->render($permit, $template, $contact);

        $delivery = ReminderDelivery::query()->firstOrCreate(
            ['idempotency_key' => $key],
            [
                'permit_id' => $permit->id,
                'reminder_rule_id' => $rule->id,
                'reminder_template_id' => $template?->id,
                'contact_id' => $contact->id,
                'cycle_key' => $permit->cycleKey(),
                'recipient_phone' => $contact->phone_e164,
                'message_body' => $message,
                'scheduled_for' => now(),
                'status' => DeliveryStatus::Pending,
                'is_manual' => false,
            ],
        );

        if (! $delivery->wasRecentlyCreated) {
            return 'duplicate';
        }

        SendReminderJob::dispatch($delivery->id);

        return 'created';
    }

    /**
     * @return Collection<int, Contact>
     */
    protected function contactsFor(Permit $permit, ReminderRule $rule): Collection
    {
        $contacts = $permit->recipients->filter->is_active;

        if ($contacts->isEmpty()) {
            $contacts = ($permit->category?->recipients ?? collect())->filter->is_active;
        }

        if ($contacts->isEmpty()) {
            $contacts = $rule->recipients->filter->is_active;
        }

        return $contacts->unique('id')->values();
    }

    protected function sendTimeReached(ReminderRule $rule, CarbonImmutable $now, CarbonImmutable $targetDate, CarbonImmutable $today): bool
    {
        if ($targetDate->lt($today)) {
            return true;
        }

        $sendAt = $today->setTimeFromTimeString((string) $rule->send_time);

        return $now->gte($sendAt);
    }

    protected function isSendingDay(CarbonImmutable $today): bool
    {
        if (Setting::get('workdays_only') === '1' && $today->isWeekend()) {
            return false;
        }

        if (Setting::get('skip_holidays') === '1' && Holiday::query()->whereDate('date', $today->toDateString())->exists()) {
            return false;
        }

        return true;
    }
}

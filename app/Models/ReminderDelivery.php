<?php

namespace App\Models;

use App\Enums\DeliveryStatus;
use App\Services\Reminders\ReminderNotifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReminderDelivery extends Model
{
    protected $fillable = [
        'permit_id',
        'reminder_rule_id',
        'reminder_template_id',
        'contact_id',
        'cycle_key',
        'recipient_phone',
        'message_body',
        'scheduled_for',
        'sent_at',
        'status',
        'waha_message_id',
        'error_message',
        'attempts',
        'idempotency_key',
        'is_manual',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_for' => 'datetime',
            'sent_at' => 'datetime',
            'status' => DeliveryStatus::class,
            'attempts' => 'integer',
            'is_manual' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (self $delivery) {
            app(ReminderNotifier::class)->notify($delivery);
        });
    }

    public function permit(): BelongsTo
    {
        return $this->belongsTo(Permit::class);
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(ReminderRule::class, 'reminder_rule_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ReminderTemplate::class, 'reminder_template_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return $status ? $query->where('status', $status) : $query;
    }
}

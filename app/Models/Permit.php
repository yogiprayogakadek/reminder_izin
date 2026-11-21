<?php

namespace App\Models;

use App\Enums\PermitStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Permit extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id',
        'title',
        'number',
        'issuer',
        'issued_at',
        'expires_at',
        'pic_user_id',
        'location',
        'notes',
        'reminder_enabled',
        'reminder_paused_until',
        'renewal_started_at',
        'archived_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'expires_at' => 'date',
            'reminder_enabled' => 'boolean',
            'reminder_paused_until' => 'date',
            'renewal_started_at' => 'datetime',
            'archived_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PermitCategory::class, 'category_id')->withTrashed();
    }

    public function pic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function renewals(): HasMany
    {
        return $this->hasMany(PermitRenewal::class)->latest();
    }

    public function files(): HasMany
    {
        return $this->hasMany(PermitFile::class);
    }

    public function currentFiles(): HasMany
    {
        return $this->files()->whereNull('permit_renewal_id');
    }

    public function recipients(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'permit_recipients');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(ReminderDelivery::class)->latest();
    }

    protected function status(): Attribute
    {
        return Attribute::get(fn (): PermitStatus => PermitStatus::fromDates(
            CarbonImmutable::parse($this->expires_at),
            CarbonImmutable::today(),
            isRenewing: $this->renewal_started_at !== null,
            isArchived: $this->archived_at !== null,
        ));
    }

    protected function daysRemaining(): Attribute
    {
        return Attribute::get(fn (): int => (int) CarbonImmutable::today()
            ->startOfDay()
            ->diffInDays(CarbonImmutable::parse($this->expires_at)->startOfDay(), false));
    }

    protected function isPaused(): Attribute
    {
        return Attribute::get(fn (): bool => $this->reminder_paused_until !== null
            && $this->reminder_paused_until->gte(CarbonImmutable::today()));
    }

    public function cycleKey(): string
    {
        return 'cycle:'.$this->expires_at->toDateString();
    }

    public function remainingLabel(): string
    {
        $days = $this->days_remaining;

        return match (true) {
            $days < 0 => 'Lewat '.abs($days).' hari',
            $days === 0 => 'Kedaluwarsa hari ini',
            $days === 1 => 'Besok',
            default => $days.' hari lagi',
        };
    }

    public function expiryLabel(): string
    {
        return $this->expires_at->locale('id')->translatedFormat('d M Y');
    }

    public function isRemindable(): bool
    {
        return $this->reminder_enabled
            && $this->archived_at === null
            && $this->renewal_started_at === null
            && ! $this->is_paused;
    }

    /**
     * Recipients used by the reminder engine: permit-specific first,
     * then category defaults, then rule recipients.
     */
    public function resolveContacts(): \Illuminate\Support\Collection
    {
        $direct = $this->relationLoaded('recipients')
            ? $this->recipients
            : $this->recipients()->get();

        $contacts = $direct->isNotEmpty()
            ? $direct
            : ($this->category?->recipients()->get() ?? collect());

        return $contacts
            ->where('is_active', true)
            ->unique('id')
            ->values();
    }

    public function scopeNeedsAction(Builder $query): Builder
    {
        return $query
            ->whereNull('archived_at')
            ->where(function (Builder $q) {
                $q->whereDate('expires_at', '<=', CarbonImmutable::today()->addDays(30))
                    ->orWhereNotNull('renewal_started_at');
            });
    }

    public function scopeNotArchived(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        $today = CarbonImmutable::today();
        $warning = $today->addDays(30)->toDateString();

        return match ($status) {
            'active' => $query->whereNull('archived_at')->whereNull('renewal_started_at')
                ->whereDate('expires_at', '>', $warning),
            'expiring' => $query->whereNull('archived_at')->whereNull('renewal_started_at')
                ->whereDate('expires_at', '>=', $today->toDateString())
                ->whereDate('expires_at', '<=', $warning),
            'expired' => $query->whereNull('archived_at')->whereNull('renewal_started_at')
                ->whereDate('expires_at', '<', $today->toDateString()),
            'renewing' => $query->whereNull('archived_at')->whereNotNull('renewal_started_at'),
            'archived' => $query->whereNotNull('archived_at'),
            default => $query,
        };
    }
}

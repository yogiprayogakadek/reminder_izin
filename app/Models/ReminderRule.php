<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReminderRule extends Model
{
    protected $fillable = [
        'category_id',
        'offset_days',
        'send_time',
        'repeat_overdue_days',
        'max_repeats',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'offset_days' => 'integer',
            'repeat_overdue_days' => 'integer',
            'max_repeats' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PermitCategory::class, 'category_id')->withTrashed();
    }

    public function recipients(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'reminder_rule_recipients');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(ReminderDelivery::class);
    }

    public function label(): string
    {
        return $this->offset_days === 0 ? 'Hari-H' : 'H-'.$this->offset_days;
    }

    public function thresholdDate(CarbonInterface $expiresAt): CarbonImmutable
    {
        return CarbonImmutable::parse($expiresAt)->startOfDay()->subDays($this->offset_days);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeGlobal(Builder $query): Builder
    {
        return $query->whereNull('category_id');
    }

    /**
     * Category rules replace the global schedule when the category defines any.
     *
     * @return Collection<int, self>
     */
    public static function forCategory(?int $categoryId): Collection
    {
        if ($categoryId !== null) {
            $categoryRules = static::active()->where('category_id', $categoryId)->orderByDesc('offset_days')->get();

            if ($categoryRules->isNotEmpty()) {
                return $categoryRules;
            }
        }

        return static::active()->whereNull('category_id')->orderByDesc('offset_days')->get();
    }
}

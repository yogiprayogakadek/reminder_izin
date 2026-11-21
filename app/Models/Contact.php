<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'phone_e164',
        'label',
        'user_id',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(PermitCategory::class, 'permit_category_recipients', 'contact_id', 'category_id');
    }

    public function permits(): BelongsToMany
    {
        return $this->belongsToMany(Permit::class, 'permit_recipients');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(ReminderDelivery::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function displayPhone(): string
    {
        $national = str_starts_with($this->phone_e164, '62')
            ? substr($this->phone_e164, 2)
            : $this->phone_e164;

        return '+62 '.trim(chunk_split($national, 4, ' '));
    }

    public function initials(): string
    {
        return collect(explode(' ', trim($this->name)))
            ->filter()
            ->take(2)
            ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }
}

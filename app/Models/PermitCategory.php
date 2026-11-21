<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PermitCategory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'default_validity_months',
        'default_pic_user_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_validity_months' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $category) {
            if (blank($category->slug)) {
                $category->slug = static::uniqueSlug($category->name, $category->id);
            }
        });
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'kategori';
        $slug = $base;
        $suffix = 2;

        while (static::withTrashed()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    public function permits(): HasMany
    {
        return $this->hasMany(Permit::class, 'category_id');
    }

    public function defaultPic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'default_pic_user_id');
    }

    public function recipients(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'permit_category_recipients', 'category_id', 'contact_id');
    }

    public function reminderRules(): HasMany
    {
        return $this->hasMany(ReminderRule::class, 'category_id');
    }

    public function reminderTemplates(): HasMany
    {
        return $this->hasMany(ReminderTemplate::class, 'category_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}

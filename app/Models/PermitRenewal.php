<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PermitRenewal extends Model
{
    protected $fillable = [
        'permit_id',
        'old_issued_at',
        'old_expires_at',
        'new_issued_at',
        'new_expires_at',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'old_issued_at' => 'date',
            'old_expires_at' => 'date',
            'new_issued_at' => 'date',
            'new_expires_at' => 'date',
        ];
    }

    public function permit(): BelongsTo
    {
        return $this->belongsTo(Permit::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function files(): HasMany
    {
        return $this->hasMany(PermitFile::class);
    }
}

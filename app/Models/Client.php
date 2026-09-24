<?php

namespace App\Models;

use App\Models\Concerns\Syncable;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory, SoftDeletes, Syncable;

    protected $fillable = [
        'uuid',
        'origin_device_id',
        'version',
        'name',
        'phone',
        'referrer_member_id',
        'accumulated_pv',
        'threshold_alerted_at',
        'converted_member_id',
    ];

    protected function casts(): array
    {
        return [
            'accumulated_pv' => 'decimal:2',
            'threshold_alerted_at' => 'datetime',
        ];
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'referrer_member_id');
    }

    public function convertedMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'converted_member_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function canFundIndirectMembership(float $threshold): bool
    {
        return $this->converted_member_id === null
            && (float) $this->accumulated_pv >= $threshold;
    }

    public function scopeEligibleForIndirect($query, float $threshold)
    {
        return $query->whereNull('converted_member_id')
            ->where('accumulated_pv', '>=', $threshold);
    }
}

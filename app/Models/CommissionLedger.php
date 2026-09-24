<?php

namespace App\Models;

use App\Models\Concerns\Syncable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommissionLedger extends Model
{
    use SoftDeletes, Syncable;

    protected $table = 'commission_ledger';

    protected $fillable = [
        'uuid',
        'origin_device_id',
        'version',
        'member_id',
        'type',
        'amount_usd',
        'related_member_id',
        'generation',
        'reward_tier_id',
        'sale_id',
        'status',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_usd' => 'decimal:2',
            'generation' => 'integer',
            'occurred_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function relatedMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'related_member_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function rewardTier(): BelongsTo
    {
        return $this->belongsTo(RewardTier::class);
    }
}

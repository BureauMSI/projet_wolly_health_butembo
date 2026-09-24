<?php

namespace App\Models;

use App\Models\Concerns\Syncable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayoutRequest extends Model
{
    use SoftDeletes, Syncable;

    protected $fillable = [
        'uuid',
        'origin_device_id',
        'version',
        'member_id',
        'amount_usd',
        'status',
        'note',
        'admin_note',
        'reviewed_by',
        'reviewed_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_usd' => 'decimal:2',
            'reviewed_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}

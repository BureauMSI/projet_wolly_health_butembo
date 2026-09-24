<?php

namespace App\Models;

use App\Models\Concerns\Syncable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PvLedger extends Model
{
    use SoftDeletes, Syncable;

    protected $table = 'pv_ledger';

    protected $fillable = [
        'uuid',
        'origin_device_id',
        'version',
        'member_id',
        'source_type',
        'pv_amount',
        'sale_id',
        'client_id',
        'related_member_id',
        'occurred_at',
        'sync_status',
    ];

    protected function casts(): array
    {
        return [
            'pv_amount' => 'decimal:2',
            'occurred_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}

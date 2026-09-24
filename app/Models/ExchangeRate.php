<?php

namespace App\Models;

use App\Models\Concerns\Syncable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExchangeRate extends Model
{
    use SoftDeletes, Syncable;

    protected $fillable = [
        'uuid',
        'origin_device_id',
        'version',
        'currency_code',
        'rate_to_usd',
        'effective_at',
    ];

    protected function casts(): array
    {
        return [
            'rate_to_usd' => 'decimal:8',
            'effective_at' => 'datetime',
        ];
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\Syncable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleItem extends Model
{
    use SoftDeletes, Syncable;

    protected $fillable = [
        'uuid',
        'origin_device_id',
        'version',
        'sale_id',
        'product_id',
        'packing',
        'quantity',
        'unit_price_usd',
        'pv',
        'commission_percent',
        'commission_usd',
        'line_total_usd',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price_usd' => 'decimal:2',
            'pv' => 'decimal:2',
            'commission_percent' => 'decimal:2',
            'commission_usd' => 'decimal:2',
            'line_total_usd' => 'decimal:2',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

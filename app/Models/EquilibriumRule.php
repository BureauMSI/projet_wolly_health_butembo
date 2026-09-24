<?php

namespace App\Models;

use App\Models\Concerns\Syncable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EquilibriumRule extends Model
{
    use SoftDeletes, Syncable;

    protected $fillable = [
        'uuid',
        'origin_device_id',
        'version',
        'scope',
        'generation',
        'amount_usd',
        'percent',
        'min_leg_pv',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'generation' => 'integer',
            'amount_usd' => 'decimal:2',
            'percent' => 'decimal:4',
            'min_leg_pv' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}

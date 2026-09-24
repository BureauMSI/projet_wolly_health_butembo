<?php

namespace App\Models;

use App\Models\Concerns\Syncable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class RewardTier extends Model
{
    use SoftDeletes, Syncable;

    protected $fillable = [
        'uuid',
        'origin_device_id',
        'version',
        'min_pv',
        'max_pv',
        'amount_usd',
        'label',
        'image_path',
        'sort_order',
        'is_active',
    ];

    public function imageUrl(): ?string
    {
        if (! filled($this->image_path)) {
            return null;
        }

        if (str_starts_with($this->image_path, 'images/')) {
            return asset($this->image_path);
        }

        return Storage::disk('public')->url($this->image_path);
    }

    protected function casts(): array
    {
        return [
            'min_pv' => 'decimal:2',
            'max_pv' => 'decimal:2',
            'amount_usd' => 'decimal:2',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}

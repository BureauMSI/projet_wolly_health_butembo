<?php

namespace App\Models;

use App\Models\Concerns\Syncable;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, SoftDeletes, Syncable;

    protected $fillable = [
        'uuid',
        'origin_device_id',
        'version',
        'code',
        'name',
        'benefit_type',
        'unit_price_usd',
        'member_unit_price_usd',
        'pv_per_tablet',
        'box_price_usd',
        'member_box_price_usd',
        'box_pv',
        'commission_percent',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'unit_price_usd' => 'decimal:2',
            'member_unit_price_usd' => 'decimal:2',
            'pv_per_tablet' => 'decimal:2',
            'box_price_usd' => 'decimal:2',
            'member_box_price_usd' => 'decimal:2',
            'box_pv' => 'decimal:2',
            'commission_percent' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function clientPrice(bool $isBox): float
    {
        return $isBox ? (float) $this->box_price_usd : (float) $this->unit_price_usd;
    }

    public function memberPrice(bool $isBox): float
    {
        $value = $isBox ? $this->member_box_price_usd : $this->member_unit_price_usd;
        if ($value === null) {
            return $this->clientPrice($isBox);
        }

        return (float) $value;
    }

    public function saleUnitPrice(string $buyerType, bool $isBox): float
    {
        return $buyerType === 'member' ? $this->memberPrice($isBox) : $this->clientPrice($isBox);
    }

    public function isPercentType(): bool
    {
        return ($this->benefit_type ?? 'pv') === 'percent';
    }

    public function hasPv(): bool
    {
        return ! $this->isPercentType()
            && ((float) $this->pv_per_tablet > 0 || (float) $this->box_pv > 0);
    }

    public function hasPercent(): bool
    {
        return $this->isPercentType() && (float) $this->commission_percent > 0;
    }

    public function matchesBenefitMode(string $mode): bool
    {
        $type = $this->isPercentType() ? 'percent' : 'pv';

        return $type === (($mode === 'percent') ? 'percent' : 'pv');
    }

    public static function nextCode(): string
    {
        $sequence = 1;
        foreach (static::withTrashed()->pluck('code') as $existing) {
            if (preg_match('/^PR-(\d+)$/', (string) $existing, $matches)) {
                $sequence = max($sequence, ((int) $matches[1]) + 1);
            }
        }

        do {
            $code = 'PR-'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
            $sequence++;
        } while (static::withTrashed()->where('code', $code)->exists());

        return $code;
    }
}

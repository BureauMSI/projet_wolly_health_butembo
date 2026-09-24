<?php

namespace App\Models;

use App\Models\Concerns\Syncable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use SoftDeletes, Syncable;

    protected $fillable = [
        'uuid',
        'origin_device_id',
        'version',
        'number',
        'branch_id',
        'user_id',
        'buyer_type',
        'benefit_mode',
        'member_id',
        'client_id',
        'currency_code',
        'rate_to_usd',
        'subtotal_usd',
        'discount_usd',
        'promo_usd',
        'total_usd',
        'sold_at',
        'sync_status',
    ];

    protected function casts(): array
    {
        return [
            'rate_to_usd' => 'decimal:8',
            'subtotal_usd' => 'decimal:2',
            'discount_usd' => 'decimal:2',
            'promo_usd' => 'decimal:2',
            'total_usd' => 'decimal:2',
            'sold_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function cashMovements(): HasMany
    {
        return $this->hasMany(CashMovement::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(CommissionLedger::class);
    }

    public function buyerName(): string
    {
        return $this->buyer_type === 'member'
            ? (string) $this->member?->full_name
            : (string) $this->client?->name;
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\Syncable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Lang;

class CashMovement extends Model
{
    use SoftDeletes, Syncable;

    protected $fillable = [
        'uuid',
        'origin_device_id',
        'version',
        'branch_id',
        'direction',
        'category',
        'operation_type_id',
        'amount',
        'currency_code',
        'rate_to_usd',
        'amount_usd',
        'sale_id',
        'commission_id',
        'description',
        'user_id',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'rate_to_usd' => 'decimal:8',
            'amount_usd' => 'decimal:2',
            'occurred_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function commission(): BelongsTo
    {
        return $this->belongsTo(CommissionLedger::class, 'commission_id');
    }

    public function operationType(): BelongsTo
    {
        return $this->belongsTo(CashOperationType::class, 'operation_type_id');
    }

    public function isManual(): bool
    {
        if ($this->sale_id !== null || $this->commission_id !== null) {
            return false;
        }

        return $this->operationType?->is_system !== true;
    }

    public function categoryLabel(): string
    {
        if ($this->operationType) {
            return $this->operationType->displayName();
        }

        $key = 'messages.cash_category_'.$this->category;

        return Lang::has($key) ? __($key) : (string) $this->category;
    }
}

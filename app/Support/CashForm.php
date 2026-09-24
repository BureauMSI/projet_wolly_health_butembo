<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\CashMovement;
use App\Models\CashOperationType;
use App\Models\Currency;
use Illuminate\Support\Collection;

class CashForm
{
    /**
     * @return array{branches: Collection, currencies: Collection, operationTypes: Collection}
     */
    public static function payload(?CashMovement $movement = null): array
    {
        $types = CashOperationType::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('code')
            ->where(function ($query) use ($movement) {
                $query->where('is_system', false);
                if ($movement?->operation_type_id) {
                    $query->orWhere('id', $movement->operation_type_id);
                }
            })
            ->get();

        return [
            'branches' => Branch::query()->orderBy('name')->get(),
            'currencies' => Currency::query()->orderBy('code')->get(),
            'operationTypes' => $types,
        ];
    }
}

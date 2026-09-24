<?php

namespace App\Services;

use App\Models\CashMovement;
use App\Models\CashOperationType;
use App\Models\User;
use App\Support\PlanConfig;
use Illuminate\Validation\ValidationException;

class CashBook
{
    public function __construct(
        private ExchangeRateResolver $rates,
        private AuditLogger $audit,
        private SyncOutbox $outbox,
        private PlanConfig $plan,
    ) {}

    public function write(User $user, array $payload, ?CashMovement $existing = null): CashMovement
    {
        $type = CashOperationType::query()
            ->where('code', $payload['category'])
            ->first();

        if ($type === null) {
            throw ValidationException::withMessages([
                'category' => __('messages.category'),
            ]);
        }

        if (! $type->is_active && $existing?->operation_type_id !== $type->id) {
            throw ValidationException::withMessages([
                'category' => __('messages.category'),
            ]);
        }

        $direction = $payload['direction'];
        if (! $type->allows($direction)) {
            throw ValidationException::withMessages([
                'category' => __('messages.operation_direction_mismatch'),
            ]);
        }

        if ($existing === null && $type->is_system) {
            throw ValidationException::withMessages([
                'category' => __('messages.operation_system_locked'),
            ]);
        }

        if ($existing && ($existing->sale_id || $existing->commission_id)) {
            throw ValidationException::withMessages([
                'category' => __('messages.operation_system_locked'),
            ]);
        }

        $branchId = $user->isAdmin()
            ? (int) ($payload['branch_id'] ?? $existing?->branch_id ?? $user->branch_id)
            : (int) $user->branch_id;

        if (! $branchId) {
            throw ValidationException::withMessages([
                'branch_id' => __('messages.branch'),
            ]);
        }

        $amount = (float) $payload['amount'];
        $currency = $payload['currency_code'];
        $rate = $this->rates->rateToUsd($currency);
        $amountUsd = round($amount * (float) $rate, 2);
        $occurredAt = filled($payload['occurred_at'] ?? null) ? $payload['occurred_at'] : now();

        $values = [
            'branch_id' => $branchId,
            'direction' => $direction,
            'category' => $type->code,
            'operation_type_id' => $type->id,
            'amount' => $amount,
            'currency_code' => $currency,
            'rate_to_usd' => $rate,
            'amount_usd' => $amountUsd,
            'description' => $payload['description'] ?? null,
            'user_id' => $user->id,
            'occurred_at' => $occurredAt,
        ];

        if ($existing) {
            $old = $existing->toArray();
            $existing->update($values);
            $existing->increment('version');
            $movement = $existing->fresh();
            $this->outbox->enqueue('cash_movement', $movement->uuid, 'update', $movement->toArray(), $this->plan->originDeviceId());
            $this->audit->record($user, 'updated', $movement, $old, $movement->toArray());

            return $movement;
        }

        $movement = CashMovement::query()->create($values + [
            'origin_device_id' => $this->plan->originDeviceId(),
        ]);
        $this->outbox->enqueue('cash_movement', $movement->uuid, 'create', $movement->toArray(), $this->plan->originDeviceId());
        $this->audit->record($user, 'created', $movement, null, $movement->toArray());

        return $movement;
    }
}

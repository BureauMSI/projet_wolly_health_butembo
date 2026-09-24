<?php

namespace App\Services;

use App\Models\CashMovement;
use App\Models\CashOperationType;
use App\Models\CommissionLedger;
use App\Models\Member;
use App\Models\PayoutRequest;
use App\Models\User;
use App\Support\PlanConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayoutRequestService
{
    public function __construct(
        private PlanConfig $plan,
        private SyncOutbox $outbox,
        private AuditLogger $audit,
    ) {}

    public function availableBalance(Member $member): float
    {
        $confirmed = (float) $member->commissions()->where('status', 'confirmed')->sum('amount_usd');
        $reserved = (float) $member->payoutRequests()
            ->whereIn('status', ['pending', 'approved'])
            ->sum('amount_usd');

        return round(max(0, $confirmed - $reserved), 2);
    }

    public function request(Member $member, float $amount, ?string $note = null): PayoutRequest
    {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => __('messages.payout_amount_invalid'),
            ]);
        }

        $available = $this->availableBalance($member);
        if ($amount > $available) {
            throw ValidationException::withMessages([
                'amount' => __('messages.payout_exceeds_balance', [
                    'available' => number_format($available, 2, '.', ''),
                ]),
            ]);
        }

        $pendingExists = $member->payoutRequests()->where('status', 'pending')->exists();
        if ($pendingExists) {
            throw ValidationException::withMessages([
                'amount' => __('messages.payout_already_pending'),
            ]);
        }

        $row = PayoutRequest::query()->create([
            'origin_device_id' => $this->plan->originDeviceId(),
            'member_id' => $member->id,
            'amount_usd' => $amount,
            'status' => 'pending',
            'note' => $note,
        ]);

        $this->outbox->enqueue('payout_request', $row->uuid, 'create', $row->toArray(), $this->plan->originDeviceId());
        $this->audit->record(null, 'created', $row, null, array_merge($row->toArray(), [
            'actor_member_id' => $member->id,
        ]));

        return $row;
    }

    public function reject(PayoutRequest $request, User $actor, ?string $adminNote = null): PayoutRequest
    {
        if (! $request->isPending()) {
            throw ValidationException::withMessages([
                'payout' => __('messages.payout_not_pending'),
            ]);
        }

        $old = $request->toArray();
        $request->update([
            'status' => 'rejected',
            'admin_note' => $adminNote,
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
        ]);
        $request->increment('version');

        $this->outbox->enqueue('payout_request', $request->uuid, 'update', $request->fresh()->toArray(), $this->plan->originDeviceId());
        $this->audit->record($actor, 'updated', $request, $old, $request->fresh()->toArray());

        return $request->fresh();
    }

    public function pay(PayoutRequest $request, User $actor, ?string $adminNote = null): PayoutRequest
    {
        if (! in_array($request->status, ['pending', 'approved'], true)) {
            throw ValidationException::withMessages([
                'payout' => __('messages.payout_not_pending'),
            ]);
        }

        return DB::transaction(function () use ($request, $actor, $adminNote) {
            $old = $request->toArray();
            $member = $request->member()->lockForUpdate()->first();
            $remaining = round((float) $request->amount_usd, 2);
            $target = $remaining;
            $covered = 0.0;

            $commissions = CommissionLedger::query()
                ->where('member_id', $member->id)
                ->where('status', 'confirmed')
                ->orderBy('occurred_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($commissions as $commission) {
                if ($covered >= $target) {
                    break;
                }

                $amount = round((float) $commission->amount_usd, 2);
                if ($amount <= 0) {
                    continue;
                }

                $commissionOld = $commission->toArray();
                $commission->update(['status' => 'paid']);
                $commission->increment('version');
                $this->outbox->enqueue(
                    'commission_ledger',
                    $commission->uuid,
                    'update',
                    $commission->fresh()->toArray(),
                    $this->plan->originDeviceId(),
                );
                $this->audit->record($actor, 'updated', $commission, $commissionOld, $commission->fresh()->toArray());

                $covered = round($covered + $amount, 2);
            }

            if ($covered + 0.001 < $target) {
                throw ValidationException::withMessages([
                    'payout' => __('messages.payout_insufficient_confirmed'),
                ]);
            }

            $branchId = $actor->branch_id ?? $member->registration_branch_id;
            if ($branchId) {
                $cash = CashMovement::query()->create([
                    'origin_device_id' => $this->plan->originDeviceId(),
                    'branch_id' => $branchId,
                    'direction' => 'out',
                    'category' => 'commission',
                    'operation_type_id' => CashOperationType::idFor('commission'),
                    'amount' => $request->amount_usd,
                    'currency_code' => 'USD',
                    'rate_to_usd' => '1',
                    'amount_usd' => $request->amount_usd,
                    'description' => 'payout_request:'.$request->id,
                    'user_id' => $actor->id,
                    'occurred_at' => now(),
                ]);
                $this->outbox->enqueue('cash_movement', $cash->uuid, 'create', $cash->toArray(), $this->plan->originDeviceId());
            }

            $request->update([
                'status' => 'paid',
                'admin_note' => $adminNote ?? $request->admin_note,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'paid_at' => now(),
            ]);
            $request->increment('version');

            $this->outbox->enqueue('payout_request', $request->uuid, 'update', $request->fresh()->toArray(), $this->plan->originDeviceId());
            $this->audit->record($actor, 'updated', $request, $old, $request->fresh()->toArray());

            return $request->fresh();
        });
    }
}

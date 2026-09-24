<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Member;
use App\Models\RegistrationCode;
use App\Models\User;
use App\Support\PlanConfig;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegistrationCodeService
{
    public function __construct(
        private PlanConfig $plan,
        private SyncOutbox $outbox,
        private AuditLogger $audit,
    ) {}

    public function availableCount(int $branchId): int
    {
        return RegistrationCode::query()
            ->where('branch_id', $branchId)
            ->where('status', 'available')
            ->count();
    }

    /**
     * @return Collection<int, RegistrationCode>
     */
    public function generate(Branch $branch, int $quantity, User $actor): Collection
    {
        if ($quantity < 1 || $quantity > 200) {
            throw ValidationException::withMessages([
                'quantity' => __('messages.registration_code_qty_invalid'),
            ]);
        }

        $created = collect();
        for ($i = 0; $i < $quantity; $i++) {
            $row = RegistrationCode::query()->create([
                'origin_device_id' => $this->plan->originDeviceId(),
                'branch_id' => $branch->id,
                'code' => $this->uniqueCode(),
                'status' => 'available',
                'created_by' => $actor->id,
            ]);
            $this->outbox->enqueue('registration_code', $row->uuid, 'create', $row->toArray(), $this->plan->originDeviceId());
            $created->push($row);
        }

        $this->audit->record($actor, 'created', $branch, null, [
            'action' => 'registration_codes_generated',
            'quantity' => $quantity,
            'codes' => $created->pluck('code')->all(),
        ]);

        return $created;
    }

    public function consumeNext(int $branchId, Member $member, User $actor): RegistrationCode
    {
        $row = RegistrationCode::query()
            ->where('branch_id', $branchId)
            ->where('status', 'available')
            ->orderBy('id')
            ->lockForUpdate()
            ->first();

        if ($row === null) {
            throw ValidationException::withMessages([
                'registration_code' => __('messages.registration_code_exhausted'),
            ]);
        }

        return $this->markUsed($row, $member, $actor);
    }

    private function markUsed(RegistrationCode $row, Member $member, User $actor): RegistrationCode
    {
        $old = $row->toArray();
        $row->update([
            'status' => 'used',
            'used_by_user_id' => $actor->id,
            'used_by_member_id' => $member->id,
            'used_at' => now(),
        ]);
        $row->increment('version');

        $this->outbox->enqueue('registration_code', $row->uuid, 'update', $row->fresh()->toArray(), $this->plan->originDeviceId());
        $this->audit->record($actor, 'updated', $row, $old, $row->fresh()->toArray());

        return $row->fresh();
    }

    private function uniqueCode(): string
    {
        do {
            $code = 'RC-'.Str::upper(Str::random(8));
        } while (RegistrationCode::withTrashed()->where('code', $code)->exists());

        return $code;
    }
}

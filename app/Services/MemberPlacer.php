<?php

namespace App\Services;

use App\Models\Member;
use App\Models\User;
use App\Support\PlanConfig;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MemberPlacer
{
    public function __construct(
        private PlanConfig $plan,
        private AuditLogger $audit,
        private SyncOutbox $outbox,
        private CompensationEngine $compensation,
    ) {}

    public function assign(User $actor, Member $member, int $parentId, string $side, int $sponsorId): Member
    {
        if ($member->isTreeRoot()) {
            throw ValidationException::withMessages([
                'placement_parent_id' => __('messages.cannot_place_root'),
            ]);
        }

        if ($member->placement_parent_id !== null) {
            throw ValidationException::withMessages([
                'placement_side' => __('messages.already_placed'),
            ]);
        }

        if ($parentId === $member->id) {
            throw ValidationException::withMessages([
                'placement_parent_id' => __('messages.cannot_place_self'),
            ]);
        }

        if ($sponsorId === $member->id) {
            throw ValidationException::withMessages([
                'sponsor_id' => __('messages.cannot_sponsor_self'),
            ]);
        }

        if (! in_array($side, ['left', 'right'], true)) {
            throw ValidationException::withMessages([
                'placement_side' => __('messages.placement_required'),
            ]);
        }

        try {
            return DB::transaction(function () use ($actor, $member, $parentId, $side, $sponsorId) {
                $parent = Member::query()->lockForUpdate()->find($parentId);

                if ($parent === null || ! $parent->isInTree()) {
                    throw ValidationException::withMessages([
                        'placement_parent_id' => __('messages.placement_parent_not_in_tree'),
                    ]);
                }

                $sponsor = Member::query()->lockForUpdate()->find($sponsorId);

                if ($sponsor === null) {
                    throw ValidationException::withMessages([
                        'sponsor_id' => __('messages.sponsor'),
                    ]);
                }

                $taken = Member::query()
                    ->where('placement_parent_id', $parent->id)
                    ->where('placement_side', $side)
                    ->lockForUpdate()
                    ->exists();

                if ($taken) {
                    throw ValidationException::withMessages([
                        'placement_side' => __('messages.placement_taken'),
                    ]);
                }

                $hadSponsor = $member->sponsor_id !== null;
                $old = $member->makeHidden('password')->toArray();
                $member->update([
                    'sponsor_id' => $sponsor->id,
                    'placement_parent_id' => $parent->id,
                    'placement_side' => $side,
                ]);
                $member->increment('version');
                $member->refresh();
                $member->load('sponsor');

                $this->outbox->enqueue(
                    'member',
                    $member->uuid,
                    'update',
                    $member->makeHidden('password')->toArray(),
                    $this->plan->originDeviceId(),
                );
                $this->audit->record($actor, 'updated', $member, $old, $member->makeHidden('password')->toArray());

                if (! $hadSponsor) {
                    $this->compensation->onMembership($member);
                }

                $this->compensation->onPlacement($member);

                return $member;
            });
        } catch (QueryException $exception) {
            if ($this->isPlacementUniqueViolation($exception)) {
                throw ValidationException::withMessages([
                    'placement_side' => __('messages.placement_taken'),
                ]);
            }

            throw $exception;
        }
    }

    private function isPlacementUniqueViolation(QueryException $exception): bool
    {
        $message = $exception->getMessage();

        return str_contains($message, 'placement_parent_id')
            || str_contains($message, 'members_placement_parent_id_placement_side_unique')
            || (string) $exception->errorInfo[0] === '23000';
    }
}

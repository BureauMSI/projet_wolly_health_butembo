<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommissionLedger;
use App\Services\AuditLogger;
use App\Services\SyncOutbox;
use App\Support\Listing;
use App\Support\PlanConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RewardController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        abort_unless(
            $user?->canViewCommissions() || $user?->isCashier(),
            403,
        );

        $status = $request->string('status')->toString();
        if (! in_array($status, ['pending', 'served', 'all'], true)) {
            $status = 'pending';
        }

        $branchId = $user->scopedBranchId();

        $rewards = CommissionLedger::query()
            ->with(['member:id,full_name,member_code,phone,registration_branch_id', 'rewardTier'])
            ->where('type', 'reward')
            ->whereHas('rewardTier', fn ($q) => $q->where('is_active', true))
            ->when($branchId, fn ($q) => $q->whereHas(
                'member',
                fn ($m) => $m->where('registration_branch_id', $branchId),
            ))
            ->when($status === 'pending', fn ($q) => $q->whereIn('status', ['pending', 'confirmed']))
            ->when($status === 'served', fn ($q) => $q->where('status', 'paid'))
            ->latest('occurred_at')
            ->paginate(Listing::PER_PAGE)
            ->withQueryString();

        return view('admin.rewards.index', [
            'rewards' => $rewards,
            'status' => $status,
        ]);
    }

    public function serve(
        CommissionLedger $commission,
        AuditLogger $audit,
        SyncOutbox $outbox,
        PlanConfig $plan,
    ): RedirectResponse {
        $user = auth()->user();
        abort_unless(
            $user?->canManagePayouts() || $user?->isCashier(),
            403,
        );
        $commission->loadMissing('rewardTier');
        abort_unless(
            $commission->type === 'reward'
            && $commission->rewardTier
            && $commission->rewardTier->is_active,
            404,
        );
        $this->assertBranchAccess($commission);

        if ($commission->status === 'paid') {
            return back()->with('status', __('messages.prize_marked_served'));
        }

        $old = $commission->toArray();
        $commission->update(['status' => 'paid']);
        $commission->increment('version');

        $outbox->enqueue('commission_ledger', $commission->uuid, 'update', $commission->fresh()->toArray(), $plan->originDeviceId());
        $audit->record($user, 'updated', $commission, $old, $commission->fresh()->toArray());

        return back()->with('status', __('messages.prize_marked_served'));
    }

    private function assertBranchAccess(CommissionLedger $commission): void
    {
        $user = auth()->user();
        $branchId = $user?->scopedBranchId();
        if ($branchId === null) {
            return;
        }

        $commission->loadMissing('member:id,registration_branch_id');
        abort_unless(
            (int) $commission->member?->registration_branch_id === (int) $branchId,
            403,
        );
    }
}

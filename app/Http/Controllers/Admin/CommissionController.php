<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashMovement;
use App\Models\CashOperationType;
use App\Models\CommissionLedger;
use App\Services\AuditLogger;
use App\Services\SyncOutbox;
use App\Support\Listing;
use App\Support\PlanConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CommissionController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->canViewCommissions(), 403);
        $branchId = auth()->user()->scopedBranchId();

        return view('admin.commissions.index', [
            'commissions' => CommissionLedger::query()
                ->with(['member', 'relatedMember', 'rewardTier'])
                ->when($branchId, fn ($q) => $q->whereHas(
                    'member',
                    fn ($m) => $m->where('registration_branch_id', $branchId),
                ))
                ->latest('occurred_at')
                ->paginate(Listing::PER_PAGE),
        ]);
    }

    public function pay(
        CommissionLedger $commission,
        AuditLogger $audit,
        SyncOutbox $outbox,
        PlanConfig $plan,
    ): RedirectResponse {
        abort_unless(auth()->user()?->canManagePayouts(), 403);
        $this->assertBranchAccess($commission);

        if ($commission->status === 'paid') {
            return back()->with('status', __('messages.saved'));
        }

        $old = $commission->toArray();
        $commission->update(['status' => 'paid']);
        $commission->increment('version');

        $user = request()->user();
        $branchId = $user->branch_id ?? $commission->member?->registration_branch_id;

        if ($branchId) {
            $cash = CashMovement::query()->create([
                'origin_device_id' => $plan->originDeviceId(),
                'branch_id' => $branchId,
                'direction' => 'out',
                'category' => 'commission',
                'operation_type_id' => CashOperationType::idFor('commission'),
                'amount' => $commission->amount_usd,
                'currency_code' => 'USD',
                'rate_to_usd' => '1',
                'amount_usd' => $commission->amount_usd,
                'commission_id' => $commission->id,
                'description' => \App\Services\ReportBuilder::commissionLabel($commission),
                'user_id' => $user->id,
                'occurred_at' => now(),
            ]);
            $outbox->enqueue('cash_movement', $cash->uuid, 'create', $cash->toArray(), $plan->originDeviceId());
        }

        $outbox->enqueue('commission_ledger', $commission->uuid, 'update', $commission->fresh()->toArray(), $plan->originDeviceId());
        $audit->record($user, 'updated', $commission, $old, $commission->fresh()->toArray());

        return back()->with('status', __('messages.commission_paid'));
    }

    public function destroy(CommissionLedger $commission, AuditLogger $audit, SyncOutbox $outbox, PlanConfig $plan): RedirectResponse
    {
        abort_unless(auth()->user()?->canManagePayouts(), 403);
        $this->assertBranchAccess($commission);

        if ($commission->status === 'paid') {
            return back()->withErrors(['commission' => __('messages.cannot_mutate_paid_sale')]);
        }

        $old = $commission->toArray();
        $commission->delete();
        $outbox->enqueue('commission_ledger', $commission->uuid, 'delete', $old, $plan->originDeviceId());
        $audit->record(request()->user(), 'deleted', $commission, $old, null);

        return back()->with('status', __('messages.record_deleted'));
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

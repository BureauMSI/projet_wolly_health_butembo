<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PayoutRequest;
use App\Services\PayoutRequestService;
use App\Support\Listing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayoutRequestController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        abort_unless(
            $user?->canViewCommissions() || $user?->isCashier(),
            403,
        );
        $branchId = $user->scopedBranchId();

        return view('admin.payouts.index', [
            'requests' => PayoutRequest::query()
                ->with(['member:id,full_name,member_code,phone,registration_branch_id', 'reviewer:id,name'])
                ->when($branchId, fn ($q) => $q->whereHas(
                    'member',
                    fn ($m) => $m->where('registration_branch_id', $branchId),
                ))
                ->latest('id')
                ->paginate(Listing::PER_PAGE),
        ]);
    }

    public function pay(Request $request, PayoutRequest $payout, PayoutRequestService $service): RedirectResponse
    {
        abort_unless(auth()->user()?->canManagePayouts(), 403);
        $this->assertBranchAccess($payout);

        $data = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:500'],
        ]);

        $service->pay($payout, $request->user(), $data['admin_note'] ?? null);

        return back()->with('status', __('messages.payout_paid'));
    }

    public function reject(Request $request, PayoutRequest $payout, PayoutRequestService $service): RedirectResponse
    {
        abort_unless(auth()->user()?->canManagePayouts(), 403);
        $this->assertBranchAccess($payout);

        $data = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:500'],
        ]);

        $service->reject($payout, $request->user(), $data['admin_note'] ?? null);

        return back()->with('status', __('messages.payout_rejected'));
    }

    private function assertBranchAccess(PayoutRequest $payout): void
    {
        $user = auth()->user();
        $branchId = $user?->scopedBranchId();
        if ($branchId === null) {
            return;
        }

        $payout->loadMissing('member:id,registration_branch_id');
        abort_unless(
            (int) $payout->member?->registration_branch_id === (int) $branchId,
            403,
        );
    }
}

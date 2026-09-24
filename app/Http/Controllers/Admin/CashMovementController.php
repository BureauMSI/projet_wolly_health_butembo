<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCashMovementRequest;
use App\Http\Requests\UpdateCashMovementRequest;
use App\Models\CashMovement;
use App\Models\Institution;
use App\Services\AuditLogger;
use App\Services\CashBook;
use App\Services\SyncOutbox;
use App\Support\CashForm;
use App\Support\Listing;
use App\Support\PlanConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CashMovementController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', CashMovement::class);
        $branchId = auth()->user()->scopedBranchId();

        $movements = CashMovement::query()
            ->with(['branch', 'user', 'sale', 'operationType'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->latest('occurred_at')
            ->paginate(Listing::PER_PAGE);

        return view('admin.cash.index', compact('movements'));
    }

    public function create(): View
    {
        $this->authorize('create', CashMovement::class);

        return view('admin.cash.create', CashForm::payload());
    }

    public function store(StoreCashMovementRequest $request, CashBook $book): RedirectResponse
    {
        $book->write($request->user(), $request->validated());

        return redirect()->route('admin.cash.index')->with('status', __('messages.movement_recorded'));
    }

    public function edit(CashMovement $movement): View
    {
        $this->authorize('update', $movement);

        return view('admin.cash.edit', CashForm::payload($movement) + ['movement' => $movement]);
    }

    public function update(
        UpdateCashMovementRequest $request,
        CashMovement $movement,
        CashBook $book,
    ): RedirectResponse {
        $book->write($request->user(), $request->validated(), $movement);

        return redirect()->route('admin.cash.index')->with('status', __('messages.saved'));
    }

    public function destroy(CashMovement $movement, AuditLogger $audit, SyncOutbox $outbox, PlanConfig $plan): RedirectResponse
    {
        $this->authorize('delete', $movement);
        $old = $movement->toArray();
        $movement->delete();
        $outbox->enqueue('cash_movement', $movement->uuid, 'delete', $old, $plan->originDeviceId());
        $audit->record(request()->user(), 'deleted', $movement, $old, null);

        return redirect()->route('admin.cash.index')->with('status', __('messages.record_deleted'));
    }

    public function print58(CashMovement $movement): View
    {
        return $this->thermalVoucher($movement, 58);
    }

    public function print80(CashMovement $movement): View
    {
        return $this->thermalVoucher($movement, 80);
    }

    private function thermalVoucher(CashMovement $movement, int $width): View
    {
        $this->authorize('view', $movement);
        abort_unless(in_array($width, [58, 80], true), 404);

        $movement->load(['branch', 'user', 'sale', 'operationType']);

        return view('print.thermal', [
            'kind' => 'voucher',
            'width' => $width,
            'sale' => $movement->sale,
            'movement' => $movement,
            'docTitle' => $movement->direction === 'out'
                ? __('messages.doc_voucher_out')
                : __('messages.doc_voucher_in'),
            'institution' => Institution::query()->first(),
            'printedBy' => auth()->user()?->name,
        ]);
    }
}

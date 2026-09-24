<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCashMovementRequest;
use App\Http\Requests\StoreCashOperationTypeRequest;
use App\Http\Requests\UpdateCashMovementRequest;
use App\Models\CashMovement;
use App\Models\CashOperationType;
use App\Services\AuditLogger;
use App\Services\CashBook;
use App\Services\SyncOutbox;
use App\Support\CashForm;
use App\Support\Listing;
use App\Support\PlanConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OperationController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', CashMovement::class);
        $branchId = auth()->user()->scopedBranchId();
        $direction = $request->query('direction');

        $movements = CashMovement::query()
            ->with(['branch', 'user', 'operationType'])
            ->whereNull('sale_id')
            ->whereNull('commission_id')
            ->where(function ($query) {
                $query->whereHas('operationType', fn ($q) => $q->where('is_system', false))
                    ->orWhereDoesntHave('operationType');
            })
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when(in_array($direction, ['in', 'out'], true), fn ($q) => $q->where('direction', $direction))
            ->latest('occurred_at')
            ->paginate(Listing::PER_PAGE)
            ->withQueryString();

        return view('admin.operations.index', [
            'movements' => $movements,
            'direction' => $direction,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', CashMovement::class);

        return view('admin.operations.create', CashForm::payload());
    }

    public function store(StoreCashMovementRequest $request, CashBook $book): RedirectResponse
    {
        $book->write($request->user(), $request->validated());

        return redirect()->route('admin.operations.index')->with('status', __('messages.movement_recorded'));
    }

    public function edit(CashMovement $movement): View
    {
        $this->authorize('update', $movement);
        abort_unless($movement->isManual(), 403);

        return view('admin.operations.edit', CashForm::payload($movement) + ['movement' => $movement]);
    }

    public function update(UpdateCashMovementRequest $request, CashMovement $movement, CashBook $book): RedirectResponse
    {
        $this->authorize('update', $movement);
        abort_unless($movement->isManual(), 403);
        $book->write($request->user(), $request->validated(), $movement);

        return redirect()->route('admin.operations.index')->with('status', __('messages.saved'));
    }

    public function destroy(CashMovement $movement, AuditLogger $audit, SyncOutbox $outbox, PlanConfig $plan): RedirectResponse
    {
        $this->authorize('delete', $movement);
        abort_unless($movement->isManual(), 403);
        $old = $movement->toArray();
        $movement->delete();
        $outbox->enqueue('cash_movement', $movement->uuid, 'delete', $old, $plan->originDeviceId());
        $audit->record(request()->user(), 'deleted', $movement, $old, null);

        return redirect()->route('admin.operations.index')->with('status', __('messages.record_deleted'));
    }

    public function storeType(
        StoreCashOperationTypeRequest $request,
        AuditLogger $audit,
        SyncOutbox $outbox,
        PlanConfig $plan,
    ): RedirectResponse {
        $maxOrder = (int) CashOperationType::query()->max('sort_order');
        $type = CashOperationType::query()->create([
            'code' => CashOperationType::uniqueCodeFromLabel($request->validated('label')),
            'label' => $request->validated('label'),
            'direction' => $request->validated('direction'),
            'sort_order' => $maxOrder + 10,
            'is_system' => false,
            'is_active' => true,
        ]);

        $outbox->enqueue('cash_operation_type', $type->uuid, 'create', $type->toArray(), $plan->originDeviceId());
        $audit->record($request->user(), 'created', $type, null, $type->toArray());

        return back()->with('status', __('messages.operation_type_created'));
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSaleRequest;
use App\Http\Requests\UpdateSaleRequest;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Currency;
use App\Models\Institution;
use App\Models\Member;
use App\Models\Product;
use App\Models\Sale;
use App\Support\Listing;
use App\Services\SaleRecorder;
use App\Services\WhatsAppNotifier;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Sale::class);
        $branchId = auth()->user()->scopedBranchId();

        $sales = Sale::query()
            ->with(['member', 'client', 'branch', 'cashier'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->latest('sold_at')
            ->paginate(Listing::PER_PAGE);

        return view('admin.sales.index', compact('sales'));
    }

    public function create(): View
    {
        $this->authorize('create', Sale::class);

        return view('admin.sales.create', [
            'products' => Product::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'benefit_type',
                    'unit_price_usd',
                    'member_unit_price_usd',
                    'box_price_usd',
                    'member_box_price_usd',
                    'pv_per_tablet',
                    'box_pv',
                    'commission_percent',
                ]),
            'members' => Member::query()->orderBy('full_name')->get(['id', 'full_name', 'username']),
            'clients' => Client::query()->orderBy('name')->get(['id', 'name', 'phone']),
            'branches' => Branch::query()->orderBy('name')->get(['id', 'name']),
            'currencies' => Currency::query()->orderBy('code')->get(['code', 'name']),
        ]);
    }

    public function store(StoreSaleRequest $request, SaleRecorder $recorder): RedirectResponse
    {
        $sale = $recorder->record($request->user(), $request->validated());

        return redirect()
            ->route('admin.sales.show', $sale)
            ->with('status', __('messages.sale_recorded'));
    }

    public function edit(Sale $sale): View
    {
        $this->authorize('update', $sale);
        $sale->load('items');

        return view('admin.sales.edit', [
            'sale' => $sale,
            'products' => Product::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'benefit_type',
                    'unit_price_usd',
                    'member_unit_price_usd',
                    'box_price_usd',
                    'member_box_price_usd',
                    'pv_per_tablet',
                    'box_pv',
                    'commission_percent',
                ]),
            'members' => Member::query()->orderBy('full_name')->get(['id', 'full_name', 'username']),
            'clients' => Client::query()->orderBy('name')->get(['id', 'name', 'phone']),
            'branches' => Branch::query()->orderBy('name')->get(['id', 'name']),
            'currencies' => Currency::query()->orderBy('code')->get(['code', 'name']),
        ]);
    }

    public function update(UpdateSaleRequest $request, Sale $sale, SaleRecorder $recorder): RedirectResponse
    {
        $sale = $recorder->update($request->user(), $sale, $request->validated());

        return redirect()
            ->route('admin.sales.show', $sale)
            ->with('status', __('messages.saved'));
    }

    public function destroy(Sale $sale, SaleRecorder $recorder): RedirectResponse
    {
        $this->authorize('delete', $sale);
        /** @var User $user */
        $user = auth()->user();
        $recorder->void($user, $sale);

        return redirect()->route('admin.sales.index')->with('status', __('messages.record_deleted'));
    }

    public function show(Sale $sale): View
    {
        $this->authorize('view', $sale);
        $sale->load([
            'items.product',
            'member',
            'client',
            'branch',
            'cashier',
            'cashMovements',
            'commissions.member',
            'commissions.relatedMember',
            'commissions.rewardTier',
        ]);

        return view('admin.sales.show', compact('sale'));
    }

    public function print58(Sale $sale): View
    {
        return $this->thermalSale($sale, 'receipt', 58);
    }

    public function print80(Sale $sale): View
    {
        return $this->thermalSale($sale, 'receipt', 80);
    }

    public function printInvoice58(Sale $sale): View
    {
        return $this->thermalSale($sale, 'invoice', 58);
    }

    public function printInvoice80(Sale $sale): View
    {
        return $this->thermalSale($sale, 'invoice', 80);
    }

    public function printReceipt58(Sale $sale): View
    {
        return $this->thermalSale($sale, 'receipt', 58);
    }

    public function printReceipt80(Sale $sale): View
    {
        return $this->thermalSale($sale, 'receipt', 80);
    }

    public function whatsapp(Sale $sale, WhatsAppNotifier $whatsapp): RedirectResponse
    {
        $this->authorize('view', $sale);
        $phone = $sale->member?->phone ?: $sale->client?->phone;

        if (! filled($phone)) {
            return back()->withErrors(['phone' => __('messages.phone_missing')]);
        }

        $row = $whatsapp->enqueue($phone, 'receipt', app()->getLocale(), [
            'number' => $sale->number,
            'total' => number_format((float) $sale->total_usd, 2, '.', ''),
            'currency' => 'USD',
        ]);

        return redirect()->away($whatsapp->waMeUrl($row));
    }

    private function thermalSale(Sale $sale, string $kind, int $width): View
    {
        $this->authorize('view', $sale);
        abort_unless(in_array($width, [58, 80], true), 404);

        $sale->load(['items.product', 'member', 'client', 'branch', 'cashier']);

        return view('print.thermal', [
            'kind' => $kind,
            'width' => $width,
            'sale' => $sale,
            'movement' => null,
            'docTitle' => $kind === 'invoice' ? __('messages.doc_invoice') : __('messages.doc_receipt'),
            'institution' => Institution::query()->first(),
            'printedBy' => auth()->user()?->name,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Services\AuditLogger;
use App\Services\SyncOutbox;
use App\Support\Listing;
use App\Support\PlanConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Product::class);

        return view('admin.products.index', [
            'products' => Product::query()->orderBy('name')->paginate(Listing::PER_PAGE),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Product::class);

        return view('admin.products.create');
    }

    public function store(StoreProductRequest $request, AuditLogger $audit, SyncOutbox $outbox, PlanConfig $plan): RedirectResponse
    {
        $product = Product::query()->create([
            ...$this->normalizedPayload($request),
            'code' => Product::nextCode(),
            'origin_device_id' => $plan->originDeviceId(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $outbox->enqueue('product', $product->uuid, 'create', $product->toArray(), $plan->originDeviceId());
        $audit->record($request->user(), 'created', $product, null, $product->toArray());

        return redirect()->route('admin.products.index')->with('status', __('messages.product_created'));
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);

        return view('admin.products.edit', compact('product'));
    }

    public function update(UpdateProductRequest $request, Product $product, AuditLogger $audit, SyncOutbox $outbox, PlanConfig $plan): RedirectResponse
    {
        $old = $product->toArray();
        $product->update([
            ...$this->normalizedPayload($request),
            'is_active' => $request->boolean('is_active', $product->is_active),
        ]);
        $product->increment('version');

        $outbox->enqueue('product', $product->uuid, 'update', $product->fresh()->toArray(), $plan->originDeviceId());
        $audit->record($request->user(), 'updated', $product, $old, $product->fresh()->toArray());

        return redirect()->route('admin.products.index')->with('status', __('messages.saved'));
    }

    public function destroy(Product $product, AuditLogger $audit, SyncOutbox $outbox, PlanConfig $plan): RedirectResponse
    {
        $this->authorize('delete', $product);

        if ($product->saleItems()->exists()) {
            return back()->withErrors(['product' => __('messages.cannot_delete_linked')]);
        }

        $old = $product->toArray();
        $product->delete();
        $outbox->enqueue('product', $product->uuid, 'delete', $old, $plan->originDeviceId());
        $audit->record(request()->user(), 'deleted', $product, $old, null);

        return redirect()->route('admin.products.index')->with('status', __('messages.record_deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizedPayload(StoreProductRequest|UpdateProductRequest $request): array
    {
        $data = $request->validated();
        unset($data['code']);
        $isPercent = ($data['benefit_type'] ?? 'pv') === 'percent';

        if ($isPercent) {
            $data['pv_per_tablet'] = 0;
            $data['box_pv'] = 0;
            $data['commission_percent'] = $data['commission_percent'] ?? 0;
        } else {
            $data['commission_percent'] = 0;
            $data['pv_per_tablet'] = $data['pv_per_tablet'] ?? 0;
            $data['box_pv'] = $data['box_pv'] ?? 0;
        }

        return $data;
    }
}

<?php

namespace App\Services;

use App\Models\CashMovement;
use App\Models\CashOperationType;
use App\Models\CommissionLedger;
use App\Models\Member;
use App\Models\Product;
use App\Models\PvLedger;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Support\PlanConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SaleRecorder
{
    public function __construct(
        private PlanConfig $plan,
        private ExchangeRateResolver $rates,
        private CompensationEngine $compensation,
        private AuditLogger $audit,
        private SyncOutbox $outbox,
        private WhatsAppNotifier $whatsapp,
    ) {}

    public function record(User $actor, array $payload): Sale
    {
        $sale = DB::transaction(function () use ($actor, $payload) {
            $sale = $this->writeSale($actor, $payload, null);
            $this->audit->record($actor, 'created', $sale, null, $sale->toArray());

            return $sale;
        });

        $this->notifyReceipt($sale);

        return $sale;
    }

    public function update(User $actor, Sale $sale, array $payload): Sale
    {
        $sale = DB::transaction(function () use ($actor, $sale, $payload) {
            $this->assertMutable($sale);
            $old = $sale->toArray();
            $this->reverseEffects($sale, keepSale: true);
            $sale = $this->writeSale($actor, $payload, $sale->fresh());
            $this->audit->record($actor, 'updated', $sale, $old, $sale->toArray());

            return $sale;
        });

        $this->notifyReceipt($sale);

        return $sale;
    }

    public function void(User $actor, Sale $sale): void
    {
        DB::transaction(function () use ($actor, $sale) {
            $this->assertMutable($sale);
            $old = $sale->toArray();
            $this->reverseEffects($sale, keepSale: false);
            $this->audit->record($actor, 'deleted', $sale, $old, null);
        });
    }

    private function writeSale(User $actor, array $payload, ?Sale $existing): Sale
    {
        $branchId = $existing
            ? (int) $existing->branch_id
            : ($actor->isAdmin() ? (int) ($payload['branch_id'] ?? 0) : (int) $actor->branch_id);

        if (! $branchId) {
            throw ValidationException::withMessages([
                'branch_id' => __('messages.branch'),
            ]);
        }

        $totals = $this->pricePayload($payload);
        $status = $this->plan->ledgerStatus();

        if ($existing) {
            $existing->update([
                'buyer_type' => $totals['buyer_type'],
                'benefit_mode' => $totals['benefit_mode'],
                'member_id' => $totals['member_id'],
                'client_id' => $totals['client_id'],
                'currency_code' => $totals['currency'],
                'rate_to_usd' => $totals['rate'],
                'subtotal_usd' => $totals['subtotal'],
                'discount_usd' => $totals['discount'],
                'promo_usd' => $totals['promo'],
                'total_usd' => $totals['total'],
                'sync_status' => $status,
            ]);
            $existing->increment('version');
            $sale = $existing->fresh();
        } else {
            $sale = Sale::query()->create([
                'origin_device_id' => $this->plan->originDeviceId(),
                'number' => $this->nextNumber(),
                'branch_id' => $branchId,
                'user_id' => $actor->id,
                'buyer_type' => $totals['buyer_type'],
                'benefit_mode' => $totals['benefit_mode'],
                'member_id' => $totals['member_id'],
                'client_id' => $totals['client_id'],
                'currency_code' => $totals['currency'],
                'rate_to_usd' => $totals['rate'],
                'subtotal_usd' => $totals['subtotal'],
                'discount_usd' => $totals['discount'],
                'promo_usd' => $totals['promo'],
                'total_usd' => $totals['total'],
                'sold_at' => now(),
                'sync_status' => $status,
            ]);
        }

        foreach ($totals['prepared'] as $line) {
            $item = SaleItem::query()->create([
                'origin_device_id' => $this->plan->originDeviceId(),
                'sale_id' => $sale->id,
                'product_id' => $line['product']->id,
                'packing' => $line['packing'],
                'quantity' => $line['quantity'],
                'unit_price_usd' => $line['unit_price_usd'],
                'pv' => $line['pv'],
                'commission_percent' => $line['commission_percent'],
                'commission_usd' => $line['commission_usd'],
                'line_total_usd' => $line['line_total_usd'],
            ]);
            $this->outbox->enqueue('sale_item', $item->uuid, 'create', $item->toArray(), $this->plan->originDeviceId());
        }

        $cash = CashMovement::query()->create([
            'origin_device_id' => $this->plan->originDeviceId(),
            'branch_id' => $branchId,
            'direction' => 'in',
            'category' => 'sale',
            'operation_type_id' => CashOperationType::idFor('sale'),
            'amount' => $totals['total'],
            'currency_code' => 'USD',
            'rate_to_usd' => '1',
            'amount_usd' => $totals['total'],
            'sale_id' => $sale->id,
            'user_id' => $actor->id,
            'occurred_at' => $sale->sold_at ?? now(),
            'description' => $sale->number,
        ]);

        $this->outbox->enqueue('sale', $sale->uuid, $existing ? 'update' : 'create', $sale->toArray(), $this->plan->originDeviceId());
        $this->outbox->enqueue('cash_movement', $cash->uuid, 'create', $cash->toArray(), $this->plan->originDeviceId());
        $this->compensation->onSale($sale->load('items.product', 'member.sponsor', 'client.referrer'));

        return $sale;
    }

    /**
     * @return array{buyer_type: string, benefit_mode: string, member_id: ?int, client_id: ?int, currency: string, rate: string|float, subtotal: float, discount: float, promo: float, total: float, prepared: array<int, array<string, mixed>>}
     */
    private function pricePayload(array $payload): array
    {
        $buyerType = $payload['buyer_type'];
        $benefitMode = (($payload['benefit_mode'] ?? 'pv') === 'percent') ? 'percent' : 'pv';
        $subtotal = 0.0;
        $prepared = [];

        foreach ($payload['items'] as $line) {
            $product = Product::query()->where('is_active', true)->findOrFail($line['product_id']);
            if (! $product->matchesBenefitMode($benefitMode)) {
                throw ValidationException::withMessages([
                    'items' => __('messages.product_benefit_mismatch'),
                ]);
            }
            $qty = (int) $line['quantity'];
            $isBox = ($line['packing'] ?? 'tablet') === 'box';
            $unit = $product->saleUnitPrice($buyerType, $isBox);
            $clientLine = round($product->clientPrice($isBox) * $qty, 2);
            $pv = $benefitMode === 'pv'
                ? ($isBox ? (float) $product->box_pv : (float) $product->pv_per_tablet)
                : 0.0;
            $commissionPercent = $benefitMode === 'percent'
                ? (float) $product->commission_percent
                : 0.0;
            $lineTotal = round($unit * $qty, 2);
            $linePv = round($pv * $qty, 2);
            $subtotal += $lineTotal;
            $prepared[] = [
                'product' => $product,
                'packing' => $isBox ? 'box' : 'tablet',
                'quantity' => $qty,
                'unit_price_usd' => $unit,
                'pv' => $linePv,
                'commission_percent' => $commissionPercent,
                'client_line_total_usd' => $clientLine,
                'line_total_usd' => $lineTotal,
            ];
        }

        $promo = min(max((float) ($payload['promo_usd'] ?? 0), 0), $subtotal);
        $discount = 0.0;
        if ($buyerType === 'member'
            && array_key_exists('discount_usd', $payload)
            && $payload['discount_usd'] !== null
            && $payload['discount_usd'] !== '') {
            $discount = min(max((float) $payload['discount_usd'], 0), $subtotal - $promo);
        }

        $total = round($subtotal - $promo - $discount, 2);
        $ratio = $subtotal > 0 ? $total / $subtotal : 0.0;
        foreach ($prepared as $index => $line) {
            $prepared[$index]['commission_usd'] = round($line['client_line_total_usd'] * $ratio * $line['commission_percent'] / 100, 2);
        }

        $currency = $payload['currency_code'] ?? 'USD';

        return [
            'buyer_type' => $buyerType,
            'benefit_mode' => $benefitMode,
            'member_id' => $buyerType === 'member' ? ($payload['member_id'] ?? null) : null,
            'client_id' => $buyerType === 'client' ? ($payload['client_id'] ?? null) : null,
            'currency' => $currency,
            'rate' => $this->rates->rateToUsd($currency),
            'subtotal' => $subtotal,
            'discount' => $discount,
            'promo' => $promo,
            'total' => $total,
            'prepared' => $prepared,
        ];
    }

    private function assertMutable(Sale $sale): void
    {
        $paid = CommissionLedger::query()
            ->where('sale_id', $sale->id)
            ->where('status', 'paid')
            ->exists();

        if ($paid) {
            throw ValidationException::withMessages([
                'sale' => __('messages.cannot_mutate_paid_sale'),
            ]);
        }
    }

    private function reverseEffects(Sale $sale, bool $keepSale): void
    {
        $sale->load(['items', 'client']);
        $pvTotal = (float) $sale->items->sum('pv');
        $memberIds = PvLedger::query()->where('sale_id', $sale->id)->pluck('member_id')->unique()->all();

        if ($sale->buyer_type === 'client' && $sale->client && $pvTotal > 0 && ($sale->benefit_mode ?? 'pv') !== 'percent') {
            $client = $sale->client;
            $client->update([
                'accumulated_pv' => max(0, round((float) $client->accumulated_pv - $pvTotal, 2)),
            ]);
            $this->outbox->enqueue('client', $client->uuid, 'update', $client->fresh()->toArray(), $this->plan->originDeviceId());
        }

        foreach (PvLedger::query()->where('sale_id', $sale->id)->get() as $row) {
            $payload = $row->toArray();
            $row->delete();
            $this->outbox->enqueue('pv_ledger', $row->uuid, 'delete', $payload, $this->plan->originDeviceId());
        }

        foreach (CommissionLedger::query()->where('sale_id', $sale->id)->get() as $row) {
            $payload = $row->toArray();
            $row->delete();
            $this->outbox->enqueue('commission_ledger', $row->uuid, 'delete', $payload, $this->plan->originDeviceId());
        }

        foreach (CashMovement::query()->where('sale_id', $sale->id)->get() as $row) {
            $payload = $row->toArray();
            $row->delete();
            $this->outbox->enqueue('cash_movement', $row->uuid, 'delete', $payload, $this->plan->originDeviceId());
        }

        foreach ($sale->items as $item) {
            $payload = $item->toArray();
            $item->delete();
            $this->outbox->enqueue('sale_item', $item->uuid, 'delete', $payload, $this->plan->originDeviceId());
        }

        if (! $keepSale) {
            $sale->delete();
            $this->outbox->enqueue('sale', $sale->uuid, 'delete', $sale->toArray(), $this->plan->originDeviceId());
        }

        foreach ($memberIds as $memberId) {
            $member = Member::query()->find($memberId);
            if ($member) {
                $this->compensation->refreshRewards($member);
            }
        }
    }

    private function notifyReceipt(Sale $sale): void
    {
        $sale->loadMissing(['member', 'client.referrer']);
        $phone = $sale->member?->phone ?: $sale->client?->phone;
        if (! filled($phone)) {
            return;
        }

        $this->whatsapp->enqueue($phone, 'receipt', $sale->member?->locale ?: $sale->client?->referrer?->locale ?: 'fr', [
            'number' => $sale->number,
            'total' => number_format((float) $sale->total_usd, 2, '.', ''),
            'currency' => 'USD',
        ]);
    }

    private function nextNumber(): string
    {
        do {
            $number = 'HH-'.now()->format('ymd').'-'.strtoupper(Str::random(4));
        } while (Sale::withTrashed()->where('number', $number)->exists());

        return $number;
    }
}

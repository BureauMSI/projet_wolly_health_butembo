<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashMovement;
use App\Models\CommissionLedger;
use App\Models\Member;
use App\Models\PayoutRequest;
use App\Models\Sale;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();
        $branchId = $user->scopedBranchId();
        $today = today()->toDateString();

        $salesToday = Sale::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereDate('sold_at', $today)
            ->selectRaw('COUNT(*) as sale_count, COALESCE(SUM(total_usd), 0) as sale_total')
            ->first();

        $cashToday = CashMovement::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereDate('occurred_at', $today)
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'in' THEN amount_usd ELSE 0 END), 0) as cash_in")
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'out' THEN amount_usd ELSE 0 END), 0) as cash_out")
            ->first();

        $cashBalance = CashMovement::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'in' THEN amount_usd ELSE -amount_usd END), 0) as balance")
            ->value('balance');

        $salesTrendStart = today()->subDays(6)->startOfDay();
        $salesTrendEnd = today()->endOfDay();
        $trendRows = Sale::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('sold_at', [$salesTrendStart, $salesTrendEnd])
            ->selectRaw('DATE(sold_at) as sale_day, COUNT(*) as sale_count, COALESCE(SUM(total_usd), 0) as sale_total')
            ->groupByRaw('DATE(sold_at)')
            ->get()
            ->keyBy(fn ($row) => (string) $row->sale_day);

        $salesTrend = collect(range(6, 0))->map(function (int $daysAgo) use ($trendRows) {
            $day = today()->subDays($daysAgo);
            $key = $day->toDateString();
            $row = $trendRows->get($key);

            return [
                'date' => $key,
                'label' => $day->format('d/m'),
                'count' => (int) ($row->sale_count ?? 0),
                'total' => (float) ($row->sale_total ?? 0),
            ];
        });

        $pendingPayoutsQuery = PayoutRequest::query()
            ->with(['member:id,full_name,member_code,phone,registration_branch_id'])
            ->where('status', 'pending')
            ->when($branchId, fn ($q) => $q->whereHas(
                'member',
                fn ($m) => $m->where('registration_branch_id', $branchId),
            ));

        $pendingPayouts = (clone $pendingPayoutsQuery)->latest('id')->limit(10)->get();

        return view('admin.dashboard', [
            'memberCount' => Member::query()
                ->when($branchId, fn ($q) => $q->where('registration_branch_id', $branchId))
                ->count(),
            'salesToday' => (int) ($salesToday->sale_count ?? 0),
            'salesTodayUsd' => (float) ($salesToday->sale_total ?? 0),
            'cashInToday' => (float) ($cashToday->cash_in ?? 0),
            'cashOutToday' => (float) ($cashToday->cash_out ?? 0),
            'cashBalance' => (float) ($cashBalance ?? 0),
            'pendingCommissions' => (float) CommissionLedger::query()
                ->where('status', 'pending')
                ->when($branchId, fn ($q) => $q->whereHas(
                    'member',
                    fn ($m) => $m->where('registration_branch_id', $branchId),
                ))
                ->sum('amount_usd'),
            'salesTrend' => $salesTrend,
            'salesTrendMax' => max(1, (float) $salesTrend->max('total')),
            'recentSales' => Sale::query()
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->with(['member:id,full_name', 'client:id,name', 'branch:id,name'])
                ->latest('sold_at')
                ->limit(5)
                ->get(['id', 'number', 'buyer_type', 'member_id', 'client_id', 'branch_id', 'total_usd', 'sold_at']),
            'recentMembers' => Member::query()
                ->when($branchId, fn ($q) => $q->where('registration_branch_id', $branchId))
                ->latest('id')
                ->limit(5)
                ->get(['id', 'full_name', 'member_code', 'created_at']),
            'recentMovements' => CashMovement::query()
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->with(['operationType:id,code,label', 'branch:id,name'])
                ->latest('occurred_at')
                ->limit(5)
                ->get(['id', 'direction', 'category', 'operation_type_id', 'amount_usd', 'branch_id', 'description', 'occurred_at']),
            'pendingPayouts' => $pendingPayouts,
            'pendingPayoutCount' => (clone $pendingPayoutsQuery)->count(),
        ]);
    }
}

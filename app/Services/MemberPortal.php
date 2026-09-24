<?php

namespace App\Services;

use App\Models\Member;
use App\Models\RewardTier;
use Illuminate\Support\Collection;

class MemberPortal
{
    public function __construct(private NetworkVolume $volume) {}

    public function downlineGrouped(Member $root): Collection
    {
        $placed = Member::query()
            ->whereNotNull('placement_parent_id')
            ->orderBy('id')
            ->get(['id', 'full_name', 'member_code', 'username', 'placement_parent_id', 'placement_side']);

        $byParent = $placed->groupBy('placement_parent_id');
        $keep = collect();
        $queue = [(int) $root->id];

        while ($queue !== []) {
            $parentId = array_shift($queue);
            foreach ($byParent->get($parentId, collect()) as $child) {
                $keep->push($child);
                $queue[] = (int) $child->id;
            }
        }

        return $keep->groupBy('placement_parent_id');
    }

    /**
     * @return array<int, float>
     */
    public function subtreeTotals(Member $root, Collection $grouped): array
    {
        $members = collect([$root])->merge($grouped->flatten());

        return $this->volume->subtreeTotalsForMembers($members);
    }

    public function account(Member $member): array
    {
        $pvRow = $member->pvEntries()
            ->selectRaw("COALESCE(SUM(CASE WHEN sync_status = 'pending' THEN pv_amount ELSE 0 END), 0) as pending")
            ->selectRaw("COALESCE(SUM(CASE WHEN sync_status = 'confirmed' THEN pv_amount ELSE 0 END), 0) as confirmed")
            ->first();
        $pvPending = (float) ($pvRow->pending ?? 0);
        $pvConfirmed = (float) ($pvRow->confirmed ?? 0);
        $pvTotal = $pvPending + $pvConfirmed;

        $gainRow = $member->commissions()
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'pending' THEN amount_usd ELSE 0 END), 0) as pending")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'confirmed' THEN amount_usd ELSE 0 END), 0) as confirmed")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'paid' THEN amount_usd ELSE 0 END), 0) as paid")
            ->first();
        $gainsPending = (float) ($gainRow->pending ?? 0);
        $gainsConfirmed = (float) ($gainRow->confirmed ?? 0);
        $gainsPaid = (float) ($gainRow->paid ?? 0);

        $grouped = $this->downlineGrouped($member);
        $subtree = $this->subtreeTotals($member, $grouped);
        $leftChild = $grouped->get($member->id, collect())->firstWhere('placement_side', 'left');
        $rightChild = $grouped->get($member->id, collect())->firstWhere('placement_side', 'right');

        $leftLegPv = $leftChild ? (float) ($subtree[$leftChild->id] ?? 0) : 0.0;
        $rightLegPv = $rightChild ? (float) ($subtree[$rightChild->id] ?? 0) : 0.0;
        $weakLegPv = min($leftLegPv, $rightLegPv);

        $tiers = RewardTier::query()
            ->where('is_active', true)
            ->orderBy('min_pv')
            ->orderBy('sort_order')
            ->get();

        $rewardLedgers = $member->commissions()
            ->where('type', 'reward')
            ->whereNotNull('reward_tier_id')
            ->get()
            ->keyBy('reward_tier_id');

        $prizes = $tiers->map(function (RewardTier $tier) use ($weakLegPv, $rewardLedgers) {
            $ledger = $rewardLedgers->get($tier->id);
            $qualified = $weakLegPv >= (float) $tier->min_pv;
            if ($ledger?->status === 'paid') {
                $state = 'served';
            } elseif ($ledger !== null || $qualified) {
                $state = 'pending';
            } else {
                $state = 'locked';
            }

            return [
                'tier' => $tier,
                'state' => $state,
                'ledger' => $ledger,
            ];
        });

        $nextTier = $tiers->first(fn (RewardTier $tier) => $weakLegPv < (float) $tier->min_pv);

        return [
            'pvPending' => $pvPending,
            'pvConfirmed' => $pvConfirmed,
            'pvTotal' => $pvTotal,
            'gainsPending' => $gainsPending,
            'gainsConfirmed' => $gainsConfirmed,
            'gainsPaid' => $gainsPaid,
            'gainsTotal' => $gainsPending + $gainsConfirmed + $gainsPaid,
            'tiers' => $tiers,
            'prizes' => $prizes,
            'nextTier' => $nextTier,
            'leftLegPv' => $leftLegPv,
            'rightLegPv' => $rightLegPv,
            'weakLegPv' => $weakLegPv,
        ];
    }

    public function alerts(Member $member, int $limit = 12): Collection
    {
        $pv = $member->pvEntries()->latest('occurred_at')->limit($limit)->get()->map(function ($row) {
            return [
                'kind' => 'pv',
                'at' => $row->occurred_at,
                'icon' => 'bi-hexagon',
                'title' => __('messages.alert_pv', [
                    'source' => __('messages.'.$row->source_type),
                    'amount' => number_format((float) $row->pv_amount, 2),
                ]),
                'status' => $row->sync_status,
                'url' => route('member.history'),
            ];
        });

        $gains = $member->commissions()->with('rewardTier')->latest('occurred_at')->limit($limit)->get()->map(function ($row) {
            return [
                'kind' => 'commission',
                'at' => $row->occurred_at,
                'icon' => 'bi-wallet2',
                'title' => __('messages.alert_gain', [
                    'type' => \App\Services\ReportBuilder::commissionLabel($row),
                    'amount' => number_format((float) $row->amount_usd, 2),
                ]),
                'status' => $row->status,
                'url' => route('member.history'),
            ];
        });

        $sales = $member->sales()->latest('sold_at')->limit($limit)->get()->map(function ($row) {
            return [
                'kind' => 'sale',
                'at' => $row->sold_at,
                'icon' => 'bi-bag-check',
                'title' => __('messages.alert_sale', [
                    'number' => $row->number,
                    'amount' => number_format((float) $row->total_usd, 2),
                ]),
                'status' => $row->sync_status,
                'url' => route('member.purchases'),
            ];
        });

        return $pv->concat($gains)->concat($sales)
            ->filter(fn (array $alert) => $alert['at'] !== null)
            ->sortByDesc(fn (array $alert) => $alert['at']->timestamp)
            ->take($limit)
            ->values();
    }
}

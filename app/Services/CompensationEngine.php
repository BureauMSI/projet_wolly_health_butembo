<?php

namespace App\Services;

use App\Models\Client;
use App\Models\CommissionLedger;
use App\Models\EquilibriumRule;
use App\Models\Member;
use App\Models\PvLedger;
use App\Models\RewardTier;
use App\Models\Sale;
use App\Support\PlanConfig;

class CompensationEngine
{
    public function __construct(
        private PlanConfig $plan,
        private SyncOutbox $outbox,
        private WhatsAppNotifier $whatsapp,
        private NetworkVolume $volume,
    ) {}

    /** @var \Illuminate\Support\Collection<int, EquilibriumRule>|null */
    private $equilibriumRules = null;

    /** @var \Illuminate\Support\Collection<int, RewardTier>|null */
    private $rewardTiers = null;

    private function equilibriumRules()
    {
        return $this->equilibriumRules ??= EquilibriumRule::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();
    }

    private function rewardTiers()
    {
        return $this->rewardTiers ??= RewardTier::query()
            ->where('is_active', true)
            ->orderBy('min_pv')
            ->orderBy('sort_order')
            ->get();
    }

    public function onMembership(Member $member): void
    {
        if ($member->sponsor_id === null) {
            return;
        }

        $sponsor = $member->sponsor;
        if ($sponsor === null) {
            return;
        }

        $alreadyCredited = CommissionLedger::query()
            ->where('type', 'sponsorship')
            ->where('related_member_id', $member->id)
            ->exists()
            || PvLedger::query()
                ->where('source_type', 'sponsorship')
                ->where('related_member_id', $member->id)
                ->exists();

        if ($alreadyCredited) {
            return;
        }

        $status = $this->plan->ledgerStatus();
        $amount = (float) $this->plan->decimal('sponsorship_amount_usd');
        $pv = (float) $this->plan->decimal('sponsorship_pv');

        if ($amount > 0) {
            $this->writeCommission($sponsor, 'sponsorship', $amount, $status, $member, null, null, null);
            if (filled($sponsor->phone)) {
                $this->whatsapp->enqueue($sponsor->phone, 'bonus', $sponsor->locale ?: 'fr', [
                    'type' => __('messages.sponsorship'),
                    'amount' => number_format($amount, 2, '.', ''),
                ]);
            }
        }

        if ($pv > 0) {
            $this->writePv($sponsor, 'sponsorship', $pv, $status, null, null, $member);
        }
    }

    /**
     * When a member is placed in the binary tree, credit equilibrium up the placement line
     * (same parametrized amounts as a PV sale: default 4 USD for levels 1–4, 1 USD after).
     */
    public function onPlacement(Member $member): void
    {
        $member->loadMissing('placementParent');

        if ($member->placement_parent_id === null) {
            return;
        }

        $this->ensurePlacementEquilibria($member);
    }

    /**
     * Credit any missing placement-equilibrium generations for a member (idempotent).
     */
    public function ensurePlacementEquilibria(Member $member): int
    {
        $member->loadMissing('placementParent');

        if ($member->placement_parent_id === null) {
            return 0;
        }

        $this->volume->warmAdjacency();
        $status = $this->plan->ledgerStatus();
        $created = 0;
        $ancestor = $member->placementParent;
        $equilibrium = 1;

        while ($ancestor !== null && $equilibrium <= 32) {
            $ancestor->loadMissing('placementParent');
            $exists = CommissionLedger::query()
                ->where('type', 'equilibrium')
                ->where('related_member_id', $member->id)
                ->where('generation', $equilibrium)
                ->whereNull('sale_id')
                ->exists();

            if (! $exists) {
                $rule = $this->matchingRule($equilibrium);
                if ($rule && $this->legQualifies($ancestor, $rule)) {
                    $amount = $this->ruleAmount($rule, null);
                    if ($amount > 0) {
                        $this->writeCommission($ancestor, 'equilibrium', $amount, $status, $member, $equilibrium, null, null);
                        $created++;
                    }
                }
            }

            $ancestor = $ancestor->placementParent;
            $equilibrium++;
        }

        return $created;
    }

    /**
     * Credit missing placement equilibria for members already in the tree.
     */
    public function backfillPlacementEquilibria(): int
    {
        $created = 0;

        Member::query()
            ->whereNotNull('placement_parent_id')
            ->orderBy('id')
            ->each(function (Member $member) use (&$created) {
                $created += $this->ensurePlacementEquilibria($member);
            });

        return $created;
    }

    public function creditJoinPack(Member $member): void
    {
        $pv = (float) ($member->membership_pv ?? $this->plan->decimal('membership_pv_threshold'));
        if ($pv <= 0) {
            return;
        }

        $alreadyCredited = PvLedger::query()
            ->where('member_id', $member->id)
            ->where('source_type', 'membership')
            ->exists();

        if ($alreadyCredited) {
            return;
        }

        $status = $this->plan->ledgerStatus();
        $this->writePv($member, 'membership', $pv, $status, null, null, null);
    }

    public function onSale(Sale $sale): void
    {
        $sale->loadMissing(['items', 'member.sponsor', 'client.referrer']);
        $this->volume->warmAdjacency();
        $status = $this->plan->ledgerStatus();
        $mode = ($sale->benefit_mode ?: 'pv') === 'percent' ? 'percent' : 'pv';
        $creditPercent = $mode === 'percent';
        $creditPv = $mode === 'pv';

        if ($creditPercent) {
            $this->creditProductPercent($sale, $status);
        }

        $pvTotal = (float) $sale->items->sum('pv');
        if (! $creditPv || $pvTotal <= 0) {
            return;
        }

        if ($sale->buyer_type === 'member' && $sale->member) {
            $sale->member->loadMissing('placementParent');
            $this->writePv($sale->member, 'own_purchase', $pvTotal, $status, $sale, null, null);
            $this->applyEquilibrium($sale->member, $sale, $status);

            return;
        }

        if ($sale->buyer_type === 'client' && $sale->client?->referrer) {
            $referrer = $sale->client->referrer;
            $referrer->loadMissing('placementParent');
            $this->writePv($referrer, 'referred_client_purchase', $pvTotal, $status, $sale, $sale->client, null);
            $sale->client->increment('accumulated_pv', $pvTotal);
            $sale->client->refresh();
            $this->outbox->enqueue('client', $sale->client->uuid, 'update', $sale->client->toArray(), $this->plan->originDeviceId());
            $this->maybeThresholdAlert($sale->client);
            $this->applyEquilibrium($referrer, $sale, $status);
        }
    }

    private function creditProductPercent(Sale $sale, string $status): void
    {
        $amount = round((float) $sale->items->sum('commission_usd'), 2);
        if ($amount <= 0) {
            return;
        }

        $related = null;
        if ($sale->buyer_type === 'member') {
            $beneficiary = $sale->member?->sponsor;
            $related = $sale->member;
        } else {
            $beneficiary = $sale->client?->referrer;
        }

        if ($beneficiary === null) {
            return;
        }

        $this->writeCommission($beneficiary, 'product_percent', $amount, $status, $related, null, null, $sale);
        if (filled($beneficiary->phone)) {
            $this->whatsapp->enqueue($beneficiary->phone, 'bonus', $beneficiary->locale ?: 'fr', [
                'type' => __('messages.product_percent'),
                'amount' => number_format($amount, 2, '.', ''),
            ]);
        }
    }

    private function maybeThresholdAlert(Client $client): void
    {
        $threshold = (float) $this->plan->decimal('membership_pv_threshold');
        if ($threshold <= 0 || $client->threshold_alerted_at !== null) {
            return;
        }

        if ((float) $client->accumulated_pv < $threshold) {
            return;
        }

        $client->update(['threshold_alerted_at' => now()]);
        $this->outbox->enqueue('client', $client->uuid, 'update', $client->fresh()->toArray(), $this->plan->originDeviceId());

        $phone = $client->referrer?->phone ?: $client->phone;
        if (filled($phone)) {
            $this->whatsapp->enqueue($phone, 'membership-threshold', $client->referrer?->locale ?: 'fr', [
                'name' => $client->name,
                'pv' => (string) $client->accumulated_pv,
            ]);
        }
    }

    public function refreshRewards(Member $member): void
    {
        $this->maybeRewardsUpTree($member, $this->plan->ledgerStatus(), null);
    }

    private function maybeRewardsUpTree(Member $member, string $status, ?Sale $sale = null): void
    {
        $node = $member;
        $guard = 0;
        while ($node !== null && $guard < 64) {
            $node->loadMissing('placementParent');
            $this->maybeRewards($node, $status, $sale);
            $node = $node->placementParent;
            $guard++;
        }
    }

    private function maybeRewards(Member $member, string $status, ?Sale $sale = null): void
    {
        $weakLeg = $this->volume->weakLegPv($member);
        $tiers = $this->rewardTiers();

        foreach ($tiers as $tier) {
            $minPv = (float) $tier->min_pv;
            if ($weakLeg < $minPv) {
                continue;
            }
            if ($tier->max_pv !== null && $weakLeg > (float) $tier->max_pv) {
                continue;
            }

            $already = CommissionLedger::query()
                ->where('member_id', $member->id)
                ->where('type', 'reward')
                ->where('reward_tier_id', $tier->id)
                ->exists();

            if ($already) {
                continue;
            }

            $amount = (float) ($tier->amount_usd ?? 0);
            $this->writeCommission($member, 'reward', $amount, $status, null, null, $tier->id, $sale);
            if ($amount > 0 && filled($member->phone)) {
                $this->whatsapp->enqueue($member->phone, 'bonus', $member->locale ?: 'fr', [
                    'type' => $tier->label,
                    'amount' => number_format($amount, 2, '.', ''),
                ]);
            }
        }
    }

    /**
     * Credit equilibrium bonuses up the placement line.
     * Levels 1–4 use scope generations_1_4; after the 4th equilibrium, scope after_generation_4
     * (flat amount from equilibrium_rules — never hardcoded here).
     * Triggered by placement and by PV sales.
     */
    private function applyEquilibrium(Member $source, ?Sale $sale, string $status): void
    {
        $source->loadMissing('placementParent');
        $ancestor = $source->placementParent;
        $equilibrium = 1;

        while ($ancestor !== null && $equilibrium <= 32) {
            $ancestor->loadMissing('placementParent');
            $rule = $this->matchingRule($equilibrium);
            if ($rule && $this->legQualifies($ancestor, $rule)) {
                $amount = $this->ruleAmount($rule, $sale);
                if ($amount > 0) {
                    $this->writeCommission($ancestor, 'equilibrium', $amount, $status, $source, $equilibrium, null, $sale);
                }
            }

            $ancestor = $ancestor->placementParent;
            $equilibrium++;
        }
    }

    private function matchingRule(int $equilibrium): ?EquilibriumRule
    {
        $scope = $equilibrium <= 4 ? 'generations_1_4' : 'after_generation_4';
        $rules = $this->equilibriumRules()->where('scope', $scope);
        $exact = $rules->firstWhere('generation', $equilibrium);

        return $exact ?: $rules->first(fn (EquilibriumRule $rule) => $rule->generation === null);
    }

    private function ruleAmount(EquilibriumRule $rule, ?Sale $sale): float
    {
        if ($rule->percent !== null && (float) $rule->percent > 0) {
            if ($sale === null) {
                return 0.0;
            }

            return round((float) $sale->total_usd * ((float) $rule->percent) / 100, 2);
        }

        return round((float) ($rule->amount_usd ?? 0), 2);
    }

    private function legQualifies(Member $ancestor, EquilibriumRule $rule): bool
    {
        if ($rule->min_leg_pv === null) {
            return true;
        }

        $left = $this->volume->legPv($ancestor, 'left');
        $right = $this->volume->legPv($ancestor, 'right');

        return min($left, $right) >= (float) $rule->min_leg_pv;
    }

    private function writePv(
        Member $member,
        string $source,
        float $amount,
        string $status,
        ?Sale $sale,
        ?Client $client,
        ?Member $related,
    ): void {
        $row = PvLedger::query()->create([
            'origin_device_id' => $this->plan->originDeviceId(),
            'member_id' => $member->id,
            'source_type' => $source,
            'pv_amount' => $amount,
            'sale_id' => $sale?->id,
            'client_id' => $client?->id,
            'related_member_id' => $related?->id,
            'occurred_at' => now(),
            'sync_status' => $status,
        ]);

        $this->outbox->enqueue('pv_ledger', $row->uuid, 'create', $row->toArray(), $this->plan->originDeviceId());
        $this->volume->forgetSubtreeCaches();
        $this->maybeRewardsUpTree($member, $status, $sale);
    }

    private function writeCommission(
        Member $member,
        string $type,
        float $amount,
        string $status,
        ?Member $related,
        ?int $generation,
        ?int $rewardTierId,
        ?Sale $sale = null,
    ): void {
        $row = CommissionLedger::query()->create([
            'origin_device_id' => $this->plan->originDeviceId(),
            'member_id' => $member->id,
            'type' => $type,
            'amount_usd' => $amount,
            'related_member_id' => $related?->id,
            'generation' => $generation,
            'reward_tier_id' => $rewardTierId,
            'sale_id' => $sale?->id,
            'status' => $status === 'confirmed' ? 'confirmed' : 'pending',
            'occurred_at' => now(),
        ]);

        $this->outbox->enqueue('commission_ledger', $row->uuid, 'create', $row->toArray(), $this->plan->originDeviceId());
    }
}

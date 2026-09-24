<?php

namespace App\Support;

use App\Models\EquilibriumRule;
use App\Models\Member;
use Illuminate\Support\Collection;

class EquilibriumPath
{
    /**
     * Map of ancestor member_id => equilibrium level (1 = parent of source).
     *
     * @param  Collection<int, Member>  $membersById
     * @return array<int, int>
     */
    public static function levelsAbove(Member $source, Collection $membersById): array
    {
        $levels = [];
        $current = $source;
        $n = 1;

        while ($current->placement_parent_id !== null && $n <= 32) {
            $parentId = (int) $current->placement_parent_id;
            $levels[$parentId] = $n;
            $current = $membersById->get($parentId);
            if ($current === null) {
                break;
            }
            $n++;
        }

        return $levels;
    }

    /**
     * Parametrized amounts keyed by equilibrium level (5+ uses after-4 rule).
     *
     * @return array{near: float, after: float}
     */
    public static function amounts(): array
    {
        $near = EquilibriumRule::query()
            ->where('is_active', true)
            ->where('scope', 'generations_1_4')
            ->orderBy('id')
            ->get();

        $nearAmount = 4.0;
        $exact1 = $near->firstWhere('generation', 1);
        if ($exact1) {
            $nearAmount = (float) $exact1->amount_usd;
        } else {
            $fallback = $near->firstWhere('generation', null) ?? $near->first();
            if ($fallback) {
                $nearAmount = (float) $fallback->amount_usd;
            }
        }

        $after = EquilibriumRule::query()
            ->where('is_active', true)
            ->where('scope', 'after_generation_4')
            ->whereNull('generation')
            ->value('amount_usd');

        return [
            'near' => $nearAmount,
            'after' => $after !== null ? (float) $after : 1.0,
        ];
    }

    public static function amountForLevel(int $level, ?array $amounts = null): float
    {
        $amounts ??= self::amounts();

        return $level <= 4 ? $amounts['near'] : $amounts['after'];
    }
}

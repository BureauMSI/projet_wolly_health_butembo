<?php

namespace App\Services;

use App\Models\Member;
use App\Models\PvLedger;
use Illuminate\Support\Collection;

class NetworkVolume
{
    /** @var array<int, list<int>>|null */
    private ?array $childrenByParent = null;

    /** @var array<int, array{left:?int, right:?int}>|null */
    private ?array $childOnSide = null;

    /** @var array<int, float> */
    private array $subtreeCache = [];

    /** @var array<int, list<int>> */
    private array $descendantCache = [];

    /**
     * @param  iterable<int, Member|object>|null  $members
     */
    public function warmAdjacency(?iterable $members = null): void
    {
        $rows = $members === null
            ? Member::query()->get(['id', 'placement_parent_id', 'placement_side'])
            : collect($members);

        $children = [];
        $sides = [];
        foreach ($rows as $member) {
            $id = (int) $member->id;
            $parentId = $member->placement_parent_id !== null ? (int) $member->placement_parent_id : null;
            if ($parentId === null) {
                continue;
            }
            $children[$parentId][] = $id;
            $side = (string) ($member->placement_side ?? '');
            if ($side === 'left' || $side === 'right') {
                $sides[$parentId][$side] = $id;
            }
        }

        $this->childrenByParent = $children;
        $this->childOnSide = $sides;
        $this->subtreeCache = [];
        $this->descendantCache = [];
    }

    public function forgetAdjacency(): void
    {
        $this->childrenByParent = null;
        $this->childOnSide = null;
        $this->subtreeCache = [];
        $this->descendantCache = [];
    }

    public function forgetSubtreeCaches(): void
    {
        $this->subtreeCache = [];
    }

    private function ensureAdjacency(): void
    {
        if ($this->childrenByParent === null) {
            $this->warmAdjacency();
        }
    }

    public function descendantIds(int $rootId): array
    {
        if (isset($this->descendantCache[$rootId])) {
            return $this->descendantCache[$rootId];
        }

        $this->ensureAdjacency();

        $ids = [$rootId];
        $frontier = [$rootId];

        while ($frontier !== []) {
            $next = [];
            foreach ($frontier as $parentId) {
                foreach ($this->childrenByParent[$parentId] ?? [] as $childId) {
                    if (! in_array($childId, $ids, true)) {
                        $ids[] = $childId;
                        $next[] = $childId;
                    }
                }
            }
            $frontier = $next;
        }

        return $this->descendantCache[$rootId] = $ids;
    }

    public function subtreePv(Member $root): float
    {
        $id = (int) $root->id;
        if (array_key_exists($id, $this->subtreeCache)) {
            return $this->subtreeCache[$id];
        }

        return $this->subtreeCache[$id] = (float) PvLedger::query()
            ->whereIn('member_id', $this->descendantIds($id))
            ->sum('pv_amount');
    }

    public function legPv(Member $parent, string $side): float
    {
        $this->ensureAdjacency();
        $childId = $this->childOnSide[(int) $parent->id][$side] ?? null;
        if ($childId === null) {
            $child = $parent->childOn($side);

            return $child === null ? 0.0 : $this->subtreePv($child);
        }

        if (array_key_exists($childId, $this->subtreeCache)) {
            return $this->subtreeCache[$childId];
        }

        return $this->subtreeCache[$childId] = (float) PvLedger::query()
            ->whereIn('member_id', $this->descendantIds($childId))
            ->sum('pv_amount');
    }

    public function weakLegPv(Member $parent): float
    {
        return min($this->legPv($parent, 'left'), $this->legPv($parent, 'right'));
    }

    /**
     * @param  array<int>  $memberIds
     * @return array<int, float>
     */
    public function subtreeTotals(array $memberIds): array
    {
        $memberIds = array_values(array_unique(array_map('intval', $memberIds)));
        if ($memberIds === []) {
            return [];
        }

        $this->ensureAdjacency();

        $allIds = [];
        foreach ($memberIds as $id) {
            foreach ($this->descendantIds($id) as $descendantId) {
                $allIds[$descendantId] = true;
            }
        }
        $allIds = array_map('intval', array_keys($allIds));

        $own = PvLedger::query()
            ->whereIn('member_id', $allIds)
            ->selectRaw('member_id, SUM(pv_amount) as total')
            ->groupBy('member_id')
            ->pluck('total', 'member_id');

        $memo = [];
        $walk = function (int $id) use (&$walk, &$memo, $own): float {
            if (array_key_exists($id, $memo)) {
                return $memo[$id];
            }

            $total = (float) ($own[$id] ?? 0);
            foreach ($this->childrenByParent[$id] ?? [] as $childId) {
                $total += $walk((int) $childId);
            }

            return $memo[$id] = $total;
        };

        $totals = [];
        foreach ($memberIds as $id) {
            $totals[$id] = $walk($id);
            $this->subtreeCache[$id] = $totals[$id];
        }

        return $totals;
    }

    /**
     * @param  Collection<int, Member>|iterable<int, Member>  $members
     * @return array<int, float>
     */
    public function subtreeTotalsForMembers(iterable $members): array
    {
        $collection = collect($members);
        $this->warmAdjacency($collection);

        return $this->subtreeTotals($collection->pluck('id')->map(fn ($id) => (int) $id)->all());
    }
}

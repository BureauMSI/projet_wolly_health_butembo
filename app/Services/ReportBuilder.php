<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\CashMovement;
use App\Models\CommissionLedger;
use App\Models\Institution;
use App\Models\Member;
use App\Models\PvLedger;
use App\Models\Sale;
use App\Models\User;
use App\Support\PlanConfig;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Storage;

class ReportBuilder
{
    public const TYPES = [
        'summary',
        'cash_book',
        'cash_movements',
        'sales_journal',
        'commissions_paid',
        'commissions_unpaid',
        'members',
        'tree',
    ];

    public const MEMBER_TYPES = [
        'member_statement',
        'member_pv',
        'member_commissions_paid',
        'member_commissions_unpaid',
        'member_purchases',
        'member_network',
        'member_tree',
        'member_sheet',
    ];

    private const ALIASES = [
        'all' => 'summary',
        'sales' => 'sales_journal',
        'cash' => 'cash_book',
        'in' => 'cash_movements',
        'out' => 'cash_movements',
        'commissions' => 'commissions_unpaid',
    ];

    public function __construct(private NetworkVolume $volume) {}

    public function normalize(string $type): string
    {
        $type = self::ALIASES[$type] ?? $type;
        abort_unless(in_array($type, self::TYPES, true), 404);

        return $type;
    }

    /**
     * @return array<int, array{type: string, group: string, icon: string, roles: array<int, string>}>
     */
    public function catalog(User $user): array
    {
        $items = [
            ['type' => 'summary', 'group' => 'finance', 'icon' => 'bi-clipboard-data', 'roles' => ['admin', 'manager', 'accountant']],
            ['type' => 'cash_book', 'group' => 'finance', 'icon' => 'bi-journal-bookmark', 'roles' => ['admin', 'manager', 'accountant', 'cashier']],
            ['type' => 'cash_movements', 'group' => 'finance', 'icon' => 'bi-arrow-left-right', 'roles' => ['admin', 'manager', 'accountant', 'cashier']],
            ['type' => 'sales_journal', 'group' => 'finance', 'icon' => 'bi-receipt', 'roles' => ['admin', 'manager', 'accountant', 'cashier']],
            ['type' => 'commissions_paid', 'group' => 'finance', 'icon' => 'bi-wallet2', 'roles' => ['admin', 'manager', 'accountant']],
            ['type' => 'commissions_unpaid', 'group' => 'finance', 'icon' => 'bi-hourglass-split', 'roles' => ['admin', 'manager', 'accountant']],
            ['type' => 'members', 'group' => 'network', 'icon' => 'bi-people', 'roles' => ['admin', 'manager', 'accountant']],
            ['type' => 'tree', 'group' => 'network', 'icon' => 'bi-diagram-3', 'roles' => ['admin', 'manager', 'accountant', 'cashier']],
        ];

        return array_values(array_filter($items, fn (array $item) => in_array($user->role, $item['roles'], true)));
    }

    public function canView(User $user, string $type): bool
    {
        $type = $this->normalize($type);

        foreach ($this->catalog($user) as $item) {
            if ($item['type'] === $type) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(User $user, string $type, array $filters): array
    {
        $type = $this->normalize($type);
        abort_unless($this->canView($user, $type), 403);

        $from = $filters['from'] ?? now()->startOfMonth()->toDateString();
        $to = $filters['to'] ?? now()->toDateString();
        $branchId = $user->isAdmin() ? (int) ($filters['branch_id'] ?? 0) ?: null : $user->branch_id;
        $institution = Institution::query()->first();
        $branch = $branchId ? Branch::query()->find($branchId) : null;

        $base = [
            'type' => $type,
            'from' => $from,
            'to' => $to,
            'branchId' => $branchId,
            'branch' => $branch,
            'institution' => $institution,
            'branches' => Branch::query()->orderBy('name')->get(),
            'title' => __('messages.report_'.$type),
            'printLandscape' => $type === 'tree',
        ];

        return $base + match ($type) {
            'summary' => $this->summary($from, $to, $branchId),
            'cash_book' => $this->cashBook($from, $to, $branchId),
            'cash_movements' => $this->cashMovements($from, $to, $branchId),
            'sales_journal' => $this->salesJournal($from, $to, $branchId),
            'commissions_paid' => $this->commissions($from, $to, $branchId, ['paid']),
            'commissions_unpaid' => $this->commissions($from, $to, $branchId, ['pending', 'confirmed']),
            'members' => $this->members($from, $to, $branchId),
            'tree' => $this->staffTree(),
        };
    }

    /**
     * @return array<int, array{type: string, icon: string}>
     */
    public function memberCatalog(): array
    {
        return [
            ['type' => 'member_sheet', 'icon' => 'bi-person-vcard'],
            ['type' => 'member_statement', 'icon' => 'bi-clipboard-data'],
            ['type' => 'member_pv', 'icon' => 'bi-hexagon'],
            ['type' => 'member_commissions_paid', 'icon' => 'bi-wallet2'],
            ['type' => 'member_commissions_unpaid', 'icon' => 'bi-hourglass-split'],
            ['type' => 'member_purchases', 'icon' => 'bi-bag-check'],
            ['type' => 'member_network', 'icon' => 'bi-diagram-3'],
            ['type' => 'member_tree', 'icon' => 'bi-diagram-2'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function memberPayload(Member $member, string $type, array $filters): array
    {
        abort_unless(in_array($type, self::MEMBER_TYPES, true), 404);

        $from = $filters['from'] ?? now()->startOfMonth()->toDateString();
        $to = $filters['to'] ?? now()->toDateString();
        $institution = Institution::query()->first();
        $account = app(MemberPortal::class)->account($member);

        $base = [
            'type' => $type,
            'from' => $from,
            'to' => $to,
            'branchId' => $member->registration_branch_id,
            'branch' => $member->registrationBranch,
            'institution' => $institution,
            'title' => __('messages.report_'.$type),
            'subjectMember' => $member,
            'audience' => 'member',
            'printLandscape' => $type === 'member_tree',
        ];

        return $base + match ($type) {
            'member_statement' => $account + [
                'pvEntries' => $member->pvEntries()->whereDate('occurred_at', '>=', $from)->whereDate('occurred_at', '<=', $to)->orderBy('occurred_at')->get(),
                'commissions' => $member->commissions()->with(['rewardTier', 'relatedMember'])->whereDate('occurred_at', '>=', $from)->whereDate('occurred_at', '<=', $to)->orderBy('occurred_at')->get(),
            ],
            'member_pv' => [
                'pvEntries' => $member->pvEntries()->whereDate('occurred_at', '>=', $from)->whereDate('occurred_at', '<=', $to)->orderBy('occurred_at')->get(),
            ],
            'member_commissions_paid' => $this->memberCommissions($member, $from, $to, ['paid']),
            'member_commissions_unpaid' => $this->memberCommissions($member, $from, $to, ['pending', 'confirmed']),
            'member_purchases' => $this->memberPurchases($member, $from, $to),
            'member_network' => $this->memberNetwork($member),
            'member_tree' => $this->memberTree($member),
            'member_sheet' => $this->membershipSheet($member, 'member'),
        };
    }

    /**
     * @param  array<int, string>  $statuses
     * @return array<string, mixed>
     */
    private function memberCommissions(Member $member, string $from, string $to, array $statuses): array
    {
        $commissions = $member->commissions()
            ->with(['rewardTier', 'member', 'relatedMember'])
            ->whereIn('status', $statuses)
            ->whereDate('occurred_at', '>=', $from)
            ->whereDate('occurred_at', '<=', $to)
            ->orderBy('occurred_at')
            ->get();

        return [
            'commissions' => $commissions,
            'commissionsTotal' => $commissions->sum('amount_usd'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function memberPurchases(Member $member, string $from, string $to): array
    {
        $sales = $member->sales()
            ->with(['items.product', 'branch', 'cashier'])
            ->whereDate('sold_at', '>=', $from)
            ->whereDate('sold_at', '<=', $to)
            ->orderBy('sold_at')
            ->get();

        return [
            'sales' => $sales,
            'salesTotal' => $sales->sum('total_usd'),
            'salesPv' => $sales->sum(fn (Sale $sale) => $sale->items->sum('pv')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function memberNetwork(Member $member): array
    {
        $portal = app(MemberPortal::class);
        $grouped = $portal->downlineGrouped($member);
        $subtree = $portal->subtreeTotals($member, $grouped);
        $people = $grouped->flatten();

        $rows = $people->map(function (Member $person) use ($subtree) {
            return [
                'member' => $person,
                'pv' => (float) ($subtree[$person->id] ?? 0),
            ];
        });

        $direct = $grouped->get($member->id, collect());
        $left = $direct->firstWhere('placement_side', 'left');
        $right = $direct->firstWhere('placement_side', 'right');

        return [
            'rows' => $rows,
            'downlineCount' => $people->count(),
            'leftLegPv' => $left ? (float) ($subtree[$left->id] ?? 0) : 0.0,
            'rightLegPv' => $right ? (float) ($subtree[$right->id] ?? 0) : 0.0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function memberTree(Member $member): array
    {
        $portal = app(MemberPortal::class);
        $grouped = $portal->downlineGrouped($member);
        $subtreePv = $portal->subtreeTotals($member, $grouped);
        $network = $this->memberNetwork($member);

        return $network + [
            'roots' => collect([$member]),
            'grouped' => $grouped,
            'subtreePv' => $subtreePv,
            'highlightId' => $member->id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function membershipSheet(Member $member, string $audience = 'staff'): array
    {
        $member->loadMissing(['registrationBranch']);
        $institution = Institution::query()->first();
        $defaultPassword = (string) app(PlanConfig::class)->get('default_member_password');
        $usesDefaultPassword = $defaultPassword !== '' && Hash::check($defaultPassword, $member->getAuthPassword());

        return [
            'type' => 'member_sheet',
            'institution' => $institution,
            'branch' => $member->registrationBranch,
            'subjectMember' => $member,
            'title' => __('messages.membership_sheet'),
            'from' => $member->joined_at?->toDateString() ?? now()->toDateString(),
            'to' => now()->toDateString(),
            'audience' => $audience,
            'member' => $member,
            'photoSrc' => $this->memberPhotoSrc($member),
            'portalUrl' => route('member.login'),
            'loginPassword' => $usesDefaultPassword ? $defaultPassword : null,
        ];
    }

    private function memberPhotoSrc(Member $member): string
    {
        if (! filled($member->photo_path)) {
            return '';
        }

        if (Storage::disk('public')->exists($member->photo_path)) {
            return asset('storage/'.$member->photo_path);
        }

        if (file_exists(public_path($member->photo_path))) {
            return asset($member->photo_path);
        }

        return '';
    }

    /**
     * @return array<string, mixed>
     */
    public function staffTree(): array
    {
        $members = Member::query()->orderBy('id')->get();
        $root = $members->first();
        $grouped = $members->groupBy('placement_parent_id');
        $eqAmounts = \App\Support\EquilibriumPath::amounts();

        return [
            'roots' => $root ? collect([$root]) : collect(),
            'grouped' => $grouped,
            'subtreePv' => $this->volume->subtreeTotals($members->pluck('id')->all()),
            'highlightId' => $root?->id,
            'eqLevels' => [],
            'eqAmounts' => $eqAmounts,
            'printLandscape' => true,
        ];
    }

    public static function commissionLabel(CommissionLedger $commission): string
    {
        if ($commission->type === 'reward' && $commission->rewardTier) {
            return (string) $commission->rewardTier->label;
        }

        if ($commission->type === 'equilibrium') {
            $n = $commission->generation;
            if ($n !== null && (int) $n > 0) {
                return __('messages.equilibrium_n', ['n' => (int) $n]);
            }

            return (string) __('messages.equilibrium');
        }

        $key = 'messages.'.$commission->type;

        return Lang::has($key) ? __($key) : (string) $commission->type;
    }

    /**
     * @return array<string, mixed>
     */
    private function cashQueryBase(string $from, string $to, ?int $branchId)
    {
        return CashMovement::query()
            ->with(['branch', 'operationType', 'user'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereDate('occurred_at', '>=', $from)
            ->whereDate('occurred_at', '<=', $to)
            ->orderBy('occurred_at')
            ->orderBy('id');
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(string $from, string $to, ?int $branchId): array
    {
        $sales = $this->salesJournal($from, $to, $branchId);
        $cash = $this->cashMovements($from, $to, $branchId);
        $paid = $this->commissions($from, $to, $branchId, ['paid']);
        $unpaid = $this->commissions($from, $to, $branchId, ['pending', 'confirmed']);

        return [
            'sales' => $sales['sales'],
            'salesTotal' => $sales['salesTotal'],
            'cash' => $cash['cash'],
            'cashIn' => $cash['cashIn'],
            'cashOut' => $cash['cashOut'],
            'cashNet' => $cash['cashNet'],
            'commissionsPaid' => $paid['commissions'],
            'commissionsPaidTotal' => $paid['commissionsTotal'],
            'commissionsUnpaid' => $unpaid['commissions'],
            'commissionsUnpaidTotal' => $unpaid['commissionsTotal'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function cashBook(string $from, string $to, ?int $branchId): array
    {
        $opening = (float) CashMovement::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereDate('occurred_at', '<', $from)
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'in' THEN amount_usd ELSE -amount_usd END), 0) as balance")
            ->value('balance');

        $movements = $this->cashQueryBase($from, $to, $branchId)->get();
        $rows = [];
        $balance = $opening;
        $in = 0.0;
        $out = 0.0;

        foreach ($movements as $movement) {
            $inUsd = $movement->direction === 'in' ? (float) $movement->amount_usd : 0.0;
            $outUsd = $movement->direction === 'out' ? (float) $movement->amount_usd : 0.0;
            $balance += $inUsd - $outUsd;
            $in += $inUsd;
            $out += $outUsd;
            $rows[] = [
                'movement' => $movement,
                'in' => $inUsd,
                'out' => $outUsd,
                'balance' => $balance,
            ];
        }

        return [
            'opening' => $opening,
            'rows' => $rows,
            'cashIn' => $in,
            'cashOut' => $out,
            'closing' => $balance,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function cashMovements(string $from, string $to, ?int $branchId): array
    {
        $cash = $this->cashQueryBase($from, $to, $branchId)->get();
        $cashIn = (float) $cash->where('direction', 'in')->sum('amount_usd');
        $cashOut = (float) $cash->where('direction', 'out')->sum('amount_usd');

        return [
            'cash' => $cash,
            'cashIn' => $cashIn,
            'cashOut' => $cashOut,
            'cashNet' => round($cashIn - $cashOut, 2),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function salesJournal(string $from, string $to, ?int $branchId): array
    {
        $sales = Sale::query()
            ->with(['member', 'client', 'cashier', 'branch', 'items'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereDate('sold_at', '>=', $from)
            ->whereDate('sold_at', '<=', $to)
            ->orderBy('sold_at')
            ->orderBy('id')
            ->get();

        return [
            'sales' => $sales,
            'salesTotal' => $sales->sum('total_usd'),
            'salesPv' => $sales->sum(fn (Sale $sale) => $sale->items->sum('pv')),
        ];
    }

    /**
     * @param  array<int, string>  $statuses
     * @return array<string, mixed>
     */
    private function commissions(string $from, string $to, ?int $branchId, array $statuses): array
    {
        $commissions = CommissionLedger::query()
            ->with(['member', 'rewardTier', 'relatedMember'])
            ->whereIn('status', $statuses)
            ->when($branchId, fn ($q) => $q->whereHas('member', fn ($m) => $m->where('registration_branch_id', $branchId)))
            ->whereDate('occurred_at', '>=', $from)
            ->whereDate('occurred_at', '<=', $to)
            ->orderBy('occurred_at')
            ->get();

        return [
            'commissions' => $commissions,
            'commissionsTotal' => $commissions->sum('amount_usd'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function members(string $from, string $to, ?int $branchId): array
    {
        $members = Member::query()
            ->with(['registrationBranch', 'sponsor', 'placementChildren'])
            ->when($branchId, fn ($q) => $q->where('registration_branch_id', $branchId))
            ->orderBy('member_code')
            ->get();

        $ids = $members->pluck('id')->all();
        $subtrees = $this->volume->subtreeTotals($ids);

        $pvByMember = PvLedger::query()
            ->whereIn('member_id', $ids ?: [0])
            ->selectRaw('member_id, sync_status, SUM(pv_amount) as total')
            ->groupBy('member_id', 'sync_status')
            ->get()
            ->groupBy('member_id');

        $gainsAll = CommissionLedger::query()
            ->whereIn('member_id', $ids ?: [0])
            ->selectRaw('member_id, status, SUM(amount_usd) as total')
            ->groupBy('member_id', 'status')
            ->get()
            ->groupBy('member_id');

        $gainsPeriod = CommissionLedger::query()
            ->whereIn('member_id', $ids ?: [0])
            ->whereDate('occurred_at', '>=', $from)
            ->whereDate('occurred_at', '<=', $to)
            ->selectRaw('member_id, status, SUM(amount_usd) as total')
            ->groupBy('member_id', 'status')
            ->get()
            ->groupBy('member_id');

        $rows = $members->map(function (Member $member) use ($subtrees, $pvByMember, $gainsAll, $gainsPeriod) {
            $pv = $pvByMember->get($member->id, collect());
            $pvPending = (float) $pv->firstWhere('sync_status', 'pending')?->total;
            $pvConfirmed = (float) $pv->firstWhere('sync_status', 'confirmed')?->total;
            $all = $gainsAll->get($member->id, collect());
            $period = $gainsPeriod->get($member->id, collect());
            $unpaid = fn (Collection $set) => (float) $set->whereIn('status', ['pending', 'confirmed'])->sum('total');
            $paid = fn (Collection $set) => (float) $set->firstWhere('status', 'paid')?->total;
            $left = $member->placementChildren->firstWhere('placement_side', 'left');
            $right = $member->placementChildren->firstWhere('placement_side', 'right');
            $leftPv = $left ? (float) ($subtrees[$left->id] ?? 0) : 0.0;
            $rightPv = $right ? (float) ($subtrees[$right->id] ?? 0) : 0.0;

            return [
                'member' => $member,
                'pvPending' => $pvPending,
                'pvConfirmed' => $pvConfirmed,
                'pvTotal' => $pvPending + $pvConfirmed,
                'gainsUnpaid' => $unpaid($all),
                'gainsPaid' => $paid($all),
                'gainsPeriodUnpaid' => $unpaid($period),
                'gainsPeriodPaid' => $paid($period),
                'weakLegPv' => min($leftPv, $rightPv),
            ];
        });

        return [
            'rows' => $rows,
            'membersCount' => $members->count(),
            'pvTotal' => $rows->sum('pvTotal'),
            'gainsUnpaidTotal' => $rows->sum('gainsUnpaid'),
            'gainsPaidTotal' => $rows->sum('gainsPaid'),
        ];
    }
}

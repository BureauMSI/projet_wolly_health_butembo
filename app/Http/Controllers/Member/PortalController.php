<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateMemberProfileRequest;
use App\Models\Member;
use App\Services\AuditLogger;
use App\Services\MemberPortal;
use App\Services\PayoutRequestService;
use App\Services\SyncOutbox;
use App\Support\Listing;
use App\Support\PlanConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PortalController extends Controller
{
    public function __construct(private MemberPortal $portal) {}

    public function dashboard(PlanConfig $plan, PayoutRequestService $payouts): View
    {
        $member = $this->member();
        $account = $this->portal->account($member);
        $available = $payouts->availableBalance($member);

        return view('member.dashboard', array_merge($account, [
            'member' => $member,
            'orientedClients' => $member->clients()->orderByDesc('accumulated_pv')->orderBy('name')->get(),
            'membershipThreshold' => (float) $plan->decimal('membership_pv_threshold'),
            'recentPv' => $member->pvEntries()->latest('occurred_at')->limit(8)->get(),
            'recentCommissions' => $member->commissions()->with(['rewardTier', 'relatedMember'])->latest('occurred_at')->limit(8)->get(),
            'payoutAvailable' => $available,
            'pendingPayout' => $member->payoutRequests()->where('status', 'pending')->latest('id')->first(),
            'recentPayouts' => $member->payoutRequests()->latest('id')->limit(5)->get(),
        ]));
    }

    public function requestPayout(Request $request, PayoutRequestService $payouts): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $payouts->request($this->member(), (float) $data['amount'], $data['note'] ?? null);

        return redirect()
            ->route('member.dashboard')
            ->with('status', __('messages.payout_requested'));
    }

    public function profile(): View
    {
        $member = $this->member()->load(['sponsor', 'placementParent']);

        return view('member.profile', compact('member'));
    }

    public function updateProfile(
        UpdateMemberProfileRequest $request,
        AuditLogger $audit,
        SyncOutbox $outbox,
        PlanConfig $plan,
    ): RedirectResponse {
        $member = $this->member();
        $old = $member->makeHidden('password')->toArray();
        unset($old['password']);

        $data = $request->safe()->only(['full_name', 'phone', 'gender', 'birth_date', 'address']);
        $data['photo_path'] = $this->storePublicFile($request->file('photo'), 'members/photos', $member->photo_path);
        $data['id_document_path'] = $this->storePublicFile($request->file('id_document'), 'members/ids', $member->id_document_path);

        $member->update($data);
        $member->increment('version');
        $member->refresh();

        $new = $member->makeHidden('password')->toArray();
        unset($new['password']);
        $new['actor_member_id'] = $member->id;

        $outbox->enqueue('member', $member->uuid, 'update', $new, $plan->originDeviceId());
        $audit->record(null, 'updated', $member, $old, $new);

        return redirect()
            ->route('member.profile')
            ->with('status', __('messages.profile_updated'));
    }

    public function network(Request $request): View
    {
        $viewer = $this->member();
        $grouped = $this->portal->downlineGrouped($viewer);
        $subtreePv = $this->portal->subtreeTotals($viewer, $grouped);
        $allowedIds = collect([$viewer->id])->merge($grouped->flatten()->pluck('id'))->unique()->all();

        $rootId = $request->integer('root') ?: $viewer->id;
        abort_unless(in_array($rootId, $allowedIds, true), 403);
        $treeRoot = $rootId === $viewer->id
            ? $viewer
            : ($grouped->flatten()->firstWhere('id', $rootId) ?? Member::query()->findOrFail($rootId));

        $leftChild = $grouped->get($viewer->id, collect())->firstWhere('placement_side', 'left');
        $rightChild = $grouped->get($viewer->id, collect())->firstWhere('placement_side', 'right');

        $byId = collect([$viewer])->merge($grouped->flatten())->keyBy('id');
        $trail = [];
        $cursor = $treeRoot;
        $guard = 0;
        while ($cursor !== null && $guard < 64) {
            array_unshift($trail, $cursor);
            if ((int) $cursor->id === (int) $viewer->id) {
                break;
            }
            $cursor = $cursor->placement_parent_id ? $byId->get($cursor->placement_parent_id) : null;
            $guard++;
        }

        return view('member.network', [
            'member' => $viewer,
            'treeRoot' => $treeRoot,
            'grouped' => $grouped,
            'subtreePv' => $subtreePv,
            'leftLegPv' => $leftChild ? (float) ($subtreePv[$leftChild->id] ?? 0) : 0.0,
            'rightLegPv' => $rightChild ? (float) ($subtreePv[$rightChild->id] ?? 0) : 0.0,
            'downlineCount' => $grouped->flatten()->count(),
            'eqAmounts' => \App\Support\EquilibriumPath::amounts(),
            'trail' => $trail,
            'maxDepth' => 4,
            'drillBase' => route('member.network'),
        ]);
    }

    public function history(): View
    {
        $member = $this->member();

        return view('member.history', [
            'member' => $member,
            'pvEntries' => $member->pvEntries()
                ->latest('occurred_at')
                ->paginate(Listing::PER_PAGE, ['*'], 'pv_page'),
            'commissions' => $member->commissions()
                ->with(['relatedMember', 'rewardTier'])
                ->latest('occurred_at')
                ->paginate(Listing::PER_PAGE, ['*'], 'gain_page'),
        ]);
    }

    public function purchases(): View
    {
        $member = $this->member();

        return view('member.purchases', [
            'member' => $member,
            'sales' => $member->sales()->with('items.product')->latest('sold_at')->paginate(Listing::PER_PAGE),
        ]);
    }

    public function alerts(): View
    {
        $member = $this->member();
        session(['member_alerts_seen_at' => now()->toIso8601String()]);

        return view('member.alerts', [
            'member' => $member,
            'alerts' => $this->portal->alerts($member, 40),
        ]);
    }

    private function member(): Member
    {
        return auth('member')->user();
    }

    private function storePublicFile(?UploadedFile $file, string $directory, ?string $previous): ?string
    {
        if ($file === null) {
            return $previous;
        }

        $path = $file->store($directory, 'public');
        if (filled($previous) && $path !== $previous) {
            Storage::disk('public')->delete($previous);
        }

        return $path;
    }
}

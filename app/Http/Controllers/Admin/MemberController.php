<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMemberRequest;
use App\Http\Requests\UpdateStaffMemberRequest;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Member;
use App\Services\AuditLogger;
use App\Services\MemberRegistrar;
use App\Services\NetworkVolume;
use App\Services\RegistrationCodeService;
use App\Services\ReportBuilder;
use App\Services\SyncOutbox;
use App\Services\WhatsAppNotifier;
use App\Support\Listing;
use App\Support\PlanConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Member::class);

        $branchId = $request->user()?->scopedBranchId();

        $members = Member::query()
            ->with(['sponsor', 'placementParent', 'registrationBranch'])
            ->when($branchId, fn ($q) => $q->where('registration_branch_id', $branchId))
            ->when($request->string('q')->toString(), function ($query, string $q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('full_name', 'like', "%{$q}%")
                        ->orWhere('username', 'like', "%{$q}%")
                        ->orWhere('member_code', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%");
                });
            })
            ->latest()
            ->paginate(Listing::PER_PAGE)
            ->withQueryString();

        return view('admin.members.index', compact('members'));
    }

    public function create(Request $request, PlanConfig $plan): View
    {
        $this->authorize('create', Member::class);

        $threshold = (float) $plan->decimal('membership_pv_threshold');
        $branchId = $request->user()?->scopedBranchId();
        $availableCodes = $branchId
            ? app(RegistrationCodeService::class)->availableCount((int) $branchId)
            : null;

        return view('admin.members.create', [
            'branches' => Branch::query()->orderBy('name')->get(),
            'membershipAmount' => $plan->decimal('membership_amount_usd'),
            'membershipPv' => $plan->decimal('membership_pv_threshold'),
            'eligibleClients' => Client::query()
                ->with('referrer')
                ->eligibleForIndirect($threshold)
                ->orderBy('name')
                ->get(),
            'selectedType' => old('membership_type', $request->string('membership_type')->toString() ?: 'direct'),
            'selectedClientId' => old('source_client_id', $request->integer('source_client_id') ?: null),
            'availableRegistrationCodes' => $request->user()?->isAdmin() ? null : $availableCodes,
            'branchName' => $request->user()?->branch?->name,
        ]);
    }

    public function store(StoreMemberRequest $request, MemberRegistrar $registrar): RedirectResponse
    {
        $result = $registrar->register(
            $request->user(),
            $request->validated(),
            $request->file('photo'),
            $request->file('id_document'),
        );

        return redirect()
            ->route('admin.members.show', $result->member)
            ->with('status', __('messages.member_created'))
            ->with('generated_username', $result->member->username)
            ->with('generated_password', $result->plainPassword)
            ->with('prompt_placement', $result->member->isAwaitingPlacement());
    }

    public function show(Member $member): View
    {
        $this->authorize('view', $member);

        $member->load(['sponsor', 'placementParent', 'registrationBranch', 'placementChildren', 'clients', 'sourceClient.referrer']);

        return view('admin.members.show', [
            'member' => $member,
            'pvPending' => $member->pvEntries()->where('sync_status', 'pending')->sum('pv_amount'),
            'pvConfirmed' => $member->pvEntries()->where('sync_status', 'confirmed')->sum('pv_amount'),
            'commissions' => $member->commissions()->with(['rewardTier', 'relatedMember'])->latest('occurred_at')->paginate(Listing::PER_PAGE, ['*'], 'gain_page'),
            'pvEntries' => $member->pvEntries()->latest('occurred_at')->paginate(Listing::PER_PAGE, ['*'], 'pv_page'),
        ]);
    }

    public function edit(Member $member): View
    {
        $this->authorize('update', $member);

        return view('admin.members.edit', compact('member'));
    }

    public function update(
        UpdateStaffMemberRequest $request,
        Member $member,
        AuditLogger $audit,
        SyncOutbox $outbox,
        PlanConfig $plan,
    ): RedirectResponse {
        $old = $member->makeHidden('password')->toArray();
        unset($old['password']);

        $data = $request->safe()->only(['full_name', 'phone', 'gender', 'birth_date', 'address', 'status']);
        $data['photo_path'] = $this->storePublicFile($request->file('photo'), 'members/photos', $member->photo_path);
        $data['id_document_path'] = $this->storePublicFile($request->file('id_document'), 'members/ids', $member->id_document_path);

        $member->update($data);
        $member->increment('version');

        $new = $member->fresh()->makeHidden('password')->toArray();
        unset($new['password']);
        $outbox->enqueue('member', $member->uuid, 'update', $new, $plan->originDeviceId());
        $audit->record($request->user(), 'updated', $member, $old, $new);

        return redirect()->route('admin.members.show', $member)->with('status', __('messages.saved'));
    }

    public function destroy(Member $member, AuditLogger $audit, SyncOutbox $outbox, PlanConfig $plan): RedirectResponse
    {
        $this->authorize('delete', $member);

        if ($member->placementChildren()->exists() || $member->sales()->exists() || $member->sponsored()->exists()) {
            return back()->withErrors(['member' => __('messages.cannot_delete_linked')]);
        }

        $old = $member->makeHidden('password')->toArray();
        unset($old['password']);
        $member->delete();
        $outbox->enqueue('member', $member->uuid, 'delete', $old, $plan->originDeviceId());
        $audit->record(request()->user(), 'deleted', $member, $old, null);

        return redirect()->route('admin.members.index')->with('status', __('messages.record_deleted'));
    }

    public function tree(Request $request, NetworkVolume $volume): View
    {
        $this->authorize('viewAny', Member::class);

        $members = Member::query()
            ->orderBy('id')
            ->get([
                'id', 'uuid', 'full_name', 'member_code', 'username',
                'placement_parent_id', 'placement_side', 'sponsor_id',
            ]);
        $companyRoot = $members->first();
        $grouped = $members->groupBy('placement_parent_id');
        $byId = $members->keyBy('id');
        $eqAmounts = \App\Support\EquilibriumPath::amounts();

        $viewRootId = $request->integer('root') ?: ($companyRoot?->id);
        $viewRoot = $viewRootId ? $byId->get($viewRootId) : $companyRoot;
        if ($viewRoot === null) {
            $viewRoot = $companyRoot;
        }

        $focusId = $request->integer('member') ?: null;
        $focus = $focusId ? $byId->get($focusId) : null;
        $eqLevels = $focus
            ? \App\Support\EquilibriumPath::levelsAbove($focus, $byId)
            : [];

        $trail = [];
        $cursor = $viewRoot;
        $guard = 0;
        while ($cursor !== null && $guard < 64) {
            array_unshift($trail, $cursor);
            $cursor = $cursor->placement_parent_id ? $byId->get($cursor->placement_parent_id) : null;
            $guard++;
        }

        return view('admin.members.tree', [
            'roots' => $viewRoot ? collect([$viewRoot]) : collect(),
            'grouped' => $grouped,
            'subtreePv' => $volume->subtreeTotalsForMembers($members),
            'unplaced' => $members->filter(fn (Member $member) => $member->isAwaitingPlacement())->sortBy('full_name')->values(),
            'highlightId' => $viewRoot?->id,
            'eqLevels' => $eqLevels,
            'eqAmounts' => $eqAmounts,
            'focusMember' => $focus,
            'viewRoot' => $viewRoot,
            'companyRoot' => $companyRoot,
            'trail' => $trail,
            'maxDepth' => 4,
            'drillBase' => route('admin.members.tree'),
        ]);
    }

    public function printCard(Request $request, Member $member, ReportBuilder $reports): View
    {
        $this->authorize('view', $member);

        $payload = $reports->membershipSheet($member, 'staff');

        return view('print.report-a4', $payload + [
            'printedBy' => $request->user()->name,
            'printedAt' => now(),
        ]);
    }

    public function whatsapp(Member $member, WhatsAppNotifier $whatsapp): RedirectResponse
    {
        $this->authorize('view', $member);

        if (! filled($member->phone)) {
            return back()->withErrors(['phone' => __('messages.phone_missing')]);
        }

        $row = $whatsapp->enqueue($member->phone, 'welcome', $member->locale ?: 'fr', [
            'name' => $member->full_name,
            'username' => $member->username,
            'password' => '********',
        ]);

        return redirect()->away($whatsapp->waMeUrl($row));
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

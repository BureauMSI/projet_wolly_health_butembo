<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlaceMemberRequest;
use App\Models\Member;
use App\Support\Listing;
use App\Services\MemberPlacer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlacementController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Member::class);

        $branchId = $request->user()?->scopedBranchId();

        $unplacedQuery = Member::query()
            ->awaitingPlacement()
            ->when($branchId, fn ($q) => $q->where('registration_branch_id', $branchId))
            ->with(['sponsor', 'registrationBranch'])
            ->orderBy('full_name');

        $focusId = $request->integer('member') ?: null;
        if ($focusId) {
            $position = (clone $unplacedQuery)->pluck('id')->search($focusId);
            if ($position !== false && ! $request->has('page')) {
                $request->merge(['page' => (int) floor($position / Listing::PER_PAGE) + 1]);
            }
        }

        $unplaced = $unplacedQuery->paginate(Listing::PER_PAGE)->withQueryString();

        $treeMembers = Member::query()
            ->inBinaryTree()
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'username']);

        $sponsors = Member::query()
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'username']);

        return view('admin.placements.index', compact('unplaced', 'treeMembers', 'sponsors', 'focusId'));
    }

    public function store(PlaceMemberRequest $request, Member $member, MemberPlacer $placer): RedirectResponse
    {
        $this->authorize('update', $member);

        $placer->assign(
            $request->user(),
            $member,
            (int) $request->validated('placement_parent_id'),
            (string) $request->validated('placement_side'),
            (int) $request->validated('sponsor_id'),
        );

        return redirect()
            ->route('admin.placements.index')
            ->with('status', __('messages.placement_saved'));
    }
}

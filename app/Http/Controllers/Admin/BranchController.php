<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Institution;
use App\Models\RegistrationCode;
use App\Services\AuditLogger;
use App\Services\RegistrationCodeService;
use App\Services\SyncOutbox;
use App\Support\Listing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Branch::class);
        $branches = Branch::query()
            ->withCount([
                'registrationCodes as available_codes_count' => fn ($q) => $q->where('status', 'available'),
                'registrationCodes as used_codes_count' => fn ($q) => $q->where('status', 'used'),
            ])
            ->orderBy('name')
            ->paginate(Listing::PER_PAGE);

        return view('admin.branches.index', compact('branches'));
    }

    public function create(): View
    {
        $this->authorize('create', Branch::class);

        return view('admin.branches.create');
    }

    public function store(Request $request, AuditLogger $audit, SyncOutbox $outbox): RedirectResponse
    {
        $this->authorize('create', Branch::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:branches,code'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $institution = Institution::query()->firstOrFail();
        $branch = Branch::query()->create([
            ...$data,
            'institution_id' => $institution->id,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $outbox->enqueue('branch', $branch->uuid, 'create', $branch->toArray());
        $audit->record($request->user(), 'created', $branch, null, $branch->toArray());

        return redirect()->route('admin.branches.show', $branch)->with('status', __('messages.saved'));
    }

    public function show(Branch $branch): View
    {
        $this->authorize('view', $branch);

        $codes = RegistrationCode::query()
            ->where('branch_id', $branch->id)
            ->with(['creator:id,name', 'usedByMember:id,full_name,member_code'])
            ->latest('id')
            ->paginate(Listing::PER_PAGE);

        return view('admin.branches.show', [
            'branch' => $branch,
            'codes' => $codes,
            'availableCount' => $branch->availableRegistrationCodesCount(),
            'canGenerateCodes' => auth()->user()?->can('generateCodes', $branch) ?? false,
        ]);
    }

    public function generateCodes(
        Request $request,
        Branch $branch,
        RegistrationCodeService $codes,
    ): RedirectResponse {
        $this->authorize('generateCodes', $branch);

        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:200'],
        ]);

        $created = $codes->generate($branch, (int) $data['quantity'], $request->user());

        return redirect()
            ->route('admin.branches.show', $branch)
            ->with('status', __('messages.registration_codes_created', ['count' => $created->count()]))
            ->with('generated_codes', $created->pluck('code')->all());
    }
}

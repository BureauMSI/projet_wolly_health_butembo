<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Branch;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\SyncOutbox;
use App\Support\Listing;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', User::class);
        $users = User::query()->with('branch')->orderBy('name')->paginate(Listing::PER_PAGE);

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('admin.users.create', [
            'branches' => Branch::query()->where('is_active', true)->orderBy('name')->get(),
            'roles' => $this->roleOptions(),
        ]);
    }

    public function store(StoreUserRequest $request, AuditLogger $audit, SyncOutbox $outbox): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $user = User::query()->create($data);
        $payload = $user->makeHidden('password')->toArray();
        $outbox->enqueue('user', $user->uuid, 'create', $payload);
        $audit->record($request->user(), 'created', $user, null, $payload);

        return redirect()->route('admin.users.index')->with('status', __('messages.user_created'));
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('admin.users.edit', [
            'user' => $user,
            'branches' => Branch::query()->where('is_active', true)->orderBy('name')->get(),
            'roles' => $this->roleOptions(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user, AuditLogger $audit, SyncOutbox $outbox): RedirectResponse
    {
        $old = $user->makeHidden('password')->toArray();
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update($data);
        $user->increment('version');
        $fresh = $user->fresh()->makeHidden('password')->toArray();

        $outbox->enqueue('user', $user->uuid, 'update', $fresh);
        $audit->record($request->user(), 'updated', $user, $old, $fresh);

        return redirect()->route('admin.users.index')->with('status', __('messages.saved'));
    }

    /**
     * @return array<int, array{value: string, label: string, hint: string}>
     */
    private function roleOptions(): array
    {
        return [
            [
                'value' => User::ROLE_ADMIN,
                'label' => __('messages.admin_role'),
                'hint' => __('messages.role_hint_admin'),
            ],
            [
                'value' => User::ROLE_MANAGER,
                'label' => __('messages.manager_role'),
                'hint' => __('messages.role_hint_manager'),
            ],
            [
                'value' => User::ROLE_CASHIER,
                'label' => __('messages.cashier_role'),
                'hint' => __('messages.role_hint_cashier'),
            ],
            [
                'value' => User::ROLE_ACCOUNTANT,
                'label' => __('messages.accountant_role'),
                'hint' => __('messages.role_hint_accountant'),
            ],
        ];
    }
}

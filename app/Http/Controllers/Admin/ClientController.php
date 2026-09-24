<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use App\Models\Member;
use App\Services\AuditLogger;
use App\Services\SyncOutbox;
use App\Support\Listing;
use App\Support\PlanConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Client::class);

        $clients = Client::query()
            ->with('referrer')
            ->latest()
            ->paginate(Listing::PER_PAGE);

        return view('admin.clients.index', [
            'clients' => $clients,
            'threshold' => app(PlanConfig::class)->decimal('membership_pv_threshold'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Client::class);

        return view('admin.clients.create', [
            'members' => Member::query()->orderBy('full_name')->get(['id', 'full_name', 'username']),
        ]);
    }

    public function store(StoreClientRequest $request, AuditLogger $audit, SyncOutbox $outbox, PlanConfig $plan): RedirectResponse
    {
        $client = Client::query()->create([
            ...$request->validated(),
            'origin_device_id' => $plan->originDeviceId(),
            'accumulated_pv' => 0,
        ]);

        $outbox->enqueue('client', $client->uuid, 'create', $client->toArray(), $plan->originDeviceId());
        $audit->record($request->user(), 'created', $client, null, $client->toArray());

        return redirect()->route('admin.clients.index')->with('status', __('messages.client_created'));
    }

    public function edit(Client $client): View
    {
        $this->authorize('update', $client);

        return view('admin.clients.edit', [
            'client' => $client,
            'members' => Member::query()->orderBy('full_name')->get(['id', 'full_name', 'username']),
        ]);
    }

    public function update(UpdateClientRequest $request, Client $client, AuditLogger $audit, SyncOutbox $outbox, PlanConfig $plan): RedirectResponse
    {
        $old = $client->toArray();
        $client->update($request->validated());
        $client->increment('version');
        $outbox->enqueue('client', $client->uuid, 'update', $client->fresh()->toArray(), $plan->originDeviceId());
        $audit->record($request->user(), 'updated', $client, $old, $client->fresh()->toArray());

        return redirect()->route('admin.clients.index')->with('status', __('messages.saved'));
    }

    public function destroy(Client $client, AuditLogger $audit, SyncOutbox $outbox, PlanConfig $plan): RedirectResponse
    {
        $this->authorize('delete', $client);

        if ($client->sales()->exists() || $client->converted_member_id !== null) {
            return back()->withErrors(['client' => __('messages.cannot_delete_linked')]);
        }

        $old = $client->toArray();
        $client->delete();
        $outbox->enqueue('client', $client->uuid, 'delete', $old, $plan->originDeviceId());
        $audit->record(request()->user(), 'deleted', $client, $old, null);

        return redirect()->route('admin.clients.index')->with('status', __('messages.record_deleted'));
    }
}

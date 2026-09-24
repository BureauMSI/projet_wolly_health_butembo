<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\SyncOutboxEntry;
use App\Services\AuditLogger;
use App\Services\SyncEngine;
use App\Support\PlanConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SyncController extends Controller
{
    public function index(SyncEngine $engine, PlanConfig $plan): View
    {
        $this->authorizeAdmin();

        $pending = SyncOutboxEntry::query()
            ->whereNull('synced_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $failed = SyncOutboxEntry::query()
            ->whereNull('synced_at')
            ->whereNotNull('last_error')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        return view('admin.sync.index', [
            'mode' => $plan->isRemoteSyncEnabled() ? 'remote' : 'local',
            'remoteUrl' => config('sync.remote_url'),
            'originDeviceId' => $plan->originDeviceId(),
            'pendingCount' => $engine->pendingCount(),
            'pending' => $pending,
            'failed' => $failed,
        ]);
    }

    public function run(Request $request, SyncEngine $engine, AuditLogger $audit): RedirectResponse
    {
        $this->authorizeAdmin();

        $result = $engine->run();
        $institution = Institution::query()->first();
        if ($institution) {
            $audit->record($request->user(), 'sync_run', $institution, null, $result);
        }

        if ($result['mode'] === 'local') {
            return redirect()
                ->route('admin.sync.index')
                ->with('status', __('messages.sync_local_mode'));
        }

        if ($result['errors'] !== []) {
            return redirect()
                ->route('admin.sync.index')
                ->with('status', __('messages.sync_done_with_errors', [
                    'pushed' => $result['pushed'],
                    'pulled' => $result['pulled'],
                    'confirmed' => $result['confirmed'],
                ]));
        }

        return redirect()
            ->route('admin.sync.index')
            ->with('status', __('messages.sync_done', [
                'pushed' => $result['pushed'],
                'pulled' => $result['pulled'],
                'confirmed' => $result['confirmed'],
            ]));
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }
}

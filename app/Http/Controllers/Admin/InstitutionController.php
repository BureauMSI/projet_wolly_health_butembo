<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Services\AuditLogger;
use App\Services\SyncOutbox;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstitutionController extends Controller
{
    public function edit(): View
    {
        $institution = Institution::query()->firstOrFail();
        $this->authorize('update', $institution);

        return view('admin.institution.edit', compact('institution'));
    }

    public function update(Request $request, AuditLogger $audit, SyncOutbox $outbox): RedirectResponse
    {
        $institution = Institution::query()->firstOrFail();
        $this->authorize('update', $institution);

        $old = $institution->toArray();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'acronym' => ['nullable', 'string', 'max:50'],
            'slogan' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'rccm' => ['nullable', 'string', 'max:100'],
            'tax_id' => ['nullable', 'string', 'max:100'],
            'id_nat' => ['nullable', 'string', 'max:100'],
            'invoice_footer' => ['nullable', 'string'],
            'default_locale' => ['required', 'in:fr,sw'],
            'default_currency_code' => ['required', 'string', 'size:3'],
        ]);

        $institution->update($data);
        $institution->increment('version');
        cache()->forget(\App\Providers\AppServiceProvider::INSTITUTION_CACHE_KEY);
        cache()->forget('holy_health.institution');
        $outbox->enqueue('institution', $institution->uuid, 'update', $institution->fresh()->toArray());
        $audit->record($request->user(), 'updated', $institution, $old, $institution->fresh()->toArray());

        return redirect()->route('admin.institution.edit')->with('status', __('messages.saved'));
    }
}

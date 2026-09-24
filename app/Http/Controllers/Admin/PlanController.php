<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EquilibriumRule;
use App\Models\PlanSetting;
use App\Models\RewardTier;
use App\Services\AuditLogger;
use App\Services\SyncOutbox;
use App\Support\PlanConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function edit(): View
    {
        $this->authorize('viewAny', PlanSetting::class);

        return view('admin.plan.edit', [
            'settings' => PlanSetting::query()->orderBy('key')->get()->keyBy('key'),
            'tiers' => RewardTier::query()->orderBy('sort_order')->orderBy('min_pv')->get(),
            'rules' => EquilibriumRule::query()->orderBy('scope')->orderBy('generation')->get(),
        ]);
    }

    public function updateSettings(Request $request, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('update', PlanSetting::class);

        $data = $request->validate([
            'sponsorship_amount_usd' => ['required', 'numeric', 'min:0'],
            'membership_amount_usd' => ['required', 'numeric', 'min:0'],
            'membership_pv_threshold' => ['required', 'numeric', 'min:0'],
            'sponsorship_pv' => ['required', 'numeric', 'min:0'],
            'username_suffix_length' => ['required', 'integer', 'min:1', 'max:8'],
            'default_member_password' => ['required', 'string', 'min:8'],
            'member_discount_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        foreach ($data as $key => $value) {
            $row = PlanSetting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
            $old = ['value' => $row->value];
            $row->update([
                'value' => (string) $value,
                'updated_by' => $request->user()->id,
            ]);
            $audit->record($request->user(), 'updated', $row, $old, ['value' => $row->value]);
        }

        app(PlanConfig::class)->flush();

        return back()->with('status', __('messages.saved'));
    }

    public function storeTier(Request $request, AuditLogger $audit, SyncOutbox $outbox, PlanConfig $plan): RedirectResponse
    {
        $this->authorize('update', PlanSetting::class);

        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'min_pv' => ['required', 'numeric', 'min:0'],
            'max_pv' => ['nullable', 'numeric', 'min:0'],
            'amount_usd' => ['nullable', 'numeric', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);

        $imagePath = null;
        if ($request->file('image')) {
            $imagePath = $request->file('image')->store('rewards', 'public');
        }

        $tier = RewardTier::query()->create([
            'label' => $data['label'],
            'min_pv' => $data['min_pv'],
            'max_pv' => $data['max_pv'] ?? null,
            'amount_usd' => $data['amount_usd'] ?? 0,
            'image_path' => $imagePath,
            'origin_device_id' => $plan->originDeviceId(),
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => true,
        ]);

        $outbox->enqueue('reward_tier', $tier->uuid, 'create', $tier->toArray(), $plan->originDeviceId());
        $audit->record($request->user(), 'created', $tier, null, $tier->toArray());

        return back()->with('status', __('messages.saved'));
    }

    public function destroyTier(RewardTier $tier, AuditLogger $audit, SyncOutbox $outbox, PlanConfig $plan): RedirectResponse
    {
        $this->authorize('update', PlanSetting::class);
        $old = $tier->toArray();
        $tier->delete();
        $outbox->enqueue('reward_tier', $tier->uuid, 'delete', $old, $plan->originDeviceId());
        $audit->record(request()->user(), 'deleted', $tier, $old, null);

        return back()->with('status', __('messages.saved'));
    }

    public function storeRule(Request $request, AuditLogger $audit, SyncOutbox $outbox, PlanConfig $plan): RedirectResponse
    {
        $this->authorize('update', PlanSetting::class);

        $data = $request->validate([
            'scope' => ['required', 'in:generations_1_4,after_generation_4'],
            'generation' => ['nullable', 'integer', 'min:1', 'max:32'],
            'amount_usd' => ['nullable', 'numeric', 'min:0'],
            'percent' => ['nullable', 'numeric', 'min:0'],
            'min_leg_pv' => ['nullable', 'numeric', 'min:0'],
        ]);

        // After the 4th equilibrium: one flat rule (generation must stay null).
        // Equilibria 1–4: generation must be 1–4 when provided.
        if ($data['scope'] === 'after_generation_4') {
            $data['generation'] = null;
        } elseif (isset($data['generation']) && ((int) $data['generation'] < 1 || (int) $data['generation'] > 4)) {
            return back()->withErrors([
                'generation' => __('messages.equilibrium_generation_1_4_only'),
            ])->withInput();
        }

        $rule = EquilibriumRule::query()->create([
            ...$data,
            'origin_device_id' => $plan->originDeviceId(),
            'is_active' => true,
        ]);

        $outbox->enqueue('equilibrium_rule', $rule->uuid, 'create', $rule->toArray(), $plan->originDeviceId());
        $audit->record($request->user(), 'created', $rule, null, $rule->toArray());

        return back()->with('status', __('messages.saved'));
    }

    public function destroyRule(EquilibriumRule $rule, AuditLogger $audit, SyncOutbox $outbox, PlanConfig $plan): RedirectResponse
    {
        $this->authorize('update', PlanSetting::class);
        $old = $rule->toArray();
        $rule->delete();
        $outbox->enqueue('equilibrium_rule', $rule->uuid, 'delete', $old, $plan->originDeviceId());
        $audit->record(request()->user(), 'deleted', $rule, $old, null);

        return back()->with('status', __('messages.saved'));
    }
}

<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CommissionLedger;
use App\Models\Institution;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlacementEquilibriumTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_placing_a_member_credits_four_usd_up_the_placement_line(): void
    {
        $branch = Branch::query()->where('code', 'BTB')->first()
            ?? Branch::query()->create([
                'institution_id' => Institution::query()->firstOrFail()->id,
                'name' => 'Test',
                'code' => 'TST',
                'is_active' => true,
            ]);

        $cashier = User::factory()->create([
            'role' => 'cashier',
            'branch_id' => $branch->id,
        ]);

        $root = Member::factory()->create(['full_name' => 'Racine']);
        $parent = Member::factory()->create([
            'full_name' => 'Parent Eq1',
            'placement_parent_id' => $root->id,
            'placement_side' => 'left',
            'sponsor_id' => $root->id,
        ]);
        // Placement of $parent already happened via factory — backfill-style call is not automatic on factory.
        // Credit equilibria for parent placement first so the tree is consistent, then place the new child via UI.
        app(\App\Services\CompensationEngine::class)->onPlacement($parent);

        $newbie = Member::factory()->create([
            'full_name' => 'Nouveau Place',
            'placement_parent_id' => null,
            'placement_side' => null,
            'sponsor_id' => null,
            'registration_branch_id' => $branch->id,
        ]);

        $this->actingAs($cashier)->post(route('admin.placements.store', $newbie), [
            'placement_parent_id' => $parent->id,
            'placement_side' => 'left',
            'sponsor_id' => $parent->id,
        ])->assertRedirect();

        $eq1 = CommissionLedger::query()
            ->where('member_id', $parent->id)
            ->where('type', 'equilibrium')
            ->where('related_member_id', $newbie->id)
            ->where('generation', 1)
            ->whereNull('sale_id')
            ->first();

        $this->assertNotNull($eq1);
        $this->assertSame(4.0, (float) $eq1->amount_usd);

        $eq2 = CommissionLedger::query()
            ->where('member_id', $root->id)
            ->where('type', 'equilibrium')
            ->where('related_member_id', $newbie->id)
            ->where('generation', 2)
            ->whereNull('sale_id')
            ->first();

        $this->assertNotNull($eq2);
        $this->assertSame(4.0, (float) $eq2->amount_usd);
    }

    public function test_backfill_credits_missing_placement_equilibria(): void
    {
        $root = Member::factory()->create();
        $child = Member::factory()->create([
            'placement_parent_id' => $root->id,
            'placement_side' => 'left',
            'sponsor_id' => $root->id,
        ]);

        $this->assertSame(0, CommissionLedger::query()->where('type', 'equilibrium')->count());

        $this->artisan('equilibrium:backfill-placements')
            ->assertSuccessful();

        $this->assertDatabaseHas('commission_ledger', [
            'member_id' => $root->id,
            'type' => 'equilibrium',
            'related_member_id' => $child->id,
            'generation' => 1,
            'amount_usd' => 4,
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\PvLedger;
use App\Models\User;
use App\Services\NetworkVolume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PerformanceHotPathTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_subtree_totals_uses_few_queries_for_a_chain(): void
    {
        $root = Member::factory()->create(['full_name' => 'Perf Root']);
        $prev = $root;
        $chain = collect([$root]);
        for ($i = 1; $i <= 6; $i++) {
            $prev = Member::factory()->create([
                'full_name' => 'Perf '.$i,
                'placement_parent_id' => $prev->id,
                'placement_side' => 'left',
                'sponsor_id' => $root->id,
            ]);
            $chain->push($prev);
            PvLedger::query()->create([
                'member_id' => $prev->id,
                'source_type' => 'own_purchase',
                'pv_amount' => 10,
                'occurred_at' => now(),
                'sync_status' => 'confirmed',
            ]);
        }

        $volume = app(NetworkVolume::class);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $totals = $volume->subtreeTotalsForMembers(
            $chain->map(fn (Member $m) => (object) [
                'id' => $m->id,
                'placement_parent_id' => $m->placement_parent_id,
                'placement_side' => $m->placement_side,
            ])
        );
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(60.0, $totals[$root->id]);
        $this->assertLessThanOrEqual(2, $queryCount);
    }

    public function test_dashboard_loads_after_trend_aggregation_change(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(__('messages.sales_trend'), false)
            ->assertSee(__('messages.cash_balance'), false);
    }
}

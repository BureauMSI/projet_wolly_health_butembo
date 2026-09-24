<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TreeEquilibriumPathTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_tree_page_shows_equilibrium_legend_and_path_for_focus_member(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);
        $root = Member::factory()->create(['full_name' => 'Racine Eq']);
        $child = Member::factory()->create([
            'full_name' => 'Enfant Eq',
            'placement_parent_id' => $root->id,
            'placement_side' => 'left',
            'sponsor_id' => $root->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.members.tree', ['root' => $root->id, 'member' => $child->id]))
            ->assertOk()
            ->assertSee(__('messages.tree_depth_hint'), false)
            ->assertSee(__('messages.equilibrium_short', ['n' => 1]), false)
            ->assertSee('mlm-eq-link-near', false)
            ->assertSee('tree-phone', false)
            ->assertSee('data-member-id="'.$child->id.'"', false)
            ->assertSee('data-parent-id="'.$root->id.'"', false)
            ->assertSee('data-drill-url', false);
    }

    public function test_tree_stops_at_fourth_generation_and_allows_drill_root(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);
        $g0 = Member::factory()->create(['full_name' => 'Gen Zero']);
        $prev = $g0;
        $names = [];
        for ($i = 1; $i <= 5; $i++) {
            $names[$i] = 'Gen '.$i;
            $prev = Member::factory()->create([
                'full_name' => $names[$i],
                'placement_parent_id' => $prev->id,
                'placement_side' => 'left',
                'sponsor_id' => $g0->id,
            ]);
        }

        $this->actingAs($admin)
            ->get(route('admin.members.tree', ['root' => $g0->id]))
            ->assertOk()
            ->assertSee('Gen 4', false)
            ->assertDontSee('Gen 5', false)
            ->assertSee(__('messages.tree_more_short'), false);

        $g4 = Member::query()->where('full_name', 'Gen 4')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.members.tree', ['root' => $g4->id]))
            ->assertOk()
            ->assertSee('Gen 4', false)
            ->assertSee('Gen 5', false);
    }
}

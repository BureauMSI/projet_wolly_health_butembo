<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Member;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchRolesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_central_admin_sees_all_members_and_org_menu(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);
        $branchA = Branch::query()->firstOrFail();
        $branchB = Branch::query()->create([
            'institution_id' => $branchA->institution_id,
            'name' => 'Goma',
            'code' => 'GOM',
            'is_active' => true,
        ]);
        Member::factory()->create(['full_name' => 'De Butembo', 'registration_branch_id' => $branchA->id]);
        Member::factory()->create(['full_name' => 'De Goma', 'registration_branch_id' => $branchB->id]);

        $this->actingAs($admin, 'web')
            ->get(route('admin.members.index'))
            ->assertOk()
            ->assertSee('De Butembo', false)
            ->assertSee('De Goma', false);

        $this->actingAs($admin, 'web')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(__('messages.institution'), false)
            ->assertSee(__('messages.plan_settings'), false);
    }

    public function test_manager_is_scoped_to_own_branch_and_cannot_open_central_settings(): void
    {
        $branchA = Branch::query()->firstOrFail();
        $branchB = Branch::query()->create([
            'institution_id' => $branchA->institution_id,
            'name' => 'Goma',
            'code' => 'GOM',
            'is_active' => true,
        ]);
        Member::factory()->create(['full_name' => 'Local Manager Member', 'registration_branch_id' => $branchA->id]);
        Member::factory()->create(['full_name' => 'Other Branch Member', 'registration_branch_id' => $branchB->id]);

        $manager = User::factory()->create([
            'role' => 'manager',
            'branch_id' => $branchA->id,
        ]);

        $this->actingAs($manager, 'web')
            ->get(route('admin.members.index'))
            ->assertOk()
            ->assertSee('Local Manager Member', false)
            ->assertDontSee('Other Branch Member', false);

        $this->actingAs($manager, 'web')
            ->get(route('admin.plan.edit'))
            ->assertForbidden();

        $this->actingAs($manager, 'web')
            ->get(route('admin.branches.index'))
            ->assertForbidden();

        $this->actingAs($manager, 'web')
            ->get(route('admin.branches.show', $branchA))
            ->assertOk()
            ->assertSee(__('messages.registration_codes_manager_hint'), false);

        $this->actingAs($manager, 'web')
            ->get(route('admin.branches.show', $branchB))
            ->assertForbidden();
    }

    public function test_cashier_and_accountant_require_branch_and_stay_scoped(): void
    {
        $branch = Branch::query()->firstOrFail();
        $other = Branch::query()->create([
            'institution_id' => $branch->institution_id,
            'name' => 'Autre',
            'code' => 'AUT',
            'is_active' => true,
        ]);

        $saleMine = Sale::query()->create([
            'number' => 'S-MINE',
            'branch_id' => $branch->id,
            'user_id' => User::factory()->create(['role' => 'cashier', 'branch_id' => $branch->id])->id,
            'buyer_type' => 'client',
            'currency_code' => 'USD',
            'rate_to_usd' => 1,
            'subtotal_usd' => 10,
            'discount_usd' => 0,
            'promo_usd' => 0,
            'total_usd' => 10,
            'sold_at' => now(),
            'benefit_mode' => 'pv',
        ]);
        $saleOther = Sale::query()->create([
            'number' => 'S-OTHER',
            'branch_id' => $other->id,
            'user_id' => User::factory()->create(['role' => 'cashier', 'branch_id' => $other->id])->id,
            'buyer_type' => 'client',
            'currency_code' => 'USD',
            'rate_to_usd' => 1,
            'subtotal_usd' => 10,
            'discount_usd' => 0,
            'promo_usd' => 0,
            'total_usd' => 10,
            'sold_at' => now(),
            'benefit_mode' => 'pv',
        ]);

        $accountant = User::factory()->create([
            'role' => 'accountant',
            'branch_id' => $branch->id,
        ]);

        $this->actingAs($accountant, 'web')
            ->get(route('admin.sales.index'))
            ->assertOk()
            ->assertSee('S-MINE', false)
            ->assertDontSee('S-OTHER', false);

        $this->actingAs($accountant, 'web')
            ->get(route('admin.sales.show', $saleOther))
            ->assertForbidden();

        $this->actingAs($accountant, 'web')
            ->get(route('admin.sales.show', $saleMine))
            ->assertOk();
    }

    public function test_admin_can_create_manager_for_a_branch(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);
        $branch = Branch::query()->firstOrFail();

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Resp Goma',
            'username' => 'respgoma',
            'password' => 'Secret1234',
            'role' => 'manager',
            'branch_id' => $branch->id,
            'locale' => 'fr',
            'is_active' => 1,
        ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'username' => 'respgoma',
            'role' => 'manager',
            'branch_id' => $branch->id,
        ]);
    }
}

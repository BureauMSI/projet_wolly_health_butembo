<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CommissionLedger;
use App\Models\EquilibriumRule;
use App\Models\Institution;
use App\Models\Member;
use App\Models\PlanSetting;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquilibriumCommissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_plan_screen_labels_equilibria_not_generations(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);

        $this->actingAs($admin)
            ->get(route('admin.plan.edit'))
            ->assertOk()
            ->assertSee(__('messages.generations_1_4', ['n' => 4]), false)
            ->assertSee(__('messages.after_generation_4', ['n' => 4]), false)
            ->assertSee(__('messages.equilibrium_rules_hint', ['n' => 4, 'next' => 5]), false)
            ->assertSee(__('messages.equilibrium_near_count'), false)
            ->assertSee(__('messages.edit'), false)
            ->assertSee(route('admin.plan.rules.update', EquilibriumRule::query()->first()), false)
            ->assertDontSee('4e génération', false)
            ->assertDontSee('Générations 1 à 4', false);
    }

    public function test_after_fourth_equilibrium_uses_parametrized_flat_amount(): void
    {
        EquilibriumRule::query()
            ->where('scope', 'generations_1_4')
            ->whereIn('generation', [1, 2, 3, 4])
            ->update(['amount_usd' => 4]);

        EquilibriumRule::query()
            ->where('scope', 'after_generation_4')
            ->whereNull('generation')
            ->update(['amount_usd' => 1]);

        [$cashier, $buyer, $product, $upline] = $this->chainOfAncestors(5);

        $this->actingAs($cashier)->post(route('admin.sales.store'), [
            'buyer_type' => 'member',
            'benefit_mode' => 'pv',
            'member_id' => $buyer->id,
            'currency_code' => 'USD',
            'items' => [
                ['product_id' => $product->id, 'packing' => 'tablet', 'quantity' => 1],
            ],
        ])->assertRedirect();

        foreach ([1, 2, 3, 4] as $level) {
            $this->assertDatabaseHas('commission_ledger', [
                'member_id' => $upline[$level - 1]->id,
                'type' => 'equilibrium',
                'generation' => $level,
                'amount_usd' => 4,
            ]);
        }

        $fifth = CommissionLedger::query()
            ->where('member_id', $upline[4]->id)
            ->where('type', 'equilibrium')
            ->where('generation', 5)
            ->first();

        $this->assertNotNull($fifth);
        $this->assertSame(1.0, (float) $fifth->amount_usd);

        EquilibriumRule::query()
            ->where('scope', 'after_generation_4')
            ->whereNull('generation')
            ->update(['amount_usd' => 3.5]);

        $deeperBuyer = Member::factory()->create([
            'placement_parent_id' => $buyer->id,
            'placement_side' => 'left',
        ]);

        $this->actingAs($cashier)->post(route('admin.sales.store'), [
            'buyer_type' => 'member',
            'benefit_mode' => 'pv',
            'member_id' => $deeperBuyer->id,
            'currency_code' => 'USD',
            'items' => [
                ['product_id' => $product->id, 'packing' => 'tablet', 'quantity' => 1],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('commission_ledger', [
            'member_id' => $upline[4]->id,
            'type' => 'equilibrium',
            'generation' => 6,
            'amount_usd' => 3.5,
        ]);
    }

    public function test_seeded_equilibrium_defaults_are_four_then_one_usd(): void
    {
        foreach ([1, 2, 3, 4] as $generation) {
            $this->assertDatabaseHas('equilibrium_rules', [
                'scope' => 'generations_1_4',
                'generation' => $generation,
                'amount_usd' => 4,
            ]);
        }

        $this->assertDatabaseHas('equilibrium_rules', [
            'scope' => 'after_generation_4',
            'generation' => null,
            'amount_usd' => 1,
        ]);

        $this->assertDatabaseHas('plan_settings', [
            'key' => 'equilibrium_near_count',
            'value' => '4',
        ]);
    }

    public function test_near_count_from_plan_settings_credits_beyond_four_at_near_amount(): void
    {
        PlanSetting::query()->where('key', 'equilibrium_near_count')->update(['value' => '6']);
        app(\App\Support\PlanConfig::class)->flush();

        EquilibriumRule::query()
            ->where('scope', 'generations_1_4')
            ->whereIn('generation', [1, 2, 3, 4])
            ->update(['amount_usd' => 5]);

        EquilibriumRule::query()->create([
            'scope' => 'generations_1_4',
            'generation' => 5,
            'amount_usd' => 5,
            'is_active' => true,
        ]);
        EquilibriumRule::query()->create([
            'scope' => 'generations_1_4',
            'generation' => 6,
            'amount_usd' => 5,
            'is_active' => true,
        ]);

        EquilibriumRule::query()
            ->where('scope', 'after_generation_4')
            ->whereNull('generation')
            ->update(['amount_usd' => 1]);

        [$cashier, $buyer, $product, $upline] = $this->chainOfAncestors(7);

        $this->actingAs($cashier)->post(route('admin.sales.store'), [
            'buyer_type' => 'member',
            'benefit_mode' => 'pv',
            'member_id' => $buyer->id,
            'currency_code' => 'USD',
            'items' => [
                ['product_id' => $product->id, 'packing' => 'tablet', 'quantity' => 1],
            ],
        ])->assertRedirect();

        foreach ([1, 2, 3, 4, 5, 6] as $level) {
            $this->assertDatabaseHas('commission_ledger', [
                'member_id' => $upline[$level - 1]->id,
                'type' => 'equilibrium',
                'generation' => $level,
                'amount_usd' => 5,
            ]);
        }

        $this->assertDatabaseHas('commission_ledger', [
            'member_id' => $upline[6]->id,
            'type' => 'equilibrium',
            'generation' => 7,
            'amount_usd' => 1,
        ]);
    }

    public function test_saving_near_count_creates_missing_generation_rules(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);

        $this->actingAs($admin)->put(route('admin.plan.settings'), $this->planSettingsPayload([
            'equilibrium_near_count' => 6,
        ]))->assertRedirect();

        $this->assertDatabaseHas('plan_settings', [
            'key' => 'equilibrium_near_count',
            'value' => '6',
        ]);

        foreach ([5, 6] as $generation) {
            $this->assertDatabaseHas('equilibrium_rules', [
                'scope' => 'generations_1_4',
                'generation' => $generation,
                'deleted_at' => null,
            ]);
        }
    }

    public function test_admin_can_update_equilibrium_rule_amount(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);
        $rule = EquilibriumRule::query()
            ->where('scope', 'generations_1_4')
            ->where('generation', 1)
            ->firstOrFail();

        $this->actingAs($admin)->put(route('admin.plan.rules.update', $rule), [
            'scope' => 'generations_1_4',
            'generation' => 1,
            'amount_usd' => 5,
        ])->assertRedirect();

        $this->assertDatabaseHas('equilibrium_rules', [
            'id' => $rule->id,
            'scope' => 'generations_1_4',
            'generation' => 1,
            'amount_usd' => 5,
            'deleted_at' => null,
        ]);
    }

    public function test_update_rejects_generation_above_near_count(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);
        $rule = EquilibriumRule::query()
            ->where('scope', 'generations_1_4')
            ->where('generation', 1)
            ->firstOrFail();

        $this->actingAs($admin)->put(route('admin.plan.rules.update', $rule), [
            'scope' => 'generations_1_4',
            'generation' => 5,
            'amount_usd' => 5,
        ])->assertSessionHasErrors('generation');

        $this->assertDatabaseHas('equilibrium_rules', [
            'id' => $rule->id,
            'generation' => 1,
        ]);
    }

    public function test_store_rejects_generation_above_near_count(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);

        $this->actingAs($admin)->post(route('admin.plan.rules.store'), [
            'scope' => 'generations_1_4',
            'generation' => 5,
            'amount_usd' => 5,
        ])->assertSessionHasErrors('generation');
    }

    public function test_after_fourth_rule_forces_null_generation_on_store(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);

        $this->actingAs($admin)->post(route('admin.plan.rules.store'), [
            'scope' => 'after_generation_4',
            'generation' => 2,
            'amount_usd' => 1.25,
        ])->assertRedirect();

        $this->assertDatabaseHas('equilibrium_rules', [
            'scope' => 'after_generation_4',
            'generation' => null,
            'amount_usd' => 1.25,
            'deleted_at' => null,
        ]);

        $this->assertDatabaseMissing('equilibrium_rules', [
            'scope' => 'after_generation_4',
            'generation' => 2,
            'deleted_at' => null,
        ]);
    }

    /**
     * Build buyer under $count placement ancestors: upline[0]=eq1 … upline[n-1]=eq n.
     *
     * @return array{0: User, 1: Member, 2: Product, 3: list<Member>}
     */
    private function chainOfAncestors(int $count): array
    {
        $institution = Institution::query()->firstOrFail();
        $branch = Branch::query()->where('code', 'BTB')->first() ?? Branch::query()->create([
            'institution_id' => $institution->id,
            'name' => 'Test',
            'code' => 'TST',
            'is_active' => true,
        ]);
        $cashier = User::factory()->create([
            'role' => 'cashier',
            'branch_id' => $branch->id,
        ]);
        $product = Product::factory()->create([
            'benefit_type' => 'pv',
            'unit_price_usd' => 5,
            'member_unit_price_usd' => 5,
            'pv_per_tablet' => 2,
            'is_active' => true,
        ]);

        $top = Member::factory()->create();
        $upline = [$top];
        $parent = $top;
        for ($i = 0; $i < $count - 1; $i++) {
            $child = Member::factory()->create([
                'placement_parent_id' => $parent->id,
                'placement_side' => 'left',
            ]);
            array_unshift($upline, $child);
            $parent = $child;
        }

        $buyer = Member::factory()->create([
            'placement_parent_id' => $upline[0]->id,
            'placement_side' => 'left',
        ]);

        return [$cashier, $buyer, $product, $upline];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function planSettingsPayload(array $overrides = []): array
    {
        $keys = [
            'sponsorship_amount_usd',
            'membership_amount_usd',
            'membership_pv_threshold',
            'sponsorship_pv',
            'username_suffix_length',
            'default_member_password',
            'member_discount_percent',
            'equilibrium_near_count',
        ];
        $payload = [];
        foreach ($keys as $key) {
            $payload[$key] = PlanSetting::query()->where('key', $key)->value('value');
        }

        return array_merge($payload, $overrides);
    }
}

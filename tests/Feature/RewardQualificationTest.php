<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CommissionLedger;
use App\Models\Institution;
use App\Models\Member;
use App\Models\Product;
use App\Models\RewardTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RewardQualificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_own_pv_does_not_qualify_for_weak_leg_reward(): void
    {
        [$cashier, $root, $product, $tier] = $this->setupRewardCase();

        $this->actingAs($cashier)->post(route('admin.sales.store'), [
            'buyer_type' => 'member',
            'benefit_mode' => 'pv',
            'member_id' => $root->id,
            'currency_code' => 'USD',
            'items' => [
                ['product_id' => $product->id, 'packing' => 'tablet', 'quantity' => 8],
            ],
        ])->assertRedirect();

        $this->assertDatabaseMissing('commission_ledger', [
            'member_id' => $root->id,
            'type' => 'reward',
            'reward_tier_id' => $tier->id,
        ]);
    }

    public function test_one_strong_leg_does_not_qualify_for_reward(): void
    {
        [$cashier, $root, $product, $tier] = $this->setupRewardCase();
        $left = Member::factory()->create([
            'placement_parent_id' => $root->id,
            'placement_side' => 'left',
        ]);
        Member::factory()->create([
            'placement_parent_id' => $root->id,
            'placement_side' => 'right',
        ]);

        $this->actingAs($cashier)->post(route('admin.sales.store'), [
            'buyer_type' => 'member',
            'benefit_mode' => 'pv',
            'member_id' => $left->id,
            'currency_code' => 'USD',
            'items' => [
                ['product_id' => $product->id, 'packing' => 'tablet', 'quantity' => 8],
            ],
        ])->assertRedirect();

        $this->assertDatabaseMissing('commission_ledger', [
            'member_id' => $root->id,
            'type' => 'reward',
            'reward_tier_id' => $tier->id,
        ]);
    }

    public function test_weak_leg_volume_credits_reward_to_upline(): void
    {
        [$cashier, $root, $product, $tier] = $this->setupRewardCase();
        $left = Member::factory()->create([
            'placement_parent_id' => $root->id,
            'placement_side' => 'left',
        ]);
        $right = Member::factory()->create([
            'placement_parent_id' => $root->id,
            'placement_side' => 'right',
        ]);

        $this->actingAs($cashier)->post(route('admin.sales.store'), [
            'buyer_type' => 'member',
            'benefit_mode' => 'pv',
            'member_id' => $left->id,
            'currency_code' => 'USD',
            'items' => [
                ['product_id' => $product->id, 'packing' => 'tablet', 'quantity' => 5],
            ],
        ])->assertRedirect();

        $this->assertDatabaseMissing('commission_ledger', [
            'member_id' => $root->id,
            'type' => 'reward',
            'reward_tier_id' => $tier->id,
        ]);

        $this->actingAs($cashier)->post(route('admin.sales.store'), [
            'buyer_type' => 'member',
            'benefit_mode' => 'pv',
            'member_id' => $right->id,
            'currency_code' => 'USD',
            'items' => [
                ['product_id' => $product->id, 'packing' => 'tablet', 'quantity' => 5],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('commission_ledger', [
            'member_id' => $root->id,
            'type' => 'reward',
            'reward_tier_id' => $tier->id,
        ]);
        $this->assertTrue(
            in_array(CommissionLedger::query()->where('reward_tier_id', $tier->id)->value('status'), ['pending', 'confirmed'], true)
        );
    }

    public function test_member_dashboard_shows_served_and_pending_prizes(): void
    {
        $member = Member::factory()->create([
            'username' => 'prixmoi',
            'password' => 'secret123',
        ]);
        $sewing = RewardTier::query()->where('label', 'Machine à coudre')->firstOrFail();
        $phone = RewardTier::query()->where('label', 'Téléphone')->firstOrFail();

        CommissionLedger::query()->create([
            'member_id' => $member->id,
            'type' => 'reward',
            'amount_usd' => 0,
            'reward_tier_id' => $sewing->id,
            'status' => 'paid',
            'occurred_at' => now(),
        ]);
        CommissionLedger::query()->create([
            'member_id' => $member->id,
            'type' => 'reward',
            'amount_usd' => 0,
            'reward_tier_id' => $phone->id,
            'status' => 'pending',
            'occurred_at' => now(),
        ]);

        $this->actingAs($member, 'member')
            ->get(route('member.dashboard'))
            ->assertOk()
            ->assertSee(__('messages.prize_served'), false)
            ->assertSee(__('messages.prize_pending'), false)
            ->assertSee(__('messages.prize_locked'), false)
            ->assertSee('Machine à coudre', false)
            ->assertSee('Téléphone', false)
            ->assertSee('Moto', false)
            ->assertSee('Voiture', false)
            ->assertSee('phone.png', false);
    }

    /**
     * @return array{0: User, 1: Member, 2: Product, 3: RewardTier}
     */
    private function setupRewardCase(): array
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
        $root = Member::factory()->create();
        $product = Product::factory()->create([
            'unit_price_usd' => 5,
            'pv_per_tablet' => 2,
            'is_active' => true,
        ]);
        $tier = RewardTier::query()->create([
            'label' => 'Palier pied faible',
            'min_pv' => 10,
            'amount_usd' => 0,
            'sort_order' => 0,
            'is_active' => true,
        ]);

        return [$cashier, $root, $product, $tier];
    }
}

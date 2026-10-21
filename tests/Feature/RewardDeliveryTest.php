<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashMovement;
use App\Models\CommissionLedger;
use App\Models\Member;
use App\Models\RewardTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RewardDeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_finance_rewards_page_lists_reached_prize_object(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);
        [$member, $tier, $pending] = $this->reachedPrize();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(__('messages.reward_deliveries'), false);

        $this->actingAs($admin)
            ->get(route('admin.rewards.index'))
            ->assertOk()
            ->assertSee($member->full_name, false)
            ->assertSee($tier->label, false)
            ->assertSee($tier->image_path, false)
            ->assertSee(__('messages.prize_pending'), false)
            ->assertSee(__('messages.reward_serve'), false)
            ->assertSee(route('admin.rewards.serve', $pending), false);
    }

    public function test_serving_prize_marks_it_served_without_cash_out(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);
        [$member, $tier, $pending] = $this->reachedPrize();

        $this->actingAs($admin)
            ->post(route('admin.rewards.serve', $pending))
            ->assertRedirect();

        $this->assertDatabaseHas('commission_ledger', [
            'id' => $pending->id,
            'status' => 'paid',
        ]);
        $this->assertSame(0, CashMovement::query()->where('commission_id', $pending->id)->count());

        $this->actingAs($admin)
            ->get(route('admin.rewards.index', ['status' => 'served']))
            ->assertOk()
            ->assertSee($member->full_name, false)
            ->assertSee($tier->label, false)
            ->assertSee(__('messages.prize_served'), false)
            ->assertDontSee(__('messages.reward_serve'), false);

        $this->actingAs($admin)
            ->get(route('admin.rewards.index'))
            ->assertOk()
            ->assertDontSee($member->full_name, false);
    }

    public function test_served_prize_shows_on_member_dashboard(): void
    {
        $member = Member::factory()->create([
            'username' => 'recompmoi',
            'password' => 'secret123',
        ]);
        $tier = RewardTier::query()->where('label', 'Téléphone')->firstOrFail();
        $row = $this->createReward($member, $tier, 'pending');

        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);
        $this->actingAs($admin)->post(route('admin.rewards.serve', $row))->assertRedirect();

        $this->actingAs($member, 'member')
            ->get(route('member.dashboard'))
            ->assertOk()
            ->assertSee('Téléphone', false)
            ->assertSee(__('messages.prize_served'), false);
    }

    public function test_only_active_plan_prizes_appear_not_other_rewards(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);
        $phone = RewardTier::query()->where('label', 'Téléphone')->firstOrFail();
        $inactive = RewardTier::query()->where('label', 'Bronze')->first()
            ?? RewardTier::query()->create([
                'label' => 'Bronze',
                'min_pv' => 10,
                'amount_usd' => 0,
                'is_active' => false,
            ]);
        $inactive->update(['is_active' => false]);

        $shown = Member::factory()->create(['full_name' => 'Shown Prize']);
        $hidden = Member::factory()->create(['full_name' => 'Hidden Other Reward']);
        $this->createReward($shown, $phone, 'pending');
        $other = $this->createReward($hidden, $inactive, 'pending');
        CommissionLedger::query()->create([
            'member_id' => $shown->id,
            'type' => 'sponsorship',
            'amount_usd' => 15,
            'status' => 'confirmed',
            'occurred_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.rewards.index'))
            ->assertOk()
            ->assertSee('Shown Prize', false)
            ->assertSee('Téléphone', false)
            ->assertDontSee('Hidden Other Reward', false)
            ->assertDontSee('Bronze', false);

        $this->actingAs($admin)
            ->post(route('admin.rewards.serve', $other))
            ->assertNotFound();
    }

    public function test_manager_is_scoped_to_own_branch_rewards(): void
    {
        $branchA = Branch::query()->firstOrFail();
        $branchB = Branch::query()->create([
            'institution_id' => $branchA->institution_id,
            'name' => 'Goma',
            'code' => 'GOMR',
            'is_active' => true,
        ]);
        $tier = RewardTier::query()->where('label', 'Téléphone')->firstOrFail();
        $local = Member::factory()->create([
            'full_name' => 'Local Prize Member',
            'registration_branch_id' => $branchA->id,
        ]);
        $other = Member::factory()->create([
            'full_name' => 'Other Prize Member',
            'registration_branch_id' => $branchB->id,
        ]);
        $this->createReward($local, $tier, 'pending');
        $foreign = $this->createReward($other, $tier, 'pending');

        $manager = User::factory()->create([
            'role' => 'manager',
            'branch_id' => $branchA->id,
        ]);

        $this->actingAs($manager)
            ->get(route('admin.rewards.index'))
            ->assertOk()
            ->assertSee('Local Prize Member', false)
            ->assertDontSee('Other Prize Member', false);

        $this->actingAs($manager)
            ->post(route('admin.rewards.serve', $foreign))
            ->assertForbidden();
    }

    /**
     * @return array{0: Member, 1: RewardTier, 2: CommissionLedger}
     */
    private function reachedPrize(): array
    {
        $member = Member::factory()->create(['full_name' => 'Marie Reward']);
        $tier = RewardTier::query()->where('label', 'Téléphone')->firstOrFail();

        return [$member, $tier, $this->createReward($member, $tier, 'confirmed')];
    }

    private function createReward(Member $member, RewardTier $tier, string $status): CommissionLedger
    {
        return CommissionLedger::query()->create([
            'member_id' => $member->id,
            'type' => 'reward',
            'amount_usd' => 0,
            'reward_tier_id' => $tier->id,
            'status' => $status,
            'occurred_at' => now(),
        ]);
    }
}

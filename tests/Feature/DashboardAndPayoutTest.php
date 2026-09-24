<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CommissionLedger;
use App\Models\Institution;
use App\Models\Member;
use App\Models\PayoutRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAndPayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_dashboard_shows_trend_movements_and_cash_balance(): void
    {
        $cashier = $this->cashier();

        $this->actingAs($cashier)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(__('messages.sales_trend'), false)
            ->assertSee(__('messages.recent_movements'), false)
            ->assertSee(__('messages.cash_balance'), false)
            ->assertSee(__('messages.recent_members'), false);
    }

    public function test_member_can_request_payout_and_admin_sees_alert(): void
    {
        [$branch] = $this->branchAndAccountant();
        $member = Member::factory()->create(['registration_branch_id' => $branch->id]);
        CommissionLedger::query()->create([
            'member_id' => $member->id,
            'type' => 'sponsorship',
            'amount_usd' => 15,
            'status' => 'confirmed',
            'occurred_at' => now(),
        ]);

        $this->actingAs($member, 'member')
            ->post(route('member.payout.request'), [
                'amount' => 10,
                'note' => 'Retrait bureau',
            ])
            ->assertRedirect(route('member.dashboard'));

        $this->assertDatabaseHas('payout_requests', [
            'member_id' => $member->id,
            'amount_usd' => 10,
            'status' => 'pending',
        ]);

        $cashier = User::factory()->create([
            'role' => 'cashier',
            'branch_id' => $branch->id,
        ]);
        $this->actingAs($cashier, 'web')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(__('messages.payout_alerts_title'), false)
            ->assertSee($member->full_name, false)
            ->assertSee('10.00', false);
    }

    public function test_accountant_can_pay_payout_request(): void
    {
        [$branch, $accountant] = $this->branchAndAccountant();
        $member = Member::factory()->create(['registration_branch_id' => $branch->id]);
        CommissionLedger::query()->create([
            'member_id' => $member->id,
            'type' => 'sponsorship',
            'amount_usd' => 20,
            'status' => 'confirmed',
            'occurred_at' => now(),
        ]);

        $request = PayoutRequest::query()->create([
            'member_id' => $member->id,
            'amount_usd' => 20,
            'status' => 'pending',
        ]);

        $this->actingAs($accountant)
            ->post(route('admin.payouts.pay', $request))
            ->assertRedirect();

        $this->assertSame('paid', $request->fresh()->status);
        $this->assertDatabaseHas('commission_ledger', [
            'member_id' => $member->id,
            'status' => 'paid',
            'amount_usd' => 20,
        ]);
        $this->assertDatabaseHas('cash_movements', [
            'direction' => 'out',
            'category' => 'commission',
            'amount_usd' => 20,
            'branch_id' => $branch->id,
        ]);
    }

    public function test_member_cannot_request_more_than_available(): void
    {
        $member = Member::factory()->create();
        CommissionLedger::query()->create([
            'member_id' => $member->id,
            'type' => 'sponsorship',
            'amount_usd' => 5,
            'status' => 'confirmed',
            'occurred_at' => now(),
        ]);

        $this->actingAs($member, 'member')
            ->from(route('member.dashboard'))
            ->post(route('member.payout.request'), ['amount' => 12])
            ->assertRedirect(route('member.dashboard'))
            ->assertSessionHasErrors('amount');
    }

    private function cashier(): User
    {
        [$branch] = $this->branchAndAccountant();

        return User::factory()->create([
            'role' => 'cashier',
            'branch_id' => $branch->id,
        ]);
    }

    /**
     * @return array{0: Branch, 1: User}
     */
    private function branchAndAccountant(): array
    {
        $institution = Institution::query()->firstOrFail();
        $branch = Branch::query()->where('code', 'BTB')->first() ?? Branch::query()->create([
            'institution_id' => $institution->id,
            'name' => 'Test',
            'code' => 'TST',
            'is_active' => true,
        ]);
        $accountant = User::factory()->create([
            'role' => 'accountant',
            'branch_id' => $branch->id,
        ]);

        return [$branch, $accountant];
    }
}

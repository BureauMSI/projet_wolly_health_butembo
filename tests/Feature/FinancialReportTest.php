<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Institution;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_report_menu_lists_financial_categories(): void
    {
        $this->actingAs($this->accountant())
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee(__('messages.report_cash_book'))
            ->assertSee(__('messages.report_sales_journal'))
            ->assertSee(__('messages.report_cash_movements'))
            ->assertSee(__('messages.report_commissions_paid'))
            ->assertSee(__('messages.report_commissions_unpaid'))
            ->assertSee(__('messages.report_members'))
            ->assertSee(__('messages.report_tree'));
    }

    public function test_staff_can_print_binary_tree(): void
    {
        $root = Member::factory()->create(['full_name' => 'Racine Bureau']);
        Member::factory()->create([
            'full_name' => 'Enfant Bureau',
            'placement_parent_id' => $root->id,
            'placement_side' => 'left',
        ]);

        $this->actingAs($this->accountant())
            ->get(route('admin.reports.print', ['type' => 'tree']))
            ->assertOk()
            ->assertSee(__('messages.report_tree'))
            ->assertSee('Racine Bureau', false)
            ->assertSee('Enfant Bureau', false)
            ->assertSee('mlm-tree', false);
    }

    public function test_a4_cash_book_includes_company_and_outflow(): void
    {
        $accountant = $this->accountant();

        $this->actingAs($accountant)->post(route('admin.operations.store'), [
            'direction' => 'out',
            'category' => 'charge',
            'amount' => 12,
            'currency_code' => 'USD',
        ])->assertRedirect();

        $this->actingAs($accountant)
            ->get(route('admin.reports.print', [
                'type' => 'cash_book',
                'from' => now()->toDateString(),
                'to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('Holy Health')
            ->assertSee('Butembo')
            ->assertSee(__('messages.report_cash_book'))
            ->assertSee(__('messages.opening_balance'))
            ->assertSee(__('messages.cash_category_charge'))
            ->assertSee('12.00')
            ->assertSee(__('messages.report_signature'));
    }

    public function test_member_follow_up_report_shows_pv_and_gains_columns(): void
    {
        $accountant = $this->accountant();
        $member = Member::factory()->create([
            'full_name' => 'Rapport Membre Test',
            'registration_branch_id' => $this->branch()->id,
        ]);

        $this->actingAs($accountant)
            ->get(route('admin.reports.print', ['type' => 'members']))
            ->assertOk()
            ->assertSee(__('messages.report_members'))
            ->assertSee($member->full_name)
            ->assertSee(__('messages.pv_confirmed'))
            ->assertSee(__('messages.gains_paid'));
    }

    public function test_cashier_can_print_cash_book_but_not_member_report(): void
    {
        $cashier = User::factory()->create([
            'role' => 'cashier',
            'branch_id' => $this->branch()->id,
        ]);

        $this->actingAs($cashier)
            ->get(route('admin.reports.print', ['type' => 'cash_book']))
            ->assertOk()
            ->assertSee(__('messages.report_cash_book'));

        $this->actingAs($cashier)
            ->get(route('admin.reports.print', ['type' => 'summary']))
            ->assertForbidden();

        $this->actingAs($cashier)
            ->get(route('admin.reports.print', ['type' => 'members']))
            ->assertForbidden();
    }

    public function test_legacy_print_url_redirects_to_typed_a4(): void
    {
        $this->actingAs($this->accountant())
            ->get(route('admin.reports.print.legacy', ['type' => 'cash']))
            ->assertRedirect(route('admin.reports.print', ['type' => 'cash']));
    }

    public function test_commission_reports_show_equilibrium_labels(): void
    {
        $parent = Member::factory()->create([
            'full_name' => 'Parent Equilibre',
            'registration_branch_id' => $this->branch()->id,
        ]);
        $child = Member::factory()->create([
            'full_name' => 'Enfant Source',
            'placement_parent_id' => $parent->id,
            'placement_side' => 'left',
            'registration_branch_id' => $this->branch()->id,
        ]);

        \App\Models\CommissionLedger::query()->create([
            'member_id' => $parent->id,
            'type' => 'equilibrium',
            'amount_usd' => 4,
            'related_member_id' => $child->id,
            'generation' => 1,
            'status' => 'confirmed',
            'occurred_at' => now(),
        ]);

        $this->actingAs($this->accountant())
            ->get(route('admin.reports.print', [
                'type' => 'commissions_unpaid',
                'from' => now()->toDateString(),
                'to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee(__('messages.equilibrium_n', ['n' => 1]), false)
            ->assertSee('Parent Equilibre', false)
            ->assertSee('Enfant Source', false);

        $this->actingAs($this->accountant())
            ->get(route('admin.commissions.index'))
            ->assertOk()
            ->assertSee(__('messages.equilibrium_n', ['n' => 1]), false);
    }

    private function accountant(): User
    {
        return User::factory()->create([
            'role' => 'accountant',
            'branch_id' => $this->branch()->id,
        ]);
    }

    private function branch(): Branch
    {
        $institution = Institution::query()->firstOrFail();

        return Branch::query()->where('code', 'BTB')->first() ?? Branch::query()->create([
            'institution_id' => $institution->id,
            'name' => 'Test',
            'code' => 'TST',
            'is_active' => true,
        ]);
    }
}

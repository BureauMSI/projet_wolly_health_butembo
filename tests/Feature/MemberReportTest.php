<?php

namespace Tests\Feature;

use App\Models\CommissionLedger;
use App\Models\Member;
use App\Models\PlanSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_member_catalog_lists_own_reports(): void
    {
        $member = Member::factory()->create();

        $this->actingAs($member, 'member')
            ->get(route('member.reports.index'))
            ->assertOk()
            ->assertSee(__('messages.report_member_sheet'))
            ->assertSee(__('messages.report_member_statement'))
            ->assertSee(__('messages.report_member_pv'))
            ->assertSee(__('messages.report_member_commissions_paid'))
            ->assertSee(__('messages.report_member_commissions_unpaid'))
            ->assertSee(__('messages.report_member_purchases'))
            ->assertSee(__('messages.report_member_network'))
            ->assertSee(__('messages.report_member_tree'));
    }

    public function test_member_paid_commissions_show_own_gains_not_another_member(): void
    {
        $member = Member::factory()->create(['full_name' => 'Alice Visible']);
        $other = Member::factory()->create(['full_name' => 'Bob Hidden']);

        CommissionLedger::query()->create([
            'member_id' => $member->id,
            'type' => 'sponsorship',
            'amount_usd' => 15,
            'status' => 'paid',
            'occurred_at' => now(),
        ]);
        CommissionLedger::query()->create([
            'member_id' => $other->id,
            'type' => 'sponsorship',
            'amount_usd' => 99,
            'status' => 'paid',
            'occurred_at' => now(),
        ]);

        $this->actingAs($member, 'member')
            ->get(route('member.reports.print', ['type' => 'member_commissions_paid']))
            ->assertOk()
            ->assertSee(__('messages.report_member_commissions_paid'))
            ->assertSee($member->member_code)
            ->assertSee($member->full_name)
            ->assertSee('15.00')
            ->assertDontSee($other->full_name)
            ->assertDontSee('99.00')
            ->assertSee(__('messages.report_sign_member'));
    }

    public function test_member_tree_print_shows_own_downline_not_siblings(): void
    {
        $root = Member::factory()->create(['full_name' => 'Racine Imprimable']);
        $member = Member::factory()->create([
            'full_name' => 'Moi Arbre Imprime',
            'placement_parent_id' => $root->id,
            'placement_side' => 'left',
        ]);
        Member::factory()->create([
            'full_name' => 'Enfant Imprime',
            'placement_parent_id' => $member->id,
            'placement_side' => 'left',
        ]);
        Member::factory()->create([
            'full_name' => 'Frere Non Imprime',
            'placement_parent_id' => $root->id,
            'placement_side' => 'right',
        ]);

        $this->actingAs($member, 'member')
            ->get(route('member.reports.print', 'member_tree'))
            ->assertOk()
            ->assertSee(__('messages.report_member_tree'))
            ->assertSee('Moi Arbre Imprime', false)
            ->assertSee('Enfant Imprime', false)
            ->assertDontSee('Frere Non Imprime', false)
            ->assertDontSee('Racine Imprimable', false)
            ->assertSee('mlm-tree', false);
    }

    public function test_membership_sheet_shows_identity_amount_and_guide(): void
    {
        $defaultPassword = PlanSetting::query()->where('key', 'default_member_password')->value('value');
        $member = Member::factory()->create([
            'full_name' => 'Fiche Alice',
            'username' => 'fichealice',
            'phone' => '099111111',
            'address' => 'Butembo centre',
            'membership_amount_usd' => 25,
            'password' => $defaultPassword,
        ]);
        $other = Member::factory()->create(['full_name' => 'Fiche Bob Cache']);

        $this->actingAs($member, 'member')
            ->get(route('member.reports.print', 'member_sheet'))
            ->assertOk()
            ->assertSee(__('messages.membership_sheet'))
            ->assertSee('Fiche Alice')
            ->assertSee('fichealice')
            ->assertSee($defaultPassword)
            ->assertSee(__('messages.sheet_login'))
            ->assertSee('099111111')
            ->assertSee('Butembo centre')
            ->assertSee('25.00')
            ->assertSee(__('messages.sheet_amount_paid'))
            ->assertSee(__('messages.sheet_guide_access_title'))
            ->assertSee(__('messages.sheet_guide_how_title'))
            ->assertSee(__('messages.sheet_guide_phone_title'))
            ->assertDontSee('Fiche Bob Cache')
            ->assertDontSee(__('messages.sheet_usage'))
            ->assertDontSee($member->getAuthPassword());
    }

    public function test_membership_sheet_hides_custom_password_and_shows_default_when_used(): void
    {
        $member = Member::factory()->create([
            'username' => 'ficheperso',
            'password' => 'SecretPerso99',
        ]);

        $this->actingAs($member, 'member')
            ->get(route('member.reports.print', 'member_sheet'))
            ->assertOk()
            ->assertSee('ficheperso')
            ->assertSee(__('messages.sheet_password_personal'))
            ->assertDontSee('SecretPerso99');
    }

    public function test_staff_membership_sheet_includes_identity_and_guide(): void
    {
        $member = Member::factory()->create([
            'full_name' => 'Fiche Bureau',
            'membership_amount_usd' => 18,
        ]);
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);

        $this->actingAs($admin, 'web')
            ->get(route('admin.members.print', $member))
            ->assertOk()
            ->assertSee(__('messages.membership_sheet'))
            ->assertSee('Fiche Bureau')
            ->assertSee(__('messages.sheet_amount_paid'))
            ->assertSee('18.00')
            ->assertSee(__('messages.sheet_guide_phone_title'))
            ->assertDontSee(__('messages.sheet_usage'));
    }

    public function test_staff_cannot_open_member_reports(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);

        $this->actingAs($admin)
            ->get(route('member.reports.index'))
            ->assertRedirect(route('login'));
    }

    public function test_member_cannot_open_staff_reports(): void
    {
        $member = Member::factory()->create();

        $this->actingAs($member, 'member')
            ->get(route('admin.reports.index'))
            ->assertRedirect(route('login'));
    }
}

<?php

namespace Tests\Feature;

use App\Models\CommissionLedger;
use App\Models\Member;
use App\Models\PvLedger;
use App\Models\RewardTier;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_member_can_login_and_see_portal(): void
    {
        $member = Member::factory()->create([
            'username' => 'memberlogin',
            'password' => 'secret123',
            'status' => 'active',
        ]);

        $this->post(route('login'), [
            'username' => 'memberlogin',
            'password' => 'secret123',
        ])->assertRedirect(route('member.dashboard'));

        $this->assertAuthenticatedAs($member, 'member');
        $this->get(route('member.dashboard'))->assertOk()->assertSee($member->full_name);
    }

    public function test_member_login_screen_redirects_to_unified_login(): void
    {
        $this->get(route('member.login'))
            ->assertRedirect(route('login'));

        $this->get(route('login'))
            ->assertOk()
            ->assertSee(__('messages.sign_in'), false)
            ->assertSee(__('messages.login_hint'), false);
    }

    public function test_member_login_rejects_bad_credentials(): void
    {
        $this->from(route('login'))->post(route('login'), [
            'username' => 'memberlogin',
            'password' => 'wrong-password',
        ])->assertRedirect(route('login'))->assertSessionHasErrors('username');
    }

    public function test_member_cannot_open_staff_admin(): void
    {
        $member = Member::factory()->create();

        $this->actingAs($member, 'member')
            ->get(route('admin.users.index'))
            ->assertRedirect(route('login'));
    }

    public function test_staff_cannot_open_member_portal(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);

        $this->actingAs($admin)
            ->get(route('member.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_member_tree_shows_own_downline_not_siblings(): void
    {
        $root = Member::factory()->create(['full_name' => 'Racine Portail']);
        $member = Member::factory()->create([
            'full_name' => 'Moi Généalogie',
            'username' => 'arbremoi',
            'password' => 'secret123',
            'placement_parent_id' => $root->id,
            'placement_side' => 'left',
        ]);
        Member::factory()->create([
            'full_name' => 'Enfant Visible',
            'placement_parent_id' => $member->id,
            'placement_side' => 'left',
        ]);
        Member::factory()->create([
            'full_name' => 'Frere Cache',
            'placement_parent_id' => $root->id,
            'placement_side' => 'right',
        ]);

        $this->actingAs($member, 'member')
            ->get(route('member.network'))
            ->assertOk()
            ->assertSee('Moi Généalogie', false)
            ->assertSee('Enfant Visible', false)
            ->assertDontSee('Frere Cache', false)
            ->assertDontSee('Racine Portail', false)
            ->assertSee('mlm-tree', false)
            ->assertSee('tree-phone', false)
            ->assertSee(__('messages.member_tree_tap_hint'), false)
            ->assertSee('data-generation="0"', false)
            ->assertSee('data-drill-url', false)
            ->assertSee(__('messages.left'), false)
            ->assertSee(__('messages.right'), false)
            ->assertSee(__('messages.empty_leg'), false);
    }

    public function test_member_network_drill_shows_descendant_as_root(): void
    {
        $member = Member::factory()->create([
            'full_name' => 'Chef Reseau',
            'username' => 'chefreseau',
            'password' => 'secret123',
        ]);
        $child = Member::factory()->create([
            'full_name' => 'Enfant Drill',
            'placement_parent_id' => $member->id,
            'placement_side' => 'left',
        ]);
        $grand = Member::factory()->create([
            'full_name' => 'Petit Drill',
            'placement_parent_id' => $child->id,
            'placement_side' => 'right',
        ]);

        $this->actingAs($member, 'member')
            ->get(route('member.network', ['root' => $child->id]))
            ->assertOk()
            ->assertSee('Enfant Drill', false)
            ->assertSee('Petit Drill', false)
            ->assertSee(__('messages.tree_back_root'), false);
    }

    public function test_member_tree_shows_left_and_right_leg_pv(): void
    {
        $root = Member::factory()->create(['full_name' => 'Racine Volume']);
        $member = Member::factory()->create([
            'full_name' => 'Chef De Jambes',
            'username' => 'jambes',
            'password' => 'secret123',
            'placement_parent_id' => $root->id,
            'placement_side' => 'left',
        ]);
        $left = Member::factory()->create([
            'full_name' => 'Jambe Gauche',
            'placement_parent_id' => $member->id,
            'placement_side' => 'left',
        ]);
        $right = Member::factory()->create([
            'full_name' => 'Jambe Droite',
            'placement_parent_id' => $member->id,
            'placement_side' => 'right',
        ]);
        PvLedger::query()->create([
            'member_id' => $left->id,
            'source_type' => 'own_purchase',
            'pv_amount' => 11.5,
            'occurred_at' => now(),
            'sync_status' => 'confirmed',
        ]);
        PvLedger::query()->create([
            'member_id' => $right->id,
            'source_type' => 'own_purchase',
            'pv_amount' => 7.25,
            'occurred_at' => now(),
            'sync_status' => 'pending',
        ]);

        $this->actingAs($member, 'member')
            ->get(route('member.network'))
            ->assertOk()
            ->assertSee(__('messages.left_leg_pv'), false)
            ->assertSee('11.50', false)
            ->assertSee('7.25', false);

        $this->actingAs($member, 'member')
            ->get(route('member.dashboard'))
            ->assertOk()
            ->assertSee(__('messages.left_leg_pv'), false)
            ->assertSee(__('messages.right_leg_pv'), false)
            ->assertSee('11.50', false)
            ->assertSee('7.25', false);
    }

    public function test_member_sees_pv_balance_history_and_achieved_gains(): void
    {
        $member = Member::factory()->create([
            'username' => 'soldemoi',
            'password' => 'secret123',
        ]);
        $tier = RewardTier::query()->create([
            'label' => 'Palier Portail',
            'min_pv' => 5,
            'amount_usd' => 6,
            'sort_order' => 90,
            'is_active' => true,
        ]);
        PvLedger::query()->create([
            'member_id' => $member->id,
            'source_type' => 'own_purchase',
            'pv_amount' => 8,
            'occurred_at' => now(),
            'sync_status' => 'pending',
        ]);
        PvLedger::query()->create([
            'member_id' => $member->id,
            'source_type' => 'membership',
            'pv_amount' => 12,
            'occurred_at' => now(),
            'sync_status' => 'confirmed',
        ]);
        CommissionLedger::query()->create([
            'member_id' => $member->id,
            'type' => 'sponsorship',
            'amount_usd' => 3,
            'status' => 'pending',
            'occurred_at' => now(),
        ]);
        CommissionLedger::query()->create([
            'member_id' => $member->id,
            'type' => 'equilibrium',
            'amount_usd' => 7,
            'status' => 'confirmed',
            'occurred_at' => now(),
        ]);
        CommissionLedger::query()->create([
            'member_id' => $member->id,
            'type' => 'reward',
            'amount_usd' => 6,
            'reward_tier_id' => $tier->id,
            'status' => 'paid',
            'occurred_at' => now(),
        ]);

        $this->actingAs($member, 'member')
            ->get(route('member.dashboard'))
            ->assertOk()
            ->assertSee(__('messages.pv_pending'), false)
            ->assertSee('8.00', false)
            ->assertSee('12.00', false)
            ->assertSee('20.00', false)
            ->assertSee('3.00', false)
            ->assertSee('7.00', false)
            ->assertSee('6.00', false)
            ->assertSee('Palier Portail', false)
            ->assertSee(__('messages.prize_served'), false)
            ->assertSee(__('messages.gains_paid'), false)
            ->assertSee(__('messages.gains_pending'), false)
            ->assertSee(__('messages.own_purchase'), false)
            ->assertSee('sewing-machine.png', false)
            ->assertSee('Machine à coudre', false);

        $this->actingAs($member, 'member')
            ->get(route('member.history'))
            ->assertOk()
            ->assertSee(__('messages.pv_history'), false)
            ->assertSee(__('messages.membership'), false)
            ->assertSee(__('messages.sponsorship'), false)
            ->assertSee('Palier Portail', false);
    }

    public function test_member_can_update_own_name_and_phone(): void
    {
        $member = Member::factory()->create([
            'full_name' => 'Ancien Nom',
            'phone' => '099111111',
            'username' => 'profilmoi',
        ]);

        $this->actingAs($member, 'member')->put(route('member.profile.update'), [
            'full_name' => 'Nouveau Nom',
            'phone' => '099222222',
            'gender' => 'female',
            'address' => 'Butembo',
            'username' => 'hacker',
            'member_code' => 'HH-99999',
        ])->assertRedirect(route('member.profile'));

        $member->refresh();
        $this->assertSame('Nouveau Nom', $member->full_name);
        $this->assertSame('099222222', $member->phone);
        $this->assertSame('female', $member->gender);
        $this->assertSame('Butembo', $member->address);
        $this->assertSame('profilmoi', $member->username);
        $this->assertNotSame('HH-99999', $member->member_code);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'updated',
            'auditable_type' => Member::class,
            'auditable_id' => $member->id,
        ]);
        $this->assertDatabaseHas('sync_outbox', [
            'entity_type' => 'member',
            'entity_uuid' => $member->uuid,
            'operation' => 'update',
        ]);
    }

    public function test_member_profile_name_is_required(): void
    {
        $member = Member::factory()->create(['full_name' => 'Nom Garde']);

        $this->actingAs($member, 'member')
            ->from(route('member.profile'))
            ->put(route('member.profile.update'), [
                'full_name' => '  ',
                'phone' => '099333333',
            ])
            ->assertSessionHasErrors('full_name');

        $this->assertSame('Nom Garde', $member->fresh()->full_name);
    }

    public function test_member_can_upload_profile_photo_without_changing_username(): void
    {
        Storage::fake('public');
        $member = Member::factory()->create([
            'username' => 'photomoi',
            'photo_path' => null,
        ]);
        $photo = UploadedFile::fake()->image('avatar.jpg', 200, 200);

        $this->actingAs($member, 'member')->post(route('member.profile.update'), [
            '_method' => 'PUT',
            'full_name' => $member->full_name,
            'phone' => $member->phone,
            'photo' => $photo,
        ])->assertRedirect(route('member.profile'));

        $member->refresh();
        $this->assertSame('photomoi', $member->username);
        $this->assertNotNull($member->photo_path);
        Storage::disk('public')->assertExists($member->photo_path);
    }

    public function test_staff_cannot_update_member_profile_via_member_route(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);

        $this->actingAs($admin)
            ->put(route('member.profile.update'), [
                'full_name' => 'Intrus',
                'phone' => '099000000',
            ])
            ->assertRedirect(route('login'));
    }

    public function test_member_sees_left_menu_top_bar_and_account_operation_alerts(): void
    {
        $member = Member::factory()->create(['username' => 'alertmoi']);
        PvLedger::query()->create([
            'member_id' => $member->id,
            'source_type' => 'own_purchase',
            'pv_amount' => 13.75,
            'occurred_at' => now(),
            'sync_status' => 'pending',
        ]);
        CommissionLedger::query()->create([
            'member_id' => $member->id,
            'type' => 'sponsorship',
            'amount_usd' => 4.4,
            'status' => 'confirmed',
            'occurred_at' => now(),
        ]);

        $this->actingAs($member, 'member')
            ->get(route('member.dashboard'))
            ->assertOk()
            ->assertSee('staff-sidebar', false)
            ->assertSee('staff-topbar', false)
            ->assertSee(__('messages.account_alerts'), false)
            ->assertSee('13.75', false)
            ->assertSee('4.40', false);

        $this->actingAs($member, 'member')
            ->get(route('member.alerts'))
            ->assertOk()
            ->assertSee(__('messages.alert_pv', [
                'source' => __('messages.own_purchase'),
                'amount' => '13.75',
            ]), false)
            ->assertSee(__('messages.sponsorship'), false);
    }
}

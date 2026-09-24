<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashMovement;
use App\Models\Client;
use App\Models\Member;
use App\Models\PlanSetting;
use App\Models\PvLedger;
use App\Models\RegistrationCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_username_receives_configured_suffix_and_photo_is_optional(): void
    {
        PlanSetting::query()->where('key', 'username_suffix_length')->update(['value' => '4']);
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);

        $this->actingAs($admin)->post(route('admin.members.store'), [
            'full_name' => 'Jean Kambale',
            'username' => 'jean',
            'locale' => 'fr',
        ])->assertRedirect();

        $member = Member::query()->where('full_name', 'Jean Kambale')->firstOrFail();
        $this->assertSame(4, strlen(substr($member->username, strlen('jean'))));
        $this->assertTrue(str_starts_with($member->username, 'jean'));
        $this->assertNull($member->photo_path);
        $this->assertNull($member->id_document_path);
    }

    public function test_registration_does_not_set_sponsor_or_placement(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);

        $this->actingAs($admin)->post(route('admin.members.store'), [
            'full_name' => 'Racine',
            'username' => 'racine',
            'locale' => 'fr',
        ])->assertRedirect();
        $root = Member::query()->where('full_name', 'Racine')->firstOrFail();
        $this->assertTrue($root->isTreeRoot());
        $this->assertFalse($root->isAwaitingPlacement());

        $this->actingAs($admin)->post(route('admin.members.store'), [
            'full_name' => 'Filleul',
            'username' => 'filleul',
            'locale' => 'fr',
            'sponsor_id' => $root->id,
            'placement_parent_id' => $root->id,
            'placement_side' => 'left',
        ])->assertRedirect();

        $child = Member::query()->where('full_name', 'Filleul')->firstOrFail();
        $this->assertNull($child->sponsor_id);
        $this->assertNull($child->placement_parent_id);
        $this->assertNull($child->placement_side);
        $this->assertTrue($child->isAwaitingPlacement());
        $this->assertDatabaseMissing('commission_ledger', [
            'member_id' => $root->id,
            'type' => 'sponsorship',
        ]);
    }

    public function test_placement_happens_after_registration_and_rejects_a_taken_side(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);
        $this->actingAs($admin)->post(route('admin.members.store'), [
            'full_name' => 'Racine',
            'username' => 'racine',
            'locale' => 'fr',
        ]);
        $root = Member::query()->where('full_name', 'Racine')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.members.store'), [
            'full_name' => 'Gauche 1',
            'username' => 'gauche1',
            'locale' => 'fr',
        ]);
        $this->actingAs($admin)->post(route('admin.members.store'), [
            'full_name' => 'Gauche 2',
            'username' => 'gauche2',
            'locale' => 'fr',
        ]);
        $first = Member::query()->where('full_name', 'Gauche 1')->firstOrFail();
        $second = Member::query()->where('full_name', 'Gauche 2')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.placements.store', $first), [
            'sponsor_id' => $root->id,
            'placement_parent_id' => $root->id,
            'placement_side' => 'left',
        ])->assertRedirect(route('admin.placements.index'));

        $this->assertSame($root->id, $first->fresh()->placement_parent_id);
        $this->assertSame('left', $first->fresh()->placement_side);
        $this->assertSame($root->id, $first->fresh()->sponsor_id);

        $this->actingAs($admin)->from(route('admin.placements.index', ['member' => $second->id]))->post(route('admin.placements.store', $second), [
            'sponsor_id' => $root->id,
            'placement_parent_id' => $root->id,
            'placement_side' => 'left',
        ])->assertSessionHasErrors('placement_side');

        $this->assertNull($second->fresh()->placement_parent_id);
        $this->assertNull($second->fresh()->sponsor_id);
        $this->assertSame(1, Member::query()->where('placement_parent_id', $root->id)->where('placement_side', 'left')->count());
    }

    public function test_placed_member_is_not_moved_silently(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);
        $this->actingAs($admin)->post(route('admin.members.store'), [
            'full_name' => 'Racine',
            'username' => 'racine',
            'locale' => 'fr',
        ]);
        $root = Member::query()->where('full_name', 'Racine')->firstOrFail();
        $this->actingAs($admin)->post(route('admin.members.store'), [
            'full_name' => 'Enfant',
            'username' => 'enfant',
            'locale' => 'fr',
        ]);
        $child = Member::query()->where('full_name', 'Enfant')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.placements.store', $child), [
            'sponsor_id' => $root->id,
            'placement_parent_id' => $root->id,
            'placement_side' => 'right',
        ])->assertRedirect();

        $this->actingAs($admin)->from(route('admin.placements.index', ['member' => $child->id]))->post(route('admin.placements.store', $child), [
            'sponsor_id' => $root->id,
            'placement_parent_id' => $root->id,
            'placement_side' => 'left',
        ])->assertSessionHasErrors('placement_side');

        $this->assertSame('right', $child->fresh()->placement_side);
        $this->assertSame($root->id, $child->fresh()->sponsor_id);
    }

    public function test_placement_parent_must_already_be_in_the_tree(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);
        $this->actingAs($admin)->post(route('admin.members.store'), [
            'full_name' => 'Racine',
            'username' => 'racine',
            'locale' => 'fr',
        ]);
        $this->actingAs($admin)->post(route('admin.members.store'), [
            'full_name' => 'Attente',
            'username' => 'attente',
            'locale' => 'fr',
        ]);
        $this->actingAs($admin)->post(route('admin.members.store'), [
            'full_name' => 'Nouveau',
            'username' => 'nouveau',
            'locale' => 'fr',
        ]);
        $root = Member::query()->where('full_name', 'Racine')->firstOrFail();
        $waiting = Member::query()->where('full_name', 'Attente')->firstOrFail();
        $new = Member::query()->where('full_name', 'Nouveau')->firstOrFail();

        $this->assertTrue($waiting->isAwaitingPlacement());

        $this->actingAs($admin)->from(route('admin.placements.index', ['member' => $new->id]))->post(route('admin.placements.store', $new), [
            'sponsor_id' => $root->id,
            'placement_parent_id' => $waiting->id,
            'placement_side' => 'left',
        ])->assertSessionHasErrors('placement_parent_id');

        $this->assertNull($new->fresh()->placement_parent_id);
        $this->assertNull($new->fresh()->sponsor_id);
    }

    public function test_sponsorship_is_credited_at_assignment_from_plan_settings(): void
    {
        PlanSetting::query()->where('key', 'sponsorship_amount_usd')->update(['value' => '21']);
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);

        $this->actingAs($admin)->post(route('admin.members.store'), [
            'full_name' => 'Parrain',
            'username' => 'parrain',
            'locale' => 'fr',
        ])->assertRedirect();
        $sponsor = Member::query()->where('full_name', 'Parrain')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.members.store'), [
            'full_name' => 'Filleul',
            'username' => 'filleul',
            'locale' => 'fr',
            'sponsor_id' => $sponsor->id,
        ])->assertRedirect();
        $child = Member::query()->where('full_name', 'Filleul')->firstOrFail();

        $this->assertDatabaseMissing('commission_ledger', [
            'member_id' => $sponsor->id,
            'type' => 'sponsorship',
        ]);
        $this->assertNull($child->sponsor_id);

        $this->actingAs($admin)->post(route('admin.placements.store', $child), [
            'sponsor_id' => $sponsor->id,
            'placement_parent_id' => $sponsor->id,
            'placement_side' => 'left',
        ])->assertRedirect();

        $this->assertSame($sponsor->id, $child->fresh()->sponsor_id);
        $this->assertDatabaseHas('commission_ledger', [
            'member_id' => $sponsor->id,
            'type' => 'sponsorship',
            'amount_usd' => 21,
        ]);
        $this->assertDatabaseMissing('commission_ledger', [
            'member_id' => $sponsor->id,
            'type' => 'sponsorship',
            'amount_usd' => 15,
        ]);
        $this->assertSame(1, $sponsor->commissions()->where('type', 'sponsorship')->count());
    }

    public function test_sponsor_can_differ_from_placement_parent(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);
        $this->actingAs($admin)->post(route('admin.members.store'), [
            'full_name' => 'Racine',
            'username' => 'racine',
            'locale' => 'fr',
        ]);
        $root = Member::query()->where('full_name', 'Racine')->firstOrFail();
        $this->actingAs($admin)->post(route('admin.members.store'), [
            'full_name' => 'Parrain',
            'username' => 'parrain',
            'locale' => 'fr',
        ]);
        $sponsor = Member::query()->where('full_name', 'Parrain')->firstOrFail();
        $this->actingAs($admin)->post(route('admin.members.store'), [
            'full_name' => 'Filleul',
            'username' => 'filleul',
            'locale' => 'fr',
        ]);
        $child = Member::query()->where('full_name', 'Filleul')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.placements.store', $child), [
            'sponsor_id' => $sponsor->id,
            'placement_parent_id' => $root->id,
            'placement_side' => 'left',
        ])->assertRedirect();

        $child->refresh();
        $this->assertSame($sponsor->id, $child->sponsor_id);
        $this->assertSame($root->id, $child->placement_parent_id);
        $this->assertNotSame($child->sponsor_id, $child->placement_parent_id);
        $this->assertDatabaseHas('commission_ledger', [
            'member_id' => $sponsor->id,
            'type' => 'sponsorship',
        ]);
        $this->assertDatabaseMissing('commission_ledger', [
            'member_id' => $root->id,
            'type' => 'sponsorship',
        ]);
    }

    public function test_assignment_menu_lists_unplaced_members_and_create_has_no_sponsor(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);
        $this->actingAs($admin)->post(route('admin.members.store'), [
            'full_name' => 'Racine',
            'username' => 'racine',
            'locale' => 'fr',
        ]);
        $this->actingAs($admin)->post(route('admin.members.store'), [
            'full_name' => 'Filleul',
            'username' => 'filleul',
            'locale' => 'fr',
        ]);
        $child = Member::query()->where('full_name', 'Filleul')->firstOrFail();

        $this->actingAs($admin)->get(route('admin.members.create'))
            ->assertOk()
            ->assertDontSee('name="sponsor_id"', false)
            ->assertSee(__('messages.sponsor_at_assignment'));

        $this->actingAs($admin)->get(route('admin.members.show', $child))
            ->assertOk()
            ->assertDontSee('name="placement_parent_id"', false)
            ->assertSee(__('messages.go_to_assignment'));

        $this->actingAs($admin)->get(route('admin.placements.index'))
            ->assertOk()
            ->assertSee('Filleul', false)
            ->assertSee('name="sponsor_id"', false)
            ->assertSee('name="placement_parent_id"', false)
            ->assertSee(__('messages.assignment'), false);
    }

    public function test_username_with_spaces_and_accents_is_sanitized(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);

        $this->actingAs($admin)->post(route('admin.members.store'), [
            'full_name' => 'Jean Kambalé',
            'username' => 'Jean Kambalé',
            'locale' => 'fr',
            'phone' => '0991112233',
        ])->assertRedirect();

        $member = Member::query()->where('full_name', 'Jean Kambalé')->firstOrFail();
        $this->assertTrue(str_starts_with($member->username, 'jeankambale'));
        $this->assertSame('HH-00001', $member->member_code);
    }

    public function test_registration_uses_fallback_password_when_plan_setting_missing(): void
    {
        PlanSetting::query()->where('key', 'default_member_password')->delete();
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);

        $this->actingAs($admin)->post(route('admin.members.store'), [
            'full_name' => 'Sans Mot De Passe',
            'username' => 'sansmdp',
            'locale' => 'fr',
        ])->assertRedirect();

        $this->assertDatabaseHas('members', ['full_name' => 'Sans Mot De Passe']);
    }

    public function test_member_code_skips_existing_gaps(): void
    {
        Member::factory()->create(['member_code' => 'HH-00001', 'username' => 'a1']);
        Member::factory()->create(['member_code' => 'HH-00003', 'username' => 'a3']);
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);

        $this->actingAs($admin)->post(route('admin.members.store'), [
            'full_name' => 'Suivant',
            'username' => 'suivant',
            'locale' => 'fr',
        ])->assertRedirect();

        $this->assertSame('HH-00004', Member::query()->where('full_name', 'Suivant')->value('member_code'));
    }

    public function test_short_password_shows_translated_min_message(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);

        $this->actingAs($admin)->post(route('admin.members.store'), [
            'full_name' => 'Court Mot',
            'username' => 'court',
            'locale' => 'fr',
            'password' => 'abc',
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('members', ['full_name' => 'Court Mot']);
        $errors = session('errors');
        $this->assertNotNull($errors);
        $this->assertStringContainsString('8', $errors->first('password'));
        $this->assertStringNotContainsString('validation.min.string', $errors->first('password'));
    }

    public function test_create_form_shows_membership_fee_from_plan_settings(): void
    {
        PlanSetting::query()->where('key', 'membership_amount_usd')->update(['value' => '33']);
        PlanSetting::query()->where('key', 'membership_pv_threshold')->update(['value' => '11']);
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);

        $this->actingAs($admin)->get(route('admin.members.create'))
            ->assertOk()
            ->assertSee('readonly', false)
            ->assertDontSee('name="membership_amount_usd"', false)
            ->assertSee('33.00 USD', false)
            ->assertSee(__('messages.membership_pv_hint', ['pv' => '11']), false)
            ->assertDontSee(__('messages.membership_pv_hint', ['pv' => '40']), false);
    }

    public function test_registration_credits_membership_pv_and_cash_from_plan_not_hardcoded(): void
    {
        PlanSetting::query()->where('key', 'membership_amount_usd')->update(['value' => '33']);
        PlanSetting::query()->where('key', 'membership_pv_threshold')->update(['value' => '11']);
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);
        $branch = Branch::query()->firstOrFail();

        $this->actingAs($admin)->post(route('admin.members.store'), [
            'full_name' => 'Payant',
            'username' => 'payant',
            'locale' => 'fr',
            'registration_branch_id' => $branch->id,
            'membership_amount_usd' => '99',
        ])->assertRedirect();

        $member = Member::query()->where('full_name', 'Payant')->firstOrFail();
        $this->assertSame('33.00', (string) $member->membership_amount_usd);
        $this->assertSame('11.00', (string) $member->membership_pv);
        $this->assertDatabaseHas('pv_ledger', [
            'member_id' => $member->id,
            'source_type' => 'membership',
            'pv_amount' => 11,
        ]);
        $this->assertDatabaseMissing('pv_ledger', [
            'member_id' => $member->id,
            'source_type' => 'membership',
            'pv_amount' => 40,
        ]);
        $this->assertDatabaseHas('cash_movements', [
            'branch_id' => $branch->id,
            'category' => 'membership',
            'amount_usd' => 33,
            'direction' => 'in',
        ]);
        $this->assertDatabaseMissing('cash_movements', [
            'category' => 'membership',
            'amount_usd' => 99,
        ]);
        $this->assertSame(1, PvLedger::query()->where('member_id', $member->id)->where('source_type', 'membership')->count());
        $this->assertSame(1, CashMovement::query()->where('category', 'membership')->count());
    }

    public function test_direct_membership_always_creates_cash_in_for_cashier_branch(): void
    {
        PlanSetting::query()->where('key', 'membership_amount_usd')->update(['value' => '20']);
        $branch = Branch::query()->firstOrFail();
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);
        $cashier = User::factory()->create(['role' => 'cashier', 'branch_id' => $branch->id]);

        $this->actingAs($admin)->post(route('admin.branches.codes.generate', $branch), [
            'quantity' => 1,
        ])->assertRedirect();

        $this->actingAs($cashier)->post(route('admin.members.store'), [
            'full_name' => 'Caisse Adhesion',
            'username' => 'caisseadh',
            'locale' => 'fr',
        ])->assertRedirect();

        $this->assertDatabaseHas('cash_movements', [
            'branch_id' => $branch->id,
            'category' => 'membership',
            'direction' => 'in',
            'amount_usd' => 20,
            'user_id' => $cashier->id,
        ]);
        $this->assertSame(0, RegistrationCode::query()
            ->where('branch_id', $branch->id)
            ->where('status', 'available')
            ->count());
    }

    public function test_create_form_lists_clients_eligible_for_indirect_membership(): void
    {
        PlanSetting::query()->where('key', 'membership_pv_threshold')->update(['value' => '11']);
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);
        $eligible = Client::factory()->create(['name' => 'Client Eligible', 'accumulated_pv' => 11]);
        Client::factory()->create(['name' => 'Client Bas', 'accumulated_pv' => 5]);

        $this->actingAs($admin)->get(route('admin.members.create', [
            'membership_type' => 'indirect',
            'source_client_id' => $eligible->id,
        ]))
            ->assertOk()
            ->assertSee('Client Eligible', false)
            ->assertDontSee('Client Bas', false)
            ->assertSee(__('messages.membership_indirect'), false);
    }

    public function test_indirect_membership_uses_client_pv_without_cash_and_keeps_sponsorship_at_assignment(): void
    {
        PlanSetting::query()->where('key', 'membership_pv_threshold')->update(['value' => '11']);
        PlanSetting::query()->where('key', 'sponsorship_amount_usd')->update(['value' => '21']);
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);
        $referrer = Member::factory()->create(['full_name' => 'Orienteur']);
        $client = Client::factory()->create([
            'name' => 'Client Seuil',
            'referrer_member_id' => $referrer->id,
            'accumulated_pv' => 15,
        ]);

        $this->actingAs($admin)->post(route('admin.members.store'), [
            'full_name' => 'Beneficiaire',
            'username' => 'benef',
            'locale' => 'fr',
            'membership_type' => 'indirect',
            'source_client_id' => $client->id,
        ])->assertRedirect();

        $member = Member::query()->where('full_name', 'Beneficiaire')->firstOrFail();
        $this->assertSame('indirect', $member->membership_type);
        $this->assertSame($client->id, $member->source_client_id);
        $this->assertSame('0.00', (string) $member->membership_amount_usd);
        $this->assertSame('11.00', (string) $member->membership_pv);
        $this->assertSame('4.00', (string) $client->fresh()->accumulated_pv);
        $this->assertSame($member->id, $client->fresh()->converted_member_id);
        $this->assertDatabaseHas('pv_ledger', [
            'member_id' => $member->id,
            'source_type' => 'membership',
            'pv_amount' => 11,
        ]);
        $this->assertDatabaseMissing('cash_movements', [
            'category' => 'membership',
            'description' => $member->member_code,
        ]);

        $this->actingAs($admin)->from(route('admin.members.create'))->post(route('admin.members.store'), [
            'full_name' => 'Second',
            'username' => 'second',
            'locale' => 'fr',
            'membership_type' => 'indirect',
            'source_client_id' => $client->id,
        ])->assertSessionHasErrors('source_client_id');

        $this->actingAs($admin)->post(route('admin.placements.store', $member), [
            'sponsor_id' => $referrer->id,
            'placement_parent_id' => $referrer->id,
            'placement_side' => 'left',
        ])->assertRedirect();

        $this->assertDatabaseHas('commission_ledger', [
            'member_id' => $referrer->id,
            'type' => 'sponsorship',
            'amount_usd' => 21,
        ]);
    }

    public function test_indirect_membership_rejects_client_below_threshold(): void
    {
        PlanSetting::query()->where('key', 'membership_pv_threshold')->update(['value' => '11']);
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);
        $referrer = Member::factory()->create();
        $client = Client::factory()->create([
            'referrer_member_id' => $referrer->id,
            'accumulated_pv' => 5,
        ]);

        $this->actingAs($admin)->from(route('admin.members.create'))->post(route('admin.members.store'), [
            'full_name' => 'Trop Tot',
            'username' => 'troptot',
            'locale' => 'fr',
            'membership_type' => 'indirect',
            'source_client_id' => $client->id,
        ])->assertSessionHasErrors('source_client_id');

        $this->assertDatabaseMissing('members', ['full_name' => 'Trop Tot']);
    }
}

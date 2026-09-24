<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffOrganizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_unified_login_screen(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(__('messages.login_hint'), false)
            ->assertSee(__('messages.sign_in'), false)
            ->assertSee(__('messages.remember_me'), false)
            ->assertDontSee('auth-switch', false);

        $this->get(route('member.login'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_log_in(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin1',
            'password' => 'secret123',
            'role' => 'admin',
            'branch_id' => null,
        ]);

        $this->post('/login', [
            'username' => 'admin1',
            'password' => 'secret123',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_member_can_log_in_via_unified_login(): void
    {
        $member = \App\Models\Member::factory()->create([
            'username' => 'membuni01',
            'password' => 'secret123',
            'status' => 'active',
        ]);

        $this->post(route('login'), [
            'username' => 'membuni01',
            'password' => 'secret123',
        ])->assertRedirect(route('member.dashboard'));

        $this->assertAuthenticatedAs($member, 'member');
    }

    public function test_staff_login_rejects_bad_credentials(): void
    {
        $this->from(route('login'))->post('/login', [
            'username' => 'inconnu',
            'password' => 'wrong-password',
        ])->assertRedirect(route('login'))->assertSessionHasErrors('username');
    }

    public function test_admin_can_create_a_branch(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);

        $response = $this->actingAs($admin)->post(route('admin.branches.store'), [
            'name' => 'Butembo Centre',
            'code' => 'BTE',
            'phone' => '099000000',
        ]);

        $branch = Branch::query()->where('code', 'BTE')->firstOrFail();
        $response->assertRedirect(route('admin.branches.show', $branch));
        $this->assertDatabaseHas('branches', ['code' => 'BTE']);
    }

    public function test_cashier_requires_a_branch(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);

        $this->actingAs($admin)->from(route('admin.users.create'))->post(route('admin.users.store'), [
            'name' => 'Caisse 1',
            'username' => 'caisse1',
            'password' => 'secret123',
            'role' => 'cashier',
            'locale' => 'fr',
        ])->assertSessionHasErrors('branch_id');
    }

    public function test_admin_with_null_branch_is_accepted(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);
        $this->assertNull($admin->fresh()->branch_id);

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Admin 2',
            'username' => 'admin2',
            'password' => 'secret123',
            'role' => 'admin',
            'locale' => 'fr',
        ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'username' => 'admin2',
            'role' => 'admin',
            'branch_id' => null,
        ]);
    }

    public function test_creating_a_user_writes_audit(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);
        $institution = Institution::query()->firstOrFail();
        $branch = Branch::query()->create([
            'institution_id' => $institution->id,
            'name' => 'Nord',
            'code' => 'NRD',
            'is_active' => true,
        ]);

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Caisse Nord',
            'username' => 'caisse_nord',
            'password' => 'secret123',
            'role' => 'cashier',
            'branch_id' => $branch->id,
            'locale' => 'fr',
        ])->assertRedirect(route('admin.users.index'));

        $created = User::query()->where('username', 'caisse_nord')->firstOrFail();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'created',
            'auditable_type' => User::class,
            'auditable_id' => $created->id,
        ]);
    }

    public function test_locale_change_is_visible_after_login(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null, 'locale' => 'fr']);

        $this->actingAs($admin)
            ->from(route('admin.dashboard'))
            ->get(route('locale.switch', 'sw'))
            ->assertRedirect();

        $this->get(route('admin.dashboard'))->assertSee('Dashibodi');
    }

    public function test_cashier_can_open_dashboard_but_not_users(): void
    {
        $institution = Institution::query()->firstOrFail();
        $branch = Branch::query()->create([
            'institution_id' => $institution->id,
            'name' => 'Est',
            'code' => 'EST',
            'is_active' => true,
        ]);
        $cashier = User::factory()->create([
            'role' => 'cashier',
            'branch_id' => $branch->id,
        ]);

        $this->actingAs($cashier)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($cashier)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_member_guard_is_separate_from_staff(): void
    {
        $this->assertArrayHasKey('member', config('auth.guards'));
    }
}

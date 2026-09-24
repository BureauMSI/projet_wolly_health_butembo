<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_can_create_users_for_each_role_with_correct_branch_rules(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);
        $branch = Branch::query()->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee(__('messages.role_hint_cashier'), false)
            ->assertSee(__('messages.role_hint_admin'), false);

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Admin Deux',
            'username' => 'admin2',
            'password' => 'secret123',
            'role' => 'admin',
            'branch_id' => $branch->id,
            'locale' => 'fr',
            'is_active' => 1,
        ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'username' => 'admin2',
            'role' => 'admin',
            'branch_id' => null,
        ]);

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Caissier Nord',
            'username' => 'caisse_n',
            'password' => 'secret123',
            'role' => 'cashier',
            'locale' => 'fr',
            'is_active' => 1,
        ])->assertSessionHasErrors('branch_id');

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Caissier Nord',
            'username' => 'caisse_n',
            'password' => 'secret123',
            'role' => 'cashier',
            'branch_id' => $branch->id,
            'locale' => 'fr',
            'is_active' => 1,
        ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'username' => 'caisse_n',
            'role' => 'cashier',
            'branch_id' => $branch->id,
        ]);
    }

    public function test_admin_can_edit_user_and_deactivate_non_last_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);
        $otherAdmin = User::factory()->create(['role' => 'admin', 'branch_id' => null, 'username' => 'autre_admin']);
        $branch = Branch::query()->firstOrFail();
        $cashier = User::factory()->create([
            'role' => 'cashier',
            'branch_id' => $branch->id,
            'username' => 'caisse_edit',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.edit', $cashier))
            ->assertOk()
            ->assertSee('caisse_edit', false);

        $this->actingAs($admin)->put(route('admin.users.update', $cashier), [
            'name' => 'Caissier Modifie',
            'username' => 'caisse_edit',
            'password' => '',
            'role' => 'accountant',
            'branch_id' => $branch->id,
            'locale' => 'sw',
            'is_active' => 1,
        ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $cashier->id,
            'name' => 'Caissier Modifie',
            'role' => 'accountant',
            'locale' => 'sw',
        ]);

        $this->actingAs($admin)->put(route('admin.users.update', $otherAdmin), [
            'name' => $otherAdmin->name,
            'username' => $otherAdmin->username,
            'role' => 'admin',
            'locale' => 'fr',
            'is_active' => 0,
        ])->assertRedirect(route('admin.users.index'));

        $this->assertFalse((bool) $otherAdmin->fresh()->is_active);
    }

    public function test_cannot_demote_or_deactivate_last_active_admin(): void
    {
        User::query()->where('role', 'admin')->update(['is_active' => false]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'branch_id' => null,
            'is_active' => true,
            'username' => 'seul_admin',
        ]);
        $branch = Branch::query()->firstOrFail();

        $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'username' => $admin->username,
            'role' => 'cashier',
            'branch_id' => $branch->id,
            'locale' => 'fr',
            'is_active' => 1,
        ])->assertSessionHasErrors('role');

        $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'username' => $admin->username,
            'role' => 'admin',
            'locale' => 'fr',
            'is_active' => 0,
        ])->assertSessionHasErrors('role');

        $this->assertTrue((bool) $admin->fresh()->is_active);
        $this->assertSame('admin', $admin->fresh()->role);
    }

    public function test_non_admin_cannot_manage_users(): void
    {
        $branch = Branch::query()->firstOrFail();
        $cashier = User::factory()->create(['role' => 'cashier', 'branch_id' => $branch->id]);

        $this->actingAs($cashier)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($cashier)->get(route('admin.users.create'))->assertForbidden();
    }
}

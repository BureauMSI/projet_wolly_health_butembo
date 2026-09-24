<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Member;
use App\Models\RegistrationCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationCodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_can_generate_codes_for_a_branch(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);
        $branch = Branch::query()->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.branches.codes.generate', $branch), ['quantity' => 5])
            ->assertRedirect(route('admin.branches.show', $branch))
            ->assertSessionHas('generated_codes');

        $this->assertSame(5, RegistrationCode::query()
            ->where('branch_id', $branch->id)
            ->where('status', 'available')
            ->count());
    }

    public function test_cashier_only_sees_members_of_own_branch(): void
    {
        $branchA = Branch::query()->firstOrFail();
        $branchB = Branch::query()->create([
            'institution_id' => $branchA->institution_id,
            'name' => 'Autre',
            'code' => 'AUT',
            'is_active' => true,
        ]);
        $mine = Member::factory()->create([
            'full_name' => 'Membre Local',
            'registration_branch_id' => $branchA->id,
        ]);
        $other = Member::factory()->create([
            'full_name' => 'Membre Autre',
            'registration_branch_id' => $branchB->id,
        ]);
        $cashier = User::factory()->create(['role' => 'cashier', 'branch_id' => $branchA->id]);

        $this->actingAs($cashier, 'web')
            ->get(route('admin.members.index'))
            ->assertOk()
            ->assertSee('Membre Local', false)
            ->assertDontSee('Membre Autre', false);

        $this->actingAs($cashier, 'web')
            ->get(route('admin.members.show', $other))
            ->assertForbidden();

        $this->actingAs($cashier, 'web')
            ->get(route('admin.members.show', $mine))
            ->assertOk();
    }

    public function test_cashier_auto_consumes_next_code_without_manual_entry(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);
        $branch = Branch::query()->firstOrFail();
        $cashier = User::factory()->create(['role' => 'cashier', 'branch_id' => $branch->id]);

        $this->actingAs($admin)->post(route('admin.branches.codes.generate', $branch), [
            'quantity' => 2,
        ])->assertRedirect();

        $firstCode = RegistrationCode::query()
            ->where('branch_id', $branch->id)
            ->where('status', 'available')
            ->orderBy('id')
            ->value('code');

        $this->actingAs($cashier, 'web')
            ->get(route('admin.members.create'))
            ->assertOk()
            ->assertSee(__('messages.registration_code_auto_help'), false)
            ->assertDontSee('name="registration_code"', false);

        $this->actingAs($cashier, 'web')->post(route('admin.members.store'), [
            'full_name' => 'Auto Code',
            'username' => 'autocode',
            'locale' => 'fr',
        ])->assertRedirect();

        $member = Member::query()->where('full_name', 'Auto Code')->firstOrFail();
        $this->assertSame($branch->id, $member->registration_branch_id);
        $this->assertDatabaseHas('registration_codes', [
            'code' => $firstCode,
            'status' => 'used',
            'used_by_member_id' => $member->id,
        ]);
        $this->assertSame(1, RegistrationCode::query()
            ->where('branch_id', $branch->id)
            ->where('status', 'available')
            ->count());
    }

    public function test_cashier_cannot_register_when_codes_exhausted(): void
    {
        $branch = Branch::query()->firstOrFail();
        $cashier = User::factory()->create(['role' => 'cashier', 'branch_id' => $branch->id]);

        $this->actingAs($cashier, 'web')
            ->from(route('admin.members.create'))
            ->post(route('admin.members.store'), [
                'full_name' => 'Sans Quota',
                'username' => 'sansquota',
                'locale' => 'fr',
            ])
            ->assertRedirect(route('admin.members.create'))
            ->assertSessionHasErrors('registration_code');
    }

    public function test_admin_can_top_up_codes_later(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);
        $branch = Branch::query()->firstOrFail();

        $this->actingAs($admin)->post(route('admin.branches.codes.generate', $branch), [
            'quantity' => 3,
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('admin.branches.codes.generate', $branch), [
            'quantity' => 2,
        ])->assertRedirect();

        $this->assertSame(5, RegistrationCode::query()
            ->where('branch_id', $branch->id)
            ->where('status', 'available')
            ->count());
    }

    public function test_admin_can_register_without_code(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);

        $this->actingAs($admin)->post(route('admin.members.store'), [
            'full_name' => 'Admin Direct',
            'username' => 'admindirect',
            'locale' => 'fr',
        ])->assertRedirect();

        $this->assertDatabaseHas('members', ['full_name' => 'Admin Direct']);
    }
}

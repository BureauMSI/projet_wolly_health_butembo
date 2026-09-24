<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashMovement;
use App\Models\CashOperationType;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashOperationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_other_income_is_recorded_as_cash_in(): void
    {
        $cashier = $this->cashier();

        $this->actingAs($cashier)->post(route('admin.operations.store'), [
            'direction' => 'in',
            'category' => 'donation',
            'amount' => 50,
            'currency_code' => 'USD',
            'description' => 'Don local',
        ])->assertRedirect(route('admin.operations.index'));

        $this->assertDatabaseHas('cash_movements', [
            'direction' => 'in',
            'category' => 'donation',
            'amount_usd' => 50,
            'operation_type_id' => CashOperationType::idFor('donation'),
        ]);
    }

    public function test_charge_is_recorded_as_cash_out(): void
    {
        $cashier = $this->cashier();

        $this->actingAs($cashier)->post(route('admin.operations.store'), [
            'direction' => 'out',
            'category' => 'charge',
            'amount' => 20,
            'currency_code' => 'USD',
        ])->assertRedirect(route('admin.operations.index'));

        $this->assertDatabaseHas('cash_movements', [
            'direction' => 'out',
            'category' => 'charge',
            'amount_usd' => 20,
        ]);

        $this->actingAs($cashier)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(__('messages.today_cash_out'))
            ->assertSee('20.00');
    }

    public function test_sale_category_cannot_be_created_from_operations(): void
    {
        $cashier = $this->cashier();

        $this->actingAs($cashier)
            ->from(route('admin.operations.create'))
            ->post(route('admin.operations.store'), [
                'direction' => 'in',
                'category' => 'sale',
                'amount' => 10,
                'currency_code' => 'USD',
            ])
            ->assertSessionHasErrors('category');

        $this->assertSame(0, CashMovement::query()->where('category', 'sale')->count());
    }

    public function test_income_type_cannot_be_used_as_cash_out(): void
    {
        $cashier = $this->cashier();

        $this->actingAs($cashier)
            ->from(route('admin.operations.create'))
            ->post(route('admin.operations.store'), [
                'direction' => 'out',
                'category' => 'donation',
                'amount' => 10,
                'currency_code' => 'USD',
            ])
            ->assertSessionHasErrors('category');
    }

    public function test_admin_can_add_a_custom_operation_type(): void
    {
        $admin = User::query()->where('username', 'admin')->firstOrFail();
        $cashier = $this->cashier();

        $this->actingAs($cashier)->post(route('admin.operations.types.store'), [
            'label' => 'Frais bancaires',
            'direction' => 'out',
        ])->assertForbidden();

        $this->actingAs($admin)->post(route('admin.operations.types.store'), [
            'label' => 'Frais bancaires',
            'direction' => 'out',
        ])->assertRedirect();

        $this->assertDatabaseHas('cash_operation_types', [
            'label' => 'Frais bancaires',
            'direction' => 'out',
            'is_system' => 0,
        ]);

        $type = CashOperationType::query()->where('label', 'Frais bancaires')->firstOrFail();

        $this->actingAs($cashier)->post(route('admin.operations.store'), [
            'direction' => 'out',
            'category' => $type->code,
            'amount' => 8,
            'currency_code' => 'USD',
        ])->assertRedirect();

        $this->assertDatabaseHas('cash_movements', [
            'category' => $type->code,
            'direction' => 'out',
            'amount_usd' => 8,
        ]);
    }

    private function cashier(): User
    {
        $institution = Institution::query()->firstOrFail();
        $branch = Branch::query()->where('code', 'BTB')->first() ?? Branch::query()->create([
            'institution_id' => $institution->id,
            'name' => 'Test',
            'code' => 'TST',
            'is_active' => true,
        ]);

        return User::factory()->create([
            'role' => 'cashier',
            'branch_id' => $branch->id,
        ]);
    }
}

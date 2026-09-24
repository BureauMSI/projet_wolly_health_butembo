<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashMovement;
use App\Models\Institution;
use App\Models\Member;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThermalTicketTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_sale_invoice_and_receipt_print_on_58_and_80(): void
    {
        [$cashier, $member, $product] = $this->cashierMemberAndProduct();

        $this->actingAs($cashier)->post(route('admin.sales.store'), [
            'buyer_type' => 'member',
            'benefit_mode' => 'pv',
            'member_id' => $member->id,
            'currency_code' => 'USD',
            'items' => [
                ['product_id' => $product->id, 'packing' => 'tablet', 'quantity' => 2],
            ],
        ])->assertRedirect();

        $sale = Sale::query()->firstOrFail();

        $this->actingAs($cashier)
            ->get(route('admin.sales.invoice80', $sale))
            ->assertOk()
            ->assertSee(__('messages.doc_invoice'))
            ->assertSee('Holy Health')
            ->assertSee('Butembo')
            ->assertSee($sale->number)
            ->assertSee($member->full_name)
            ->assertSee($product->name)
            ->assertSee('10.00');

        $this->actingAs($cashier)
            ->get(route('admin.sales.receipt58', $sale))
            ->assertOk()
            ->assertSee(__('messages.doc_receipt'))
            ->assertSee(__('messages.ticket_paid'))
            ->assertSee($sale->number)
            ->assertSee('Holy Health');
    }

    public function test_cash_in_and_out_vouchers_print_thermally(): void
    {
        $cashier = $this->cashier();

        $this->actingAs($cashier)->post(route('admin.operations.store'), [
            'direction' => 'in',
            'category' => 'donation',
            'amount' => 50,
            'currency_code' => 'USD',
            'description' => 'Don local',
        ])->assertRedirect();

        $this->actingAs($cashier)->post(route('admin.operations.store'), [
            'direction' => 'out',
            'category' => 'charge',
            'amount' => 12,
            'currency_code' => 'USD',
        ])->assertRedirect();

        $in = CashMovement::query()->where('direction', 'in')->where('category', 'donation')->firstOrFail();
        $out = CashMovement::query()->where('direction', 'out')->where('category', 'charge')->firstOrFail();

        $this->actingAs($cashier)
            ->get(route('admin.cash.print80', $in))
            ->assertOk()
            ->assertSee(__('messages.doc_voucher_in'))
            ->assertSee('Holy Health')
            ->assertSee('Don local')
            ->assertSee('50.00');

        $this->actingAs($cashier)
            ->get(route('admin.cash.print58', $out))
            ->assertOk()
            ->assertSee(__('messages.doc_voucher_out'))
            ->assertSee('12.00')
            ->assertSee('Holy Health');
    }

    /**
     * @return array{0: User, 1: Member, 2: Product}
     */
    private function cashierMemberAndProduct(): array
    {
        $cashier = $this->cashier();
        $member = Member::factory()->create();
        $product = Product::factory()->create([
            'unit_price_usd' => 5,
            'pv_per_tablet' => 2,
            'is_active' => true,
        ]);

        return [$cashier, $member, $product];
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

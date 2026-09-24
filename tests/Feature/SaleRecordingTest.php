<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Institution;
use App\Models\Member;
use App\Models\PlanSetting;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleRecordingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_sale_succeeds_without_stock_and_credits_member_pv(): void
    {
        [$cashier, $member, $product] = $this->cashierMemberAndProduct();

        $this->actingAs($cashier)->post(route('admin.sales.store'), [
            'buyer_type' => 'member',
            'benefit_mode' => 'pv',
            'member_id' => $member->id,
            'currency_code' => 'USD',
            'items' => [
                ['product_id' => $product->id, 'packing' => 'tablet', 'quantity' => 3],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('sales', [
            'member_id' => $member->id,
            'total_usd' => 15,
        ]);
        $this->assertDatabaseHas('pv_ledger', [
            'member_id' => $member->id,
            'source_type' => 'own_purchase',
            'pv_amount' => 6,
        ]);
        $this->assertDatabaseHas('cash_movements', [
            'direction' => 'in',
            'category' => 'sale',
            'amount_usd' => 15,
        ]);
    }

    public function test_updating_a_sale_updates_cash_and_pv(): void
    {
        [$cashier, $member, $product] = $this->cashierMemberAndProduct();

        $this->actingAs($cashier)->post(route('admin.sales.store'), [
            'buyer_type' => 'member',
            'benefit_mode' => 'pv',
            'member_id' => $member->id,
            'currency_code' => 'USD',
            'items' => [
                ['product_id' => $product->id, 'packing' => 'tablet', 'quantity' => 3],
            ],
        ])->assertRedirect();

        $sale = \App\Models\Sale::query()->firstOrFail();

        $this->actingAs($cashier)->put(route('admin.sales.update', $sale), [
            'buyer_type' => 'member',
            'benefit_mode' => 'pv',
            'member_id' => $member->id,
            'currency_code' => 'USD',
            'items' => [
                ['product_id' => $product->id, 'packing' => 'tablet', 'quantity' => 1],
            ],
        ])->assertRedirect(route('admin.sales.show', $sale));

        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'total_usd' => 5,
        ]);
        $this->assertDatabaseHas('cash_movements', [
            'sale_id' => $sale->id,
            'amount_usd' => 5,
            'category' => 'sale',
        ]);
        $this->assertDatabaseHas('pv_ledger', [
            'sale_id' => $sale->id,
            'pv_amount' => 2,
        ]);
        $this->assertSame(1, \App\Models\CashMovement::query()->where('sale_id', $sale->id)->count());
    }

    public function test_deleting_a_sale_removes_cash_in(): void
    {
        [$cashier, $member, $product] = $this->cashierMemberAndProduct();

        $this->actingAs($cashier)->post(route('admin.sales.store'), [
            'buyer_type' => 'member',
            'benefit_mode' => 'pv',
            'member_id' => $member->id,
            'currency_code' => 'USD',
            'items' => [
                ['product_id' => $product->id, 'packing' => 'tablet', 'quantity' => 3],
            ],
        ])->assertRedirect();

        $sale = \App\Models\Sale::query()->firstOrFail();

        $this->actingAs($cashier)
            ->delete(route('admin.sales.destroy', $sale))
            ->assertRedirect(route('admin.sales.index'));

        $this->assertSoftDeleted('sales', ['id' => $sale->id]);
        $this->assertSame(0, \App\Models\CashMovement::query()->where('sale_id', $sale->id)->count());
        $this->assertSame(0, \App\Models\PvLedger::query()->where('sale_id', $sale->id)->count());
    }

    public function test_sale_linked_cash_cannot_be_edited_directly(): void
    {
        [$cashier, $member, $product] = $this->cashierMemberAndProduct();

        $this->actingAs($cashier)->post(route('admin.sales.store'), [
            'buyer_type' => 'member',
            'benefit_mode' => 'pv',
            'member_id' => $member->id,
            'currency_code' => 'USD',
            'items' => [
                ['product_id' => $product->id, 'packing' => 'tablet', 'quantity' => 1],
            ],
        ]);

        $cash = \App\Models\CashMovement::query()->where('category', 'sale')->firstOrFail();

        $this->actingAs($cashier)
            ->get(route('admin.cash.edit', $cash))
            ->assertForbidden();
    }

    public function test_client_can_be_updated(): void
    {
        [$cashier, $member] = $this->cashierMemberAndProduct();
        $client = Client::factory()->create([
            'name' => 'Ancien',
            'referrer_member_id' => $member->id,
        ]);

        $this->actingAs($cashier)->put(route('admin.clients.update', $client), [
            'name' => 'Nouveau Client',
            'phone' => '099111222',
            'referrer_member_id' => $member->id,
        ])->assertRedirect(route('admin.clients.index'));

        $this->assertSame('Nouveau Client', $client->fresh()->name);
    }

    public function test_client_sale_uses_client_price_not_member_price(): void
    {
        [$cashier, $member, $product] = $this->cashierMemberAndProduct();
        $product->update([
            'unit_price_usd' => 10,
            'member_unit_price_usd' => 8,
        ]);
        $client = Client::factory()->create(['referrer_member_id' => $member->id]);

        $this->actingAs($cashier)->post(route('admin.sales.store'), [
            'buyer_type' => 'client',
            'benefit_mode' => 'pv',
            'client_id' => $client->id,
            'currency_code' => 'USD',
            'items' => [
                ['product_id' => $product->id, 'packing' => 'tablet', 'quantity' => 2],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('sales', [
            'client_id' => $client->id,
            'discount_usd' => 0,
            'total_usd' => 20,
        ]);
        $this->assertDatabaseHas('pv_ledger', [
            'member_id' => $member->id,
            'source_type' => 'referred_client_purchase',
            'pv_amount' => 4,
        ]);
        $this->assertSame(4.0, (float) $client->fresh()->accumulated_pv);
    }

    public function test_member_sale_uses_member_price(): void
    {
        [$cashier, $member, $product] = $this->cashierMemberAndProduct();
        $product->update([
            'unit_price_usd' => 10,
            'member_unit_price_usd' => 8,
        ]);

        $this->actingAs($cashier)->post(route('admin.sales.store'), [
            'buyer_type' => 'member',
            'benefit_mode' => 'pv',
            'member_id' => $member->id,
            'currency_code' => 'USD',
            'items' => [
                ['product_id' => $product->id, 'packing' => 'tablet', 'quantity' => 2],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('sales', [
            'member_id' => $member->id,
            'discount_usd' => 0,
            'total_usd' => 16,
        ]);
    }

    public function test_sale_form_shows_benefit_mode_filter(): void
    {
        [$cashier] = $this->cashierMemberAndProduct();

        $this->actingAs($cashier)
            ->get(route('admin.sales.create'))
            ->assertOk()
            ->assertSee(__('messages.sale_benefit_filter_hint'), false)
            ->assertSee(__('messages.benefit_pv'), false)
            ->assertSee(__('messages.benefit_percent'), false);
    }

    public function test_client_threshold_alert_uses_plan_setting(): void
    {
        PlanSetting::query()->where('key', 'membership_pv_threshold')->update(['value' => '5']);
        [$cashier, $member, $product] = $this->cashierMemberAndProduct();
        $client = Client::factory()->create(['referrer_member_id' => $member->id]);

        $this->actingAs($cashier)->post(route('admin.sales.store'), [
            'buyer_type' => 'client',
            'benefit_mode' => 'pv',
            'client_id' => $client->id,
            'currency_code' => 'USD',
            'items' => [
                ['product_id' => $product->id, 'packing' => 'tablet', 'quantity' => 3],
            ],
        ])->assertRedirect();

        $this->assertNotNull($client->fresh()->threshold_alerted_at);
        $this->assertDatabaseHas('whatsapp_outbox', [
            'template_key' => 'membership-threshold',
        ]);
    }

    public function test_admin_can_define_product_commission_percent(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);

        $this->actingAs($admin)
            ->get(route('admin.products.create'))
            ->assertOk()
            ->assertSee(__('messages.client_price'), false)
            ->assertSee(__('messages.member_price'), false)
            ->assertSee(__('messages.member_unit_price_usd'), false)
            ->assertSee(__('messages.product_benefit_type'), false)
            ->assertSee(__('messages.product_type_pv'), false)
            ->assertSee(__('messages.product_type_percent'), false)
            ->assertSee(__('messages.commission_percent'), false)
            ->assertSee(__('messages.product_code_auto'), false)
            ->assertDontSee('name="code"', false)
            ->assertDontSee(__('messages.product_percent_hint'), false)
            ->assertDontSee(__('messages.client_price_fields_hint'), false)
            ->assertDontSee(__('messages.member_price_hint'), false);

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'name' => 'Gel percent',
            'benefit_type' => 'percent',
            'unit_price_usd' => 8,
            'member_unit_price_usd' => 6,
            'box_price_usd' => 70,
            'member_box_price_usd' => 55,
            'commission_percent' => 12.5,
            'is_active' => 1,
        ])->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', [
            'name' => 'Gel percent',
            'benefit_type' => 'percent',
            'commission_percent' => 12.5,
            'pv_per_tablet' => 0,
            'box_pv' => 0,
            'member_unit_price_usd' => 6,
        ]);
        $this->assertTrue(
            Product::query()->where('name', 'Gel percent')->where('code', 'like', 'PR-%')->exists()
        );
    }

    public function test_admin_can_define_pv_product_type(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'name' => 'Gel PV',
            'benefit_type' => 'pv',
            'unit_price_usd' => 8,
            'member_unit_price_usd' => 6,
            'pv_per_tablet' => 3,
            'box_price_usd' => 70,
            'member_box_price_usd' => 55,
            'box_pv' => 25,
            'is_active' => 1,
        ])->assertRedirect(route('admin.products.index'));

        $product = Product::query()->where('name', 'Gel PV')->firstOrFail();
        $this->assertMatchesRegularExpression('/^PR-\d{5}$/', $product->code);
        $this->assertDatabaseHas('products', [
            'code' => $product->code,
            'benefit_type' => 'pv',
            'pv_per_tablet' => 3,
            'box_pv' => 25,
            'commission_percent' => 0,
        ]);
    }

    public function test_member_percent_sale_credits_sponsor_without_pv(): void
    {
        [$cashier, $member, $product] = $this->cashierMemberAndProduct();
        $sponsor = Member::factory()->create();
        $member->update(['sponsor_id' => $sponsor->id]);
        $product->update([
            'benefit_type' => 'percent',
            'unit_price_usd' => 10,
            'member_unit_price_usd' => 8,
            'pv_per_tablet' => 0,
            'box_pv' => 0,
            'commission_percent' => 10,
        ]);

        $this->actingAs($cashier)->post(route('admin.sales.store'), [
            'buyer_type' => 'member',
            'benefit_mode' => 'percent',
            'member_id' => $member->id,
            'currency_code' => 'USD',
            'items' => [
                ['product_id' => $product->id, 'packing' => 'tablet', 'quantity' => 3],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('sales', [
            'member_id' => $member->id,
            'total_usd' => 24,
        ]);
        $this->assertDatabaseHas('commission_ledger', [
            'member_id' => $sponsor->id,
            'related_member_id' => $member->id,
            'type' => 'product_percent',
            'amount_usd' => 3,
        ]);
        $this->assertSame(0, \App\Models\PvLedger::query()->count());

        $this->actingAs($sponsor, 'member')
            ->get(route('member.dashboard'))
            ->assertOk()
            ->assertSee('3.00', false)
            ->assertSee(__('messages.product_percent'), false);
    }

    public function test_member_sale_without_sponsor_creates_no_product_percent(): void
    {
        [$cashier, $member, $product] = $this->cashierMemberAndProduct();
        $product->update([
            'benefit_type' => 'percent',
            'pv_per_tablet' => 0,
            'box_pv' => 0,
            'commission_percent' => 10,
        ]);

        $this->actingAs($cashier)->post(route('admin.sales.store'), [
            'buyer_type' => 'member',
            'benefit_mode' => 'percent',
            'member_id' => $member->id,
            'currency_code' => 'USD',
            'items' => [
                ['product_id' => $product->id, 'packing' => 'tablet', 'quantity' => 3],
            ],
        ])->assertRedirect();

        $this->assertSame(0, \App\Models\CommissionLedger::query()->where('type', 'product_percent')->count());
        $this->assertSame(0, \App\Models\PvLedger::query()->count());
    }

    public function test_percent_mode_rejects_pv_only_product(): void
    {
        [$cashier, $member, $product] = $this->cashierMemberAndProduct();
        $product->update(['benefit_type' => 'pv', 'commission_percent' => 0, 'pv_per_tablet' => 2]);

        $this->actingAs($cashier)->from(route('admin.sales.create'))->post(route('admin.sales.store'), [
            'buyer_type' => 'member',
            'benefit_mode' => 'percent',
            'member_id' => $member->id,
            'currency_code' => 'USD',
            'items' => [
                ['product_id' => $product->id, 'packing' => 'tablet', 'quantity' => 1],
            ],
        ])->assertRedirect(route('admin.sales.create'))
            ->assertSessionHasErrors('items');
    }

    public function test_client_pv_sale_does_not_credit_product_percent(): void
    {
        [$cashier, $member, $product] = $this->cashierMemberAndProduct();
        $product->update(['benefit_type' => 'pv', 'commission_percent' => 0]);
        $client = Client::factory()->create(['referrer_member_id' => $member->id]);

        $this->actingAs($cashier)->post(route('admin.sales.store'), [
            'buyer_type' => 'client',
            'client_id' => $client->id,
            'benefit_mode' => 'pv',
            'currency_code' => 'USD',
            'items' => [
                ['product_id' => $product->id, 'packing' => 'tablet', 'quantity' => 2],
            ],
        ])->assertRedirect();

        $this->assertSame(0, \App\Models\CommissionLedger::query()->where('type', 'product_percent')->count());
        $this->assertDatabaseHas('pv_ledger', [
            'member_id' => $member->id,
            'source_type' => 'referred_client_purchase',
            'pv_amount' => 4,
        ]);
        $this->assertSame(4.0, (float) $client->fresh()->accumulated_pv);

        $this->actingAs($member, 'member')
            ->get(route('member.dashboard'))
            ->assertOk()
            ->assertSee($client->name, false)
            ->assertSee(__('messages.referred_clients'), false);
    }

    public function test_client_percent_sale_credits_referrer_and_skips_pv(): void
    {
        [$cashier, $member, $product] = $this->cashierMemberAndProduct();
        $product->update([
            'benefit_type' => 'percent',
            'pv_per_tablet' => 0,
            'box_pv' => 0,
            'commission_percent' => 10,
        ]);
        $client = Client::factory()->create([
            'referrer_member_id' => $member->id,
            'accumulated_pv' => 0,
        ]);

        $this->actingAs($cashier)->post(route('admin.sales.store'), [
            'buyer_type' => 'client',
            'client_id' => $client->id,
            'benefit_mode' => 'percent',
            'currency_code' => 'USD',
            'items' => [
                ['product_id' => $product->id, 'packing' => 'tablet', 'quantity' => 2],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('commission_ledger', [
            'member_id' => $member->id,
            'type' => 'product_percent',
            'amount_usd' => 1,
        ]);
        $this->assertSame(0, \App\Models\PvLedger::query()->count());
        $this->assertSame(0.0, (float) $client->fresh()->accumulated_pv);

        $this->actingAs($member, 'member')
            ->get(route('member.dashboard'))
            ->assertOk()
            ->assertSee('1.00', false)
            ->assertSee(__('messages.product_percent'), false);
    }

    /**
     * @return array{0: User, 1: Member, 2: Product}
     */
    private function cashierMemberAndProduct(): array
    {
        $institution = Institution::query()->firstOrFail();
        $branch = Branch::query()->where('code', 'BTB')->first() ?? Branch::query()->create([
            'institution_id' => $institution->id,
            'name' => 'Test',
            'code' => 'TST',
            'is_active' => true,
        ]);
        $cashier = User::factory()->create([
            'role' => 'cashier',
            'branch_id' => $branch->id,
        ]);
        $member = Member::factory()->create();
        $product = Product::factory()->create([
            'unit_price_usd' => 5,
            'member_unit_price_usd' => 5,
            'pv_per_tablet' => 2,
            'is_active' => true,
        ]);

        return [$cashier, $member, $product];
    }
}

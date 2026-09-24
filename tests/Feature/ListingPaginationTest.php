<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Product;
use App\Models\User;
use App\Support\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListingPaginationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->app->setLocale('fr');
    }

    public function test_product_index_shows_ten_rows_then_next_page(): void
    {
        $this->assertSame(10, Listing::PER_PAGE);

        foreach (range(0, 10) as $index) {
            Product::factory()->create([
                'name' => sprintf('AAA-Liste-%02d', $index),
            ]);
        }

        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);

        $this->actingAs($admin)
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('AAA-Liste-00', false)
            ->assertDontSee('AAA-Liste-10', false)
            ->assertSee(__('pagination.next'), false);

        $this->actingAs($admin)
            ->get(route('admin.products.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('AAA-Liste-10', false);
    }

    public function test_member_index_hides_eleventh_row_on_first_page(): void
    {
        foreach (range(0, 10) as $index) {
            Member::factory()->create([
                'full_name' => 'PaginateMarker-'.$index,
            ]);
        }

        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);

        $this->actingAs($admin)
            ->get(route('admin.members.index'))
            ->assertOk()
            ->assertSee('PaginateMarker-0', false)
            ->assertDontSee('PaginateMarker-10', false)
            ->assertSee(__('pagination.next'), false);
    }
}

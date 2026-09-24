<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchableSelectPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_key_forms_with_entity_selects_still_load(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);
        Member::factory()->create(['full_name' => 'Racine Select']);
        Member::factory()->create([
            'full_name' => 'En Attente Select',
            'placement_parent_id' => null,
            'placement_side' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.sales.create'))
            ->assertOk()
            ->assertSee('name="member_id"', false)
            ->assertSee('data-searchable="1"', false);

        $this->actingAs($admin)
            ->get(route('admin.placements.index'))
            ->assertOk()
            ->assertSee('En Attente Select', false)
            ->assertSee('name="sponsor_id"', false)
            ->assertSee('data-searchable="1"', false);

        $this->actingAs($admin)
            ->get(route('admin.clients.create'))
            ->assertOk()
            ->assertSee('name="referrer_member_id"', false)
            ->assertSee('data-searchable="1"', false);
    }
}

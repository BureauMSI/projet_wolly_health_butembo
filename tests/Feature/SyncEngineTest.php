<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Member;
use App\Models\Sale;
use App\Models\User;
use App\Services\SyncEngine;
use App\Services\SyncOutbox;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class SyncEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_run_is_noop_when_remote_url_empty(): void
    {
        config(['sync.remote_url' => '']);

        app(SyncOutbox::class)->enqueue('product', (string) Str::uuid(), 'create', ['name' => 'X']);

        $result = app(SyncEngine::class)->run();

        $this->assertSame('local', $result['mode']);
        $this->assertSame(0, $result['pushed']);
        $this->assertDatabaseHas('sync_outbox', [
            'entity_type' => 'product',
            'synced_at' => null,
        ]);
    }

    public function test_admin_sync_page_shows_local_mode(): void
    {
        config(['sync.remote_url' => '']);
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);

        $this->actingAs($admin)
            ->get(route('admin.sync.index'))
            ->assertOk()
            ->assertSee(__('messages.sync_mode_local'), false);

        $this->actingAs($admin)
            ->post(route('admin.sync.run'))
            ->assertRedirect(route('admin.sync.index'))
            ->assertSessionHas('status');
    }

    public function test_cashier_cannot_access_sync_page(): void
    {
        $branch = Branch::query()->where('code', 'BTB')->firstOrFail();
        $cashier = User::factory()->create(['role' => 'cashier', 'branch_id' => $branch->id]);

        $this->actingAs($cashier)
            ->get(route('admin.sync.index'))
            ->assertForbidden();
    }

    public function test_ingest_accepts_member_by_uuid_idempotently(): void
    {
        $uuid = (string) Str::uuid();
        $engine = app(SyncEngine::class);

        $first = $engine->ingest([
            'entity_type' => 'member',
            'entity_uuid' => $uuid,
            'operation' => 'create',
            'payload' => [
                'member_code' => 'HH-90001',
                'full_name' => 'Sync Member',
                'username' => 'syncuserabcd',
                'password' => 'secret123',
                'locale' => 'fr',
                'status' => 'active',
                'joined_at' => now()->toDateTimeString(),
            ],
            'origin_device_id' => 'branch-a',
        ]);

        $second = $engine->ingest([
            'entity_type' => 'member',
            'entity_uuid' => $uuid,
            'operation' => 'update',
            'payload' => [
                'member_code' => 'HH-90001',
                'full_name' => 'Sync Member Updated',
                'username' => 'syncuserabcd',
                'locale' => 'fr',
                'status' => 'active',
            ],
            'origin_device_id' => 'branch-a',
        ]);

        $this->assertSame('accepted', $first['status']);
        $this->assertSame('accepted', $second['status']);
        $this->assertSame(1, Member::query()->where('uuid', $uuid)->count());
        $this->assertSame('Sync Member Updated', Member::query()->where('uuid', $uuid)->value('full_name'));
    }

    public function test_placement_conflict_rejects_second_leg_occupant(): void
    {
        $parent = Member::factory()->create(['username' => 'parentxxxx']);
        $first = Member::factory()->create([
            'username' => 'firstyyyy',
            'placement_parent_id' => $parent->id,
            'placement_side' => 'left',
            'sponsor_id' => $parent->id,
        ]);

        $challengerUuid = (string) Str::uuid();
        $result = app(SyncEngine::class)->ingest([
            'entity_type' => 'member',
            'entity_uuid' => $challengerUuid,
            'operation' => 'create',
            'payload' => [
                'member_code' => 'HH-90002',
                'full_name' => 'Challenger',
                'username' => 'challenger1',
                'password' => 'secret123',
                'locale' => 'fr',
                'status' => 'active',
                'joined_at' => now()->toDateTimeString(),
                'placement_parent_uuid' => $parent->uuid,
                'placement_side' => 'left',
                'sponsor_uuid' => $parent->uuid,
            ],
            'origin_device_id' => 'branch-b',
        ]);

        $this->assertSame('rejected', $result['status']);
        $this->assertStringContainsString($first->username, $result['message']);
        $this->assertSame(0, Member::query()->where('uuid', $challengerUuid)->count());
        $this->assertDatabaseHas('members', [
            'id' => $first->id,
            'placement_parent_id' => $parent->id,
            'placement_side' => 'left',
        ]);
    }

    public function test_push_marks_outbox_synced_and_confirms_sale(): void
    {
        config([
            'sync.remote_url' => 'https://remote.test',
            'sync.token' => 'secret-token',
            'sync.origin_device_id' => 'office-local',
        ]);

        $branch = Branch::query()->where('code', 'BTB')->firstOrFail();
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => null]);

        $saleUuid = (string) Str::uuid();
        Sale::query()->create([
            'uuid' => $saleUuid,
            'number' => 'S-TEST-1',
            'branch_id' => $branch->id,
            'user_id' => $admin->id,
            'buyer_type' => 'member',
            'currency_code' => 'USD',
            'rate_to_usd' => 1,
            'subtotal_usd' => 10,
            'discount_usd' => 0,
            'promo_usd' => 0,
            'total_usd' => 10,
            'sold_at' => now(),
            'sync_status' => 'pending',
        ]);

        $entry = app(SyncOutbox::class)->enqueue('sale', $saleUuid, 'create', [
            'uuid' => $saleUuid,
            'number' => 'S-TEST-1',
            'sync_status' => 'pending',
        ], 'office-local');

        Http::fake([
            'https://remote.test/api/sync/push' => Http::response([
                'results' => [['status' => 'accepted']],
            ], 200),
            'https://remote.test/api/sync/pull*' => Http::response([
                'entries' => [],
            ], 200),
        ]);

        $result = app(SyncEngine::class)->run();

        $this->assertSame('remote', $result['mode']);
        $this->assertSame(1, $result['pushed']);
        $this->assertSame(1, $result['confirmed']);
        $this->assertNotNull($entry->fresh()->synced_at);
        $this->assertSame('confirmed', Sale::query()->where('uuid', $saleUuid)->value('sync_status'));
    }

    public function test_api_push_requires_token_and_applies_entries(): void
    {
        config(['sync.token' => 'office-secret']);

        $uuid = (string) Str::uuid();

        $this->postJson('/api/sync/push', [
            'entries' => [[
                'entity_type' => 'member',
                'entity_uuid' => $uuid,
                'operation' => 'create',
                'payload' => [
                    'member_code' => 'HH-90003',
                    'full_name' => 'Api Member',
                    'username' => 'apimemberxx',
                    'password' => 'secret123',
                    'locale' => 'fr',
                    'status' => 'active',
                    'joined_at' => now()->toDateTimeString(),
                ],
            ]],
        ])->assertUnauthorized();

        $this->withHeader('X-Sync-Token', 'office-secret')
            ->postJson('/api/sync/push', [
                'entries' => [[
                    'entity_type' => 'member',
                    'entity_uuid' => $uuid,
                    'operation' => 'create',
                    'payload' => [
                        'member_code' => 'HH-90003',
                        'full_name' => 'Api Member',
                        'username' => 'apimemberxx',
                        'password' => 'secret123',
                        'locale' => 'fr',
                        'status' => 'active',
                        'joined_at' => now()->toDateTimeString(),
                    ],
                ]],
            ])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'accepted');

        $this->assertDatabaseHas('members', ['uuid' => $uuid, 'username' => 'apimemberxx']);
    }

    public function test_pwa_manifest_and_service_worker_are_public(): void
    {
        $this->get('/manifest.webmanifest')
            ->assertOk()
            ->assertSee('Holy Health', false)
            ->assertSee('standalone', false);

        $this->get('/sw.js')
            ->assertOk()
            ->assertSee('holy-health-v1', false);
    }
}

<?php

namespace Tests\Feature;

use App\Services\SyncOutbox;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncOutboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_enqueues_a_sync_outbox_row(): void
    {
        $entry = app(SyncOutbox::class)->enqueue(
            'institution',
            '11111111-1111-1111-1111-111111111111',
            'create',
            ['name' => 'Holy Health'],
        );

        $this->assertDatabaseHas('sync_outbox', [
            'id' => $entry->id,
            'entity_type' => 'institution',
            'entity_uuid' => '11111111-1111-1111-1111-111111111111',
            'operation' => 'create',
        ]);
    }
}

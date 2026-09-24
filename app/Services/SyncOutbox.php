<?php

namespace App\Services;

use App\Models\SyncOutboxEntry;

class SyncOutbox
{
    public function enqueue(
        string $entityType,
        string $entityUuid,
        string $operation,
        array $payload = [],
        ?string $originDeviceId = null,
    ): SyncOutboxEntry {
        return SyncOutboxEntry::query()->create([
            'entity_type' => $entityType,
            'entity_uuid' => $entityUuid,
            'operation' => $operation,
            'payload_json' => $payload,
            'origin_device_id' => $originDeviceId,
            'queued_at' => now(),
        ]);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncOutboxEntry extends Model
{
    public $timestamps = false;

    protected $table = 'sync_outbox';

    protected $fillable = [
        'entity_type',
        'entity_uuid',
        'operation',
        'payload_json',
        'origin_device_id',
        'queued_at',
        'synced_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'payload_json' => 'array',
            'queued_at' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }
}

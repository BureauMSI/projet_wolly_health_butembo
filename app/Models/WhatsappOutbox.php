<?php

namespace App\Models;

use App\Models\Concerns\Syncable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WhatsappOutbox extends Model
{
    use SoftDeletes, Syncable;

    protected $table = 'whatsapp_outbox';

    protected $fillable = [
        'uuid',
        'origin_device_id',
        'version',
        'to_phone',
        'template_key',
        'locale',
        'payload_json',
        'body_rendered',
        'driver',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'payload_json' => 'array',
        ];
    }
}

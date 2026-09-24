<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

trait Syncable
{
    protected static function bootSyncable(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if ($model->version === null) {
                $model->version = 1;
            }
        });
    }
}

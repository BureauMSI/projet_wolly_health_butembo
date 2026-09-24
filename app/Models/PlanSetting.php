<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'updated_by',
    ];
}

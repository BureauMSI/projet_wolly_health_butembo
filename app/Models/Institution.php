<?php

namespace App\Models;

use App\Models\Concerns\Syncable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Institution extends Model
{
    use SoftDeletes, Syncable;

    protected $fillable = [
        'uuid',
        'origin_device_id',
        'version',
        'name',
        'acronym',
        'slogan',
        'logo_path',
        'email',
        'phone',
        'whatsapp',
        'address',
        'city',
        'country',
        'rccm',
        'tax_id',
        'id_nat',
        'invoice_footer',
        'default_locale',
        'default_currency_code',
    ];
}

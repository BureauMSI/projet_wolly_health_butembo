<?php

namespace App\Models;

use App\Models\Concerns\Syncable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegistrationCode extends Model
{
    use SoftDeletes, Syncable;

    protected $fillable = [
        'uuid',
        'origin_device_id',
        'version',
        'branch_id',
        'code',
        'status',
        'created_by',
        'used_by_user_id',
        'used_by_member_id',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'used_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function usedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by_user_id');
    }

    public function usedByMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'used_by_member_id');
    }

    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }
}

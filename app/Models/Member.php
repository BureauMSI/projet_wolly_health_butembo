<?php

namespace App\Models;

use App\Models\Concerns\Syncable;
use Database\Factories\MemberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Member extends Authenticatable
{
    /** @use HasFactory<MemberFactory> */
    use HasFactory, SoftDeletes, Syncable;

    protected $fillable = [
        'uuid',
        'origin_device_id',
        'version',
        'member_code',
        'full_name',
        'gender',
        'birth_date',
        'phone',
        'address',
        'photo_path',
        'id_document_path',
        'username',
        'password',
        'sponsor_id',
        'placement_parent_id',
        'placement_side',
        'registration_branch_id',
        'locale',
        'status',
        'joined_at',
        'membership_amount_usd',
        'membership_pv',
        'membership_type',
        'source_client_id',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'birth_date' => 'date',
            'joined_at' => 'datetime',
            'membership_amount_usd' => 'decimal:2',
            'membership_pv' => 'decimal:2',
        ];
    }

    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(self::class, 'sponsor_id');
    }

    public function placementParent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'placement_parent_id');
    }

    public function sponsored(): HasMany
    {
        return $this->hasMany(self::class, 'sponsor_id');
    }

    public function placementChildren(): HasMany
    {
        return $this->hasMany(self::class, 'placement_parent_id');
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class, 'referrer_member_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function pvEntries(): HasMany
    {
        return $this->hasMany(PvLedger::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(CommissionLedger::class);
    }

    public function payoutRequests(): HasMany
    {
        return $this->hasMany(PayoutRequest::class);
    }

    public function registrationBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'registration_branch_id');
    }

    public function sourceClient(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'source_client_id');
    }

    public function childOn(string $side): ?self
    {
        return $this->placementChildren()->where('placement_side', $side)->first();
    }

    public static function treeRootId(): ?int
    {
        return once(fn () => static::query()->orderBy('id')->value('id'));
    }

    public function isTreeRoot(): bool
    {
        return $this->id === static::treeRootId();
    }

    public function isInTree(): bool
    {
        return $this->isTreeRoot() || $this->placement_parent_id !== null;
    }

    public function isAwaitingPlacement(): bool
    {
        return $this->placement_parent_id === null && ! $this->isTreeRoot();
    }

    public function scopeAwaitingPlacement($query)
    {
        $rootId = static::treeRootId();

        return $query->whereNull('placement_parent_id')
            ->when($rootId, fn ($inner) => $inner->where('id', '!=', $rootId));
    }

    public function scopeInBinaryTree($query)
    {
        $rootId = static::treeRootId();

        return $query->where(function ($inner) use ($rootId) {
            $inner->whereNotNull('placement_parent_id');
            if ($rootId) {
                $inner->orWhere('id', $rootId);
            }
        });
    }
}

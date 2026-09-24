<?php

namespace App\Models;

use App\Models\Concerns\Syncable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'uuid',
    'origin_device_id',
    'version',
    'name',
    'username',
    'email',
    'password',
    'role',
    'branch_id',
    'locale',
    'is_active',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, Syncable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_MANAGER = 'manager';

    public const ROLE_CASHIER = 'cashier';

    public const ROLE_ACCOUNTANT = 'accountant';

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** Bureau central — voit toutes les succursales. */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /** Responsable / administrateur de succursale. */
    public function isManager(): bool
    {
        return $this->role === self::ROLE_MANAGER;
    }

    public function isCashier(): bool
    {
        return $this->role === self::ROLE_CASHIER;
    }

    public function isAccountant(): bool
    {
        return $this->role === self::ROLE_ACCOUNTANT;
    }

    public function isBranchStaff(): bool
    {
        return in_array($this->role, [self::ROLE_MANAGER, self::ROLE_CASHIER, self::ROLE_ACCOUNTANT], true);
    }

    public function canRegisterMembers(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_MANAGER, self::ROLE_CASHIER], true);
    }

    public function canRecordSales(): bool
    {
        return $this->canRegisterMembers();
    }

    public function canManagePayouts(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_MANAGER, self::ROLE_ACCOUNTANT], true);
    }

    public function canViewCommissions(): bool
    {
        return $this->canManagePayouts();
    }

    public function canViewReports(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_MANAGER, self::ROLE_CASHIER, self::ROLE_ACCOUNTANT], true);
    }

    public function canUseWhatsappOutbox(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_MANAGER, self::ROLE_CASHIER], true);
    }

    /**
     * null = bureau central (toutes succursales), sinon ID de la succursale du staff.
     */
    public function scopedBranchId(): ?int
    {
        return $this->isAdmin() ? null : $this->branch_id;
    }

    public function belongsToBranch(?int $branchId): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $branchId !== null && (int) $this->branch_id === (int) $branchId;
    }
}

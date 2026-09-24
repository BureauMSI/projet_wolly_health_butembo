<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

class CashOperationType extends Model
{
    protected $fillable = [
        'uuid',
        'code',
        'label',
        'direction',
        'sort_order',
        'is_system',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $type) {
            if (empty($type->uuid)) {
                $type->uuid = (string) Str::uuid();
            }
        });
    }

    public static function idFor(string $code): ?int
    {
        return static::query()->where('code', $code)->value('id');
    }

    public static function uniqueCodeFromLabel(string $label): string
    {
        $base = Str::slug($label, '_') ?: 'op';
        $code = $base;
        $i = 2;
        while (static::query()->where('code', $code)->exists()) {
            $code = $base.'_'.$i;
            $i++;
        }

        return $code;
    }

    public function movements(): HasMany
    {
        return $this->hasMany(CashMovement::class, 'operation_type_id');
    }

    public function displayName(): string
    {
        $key = 'messages.cash_category_'.$this->code;
        if (Lang::has($key)) {
            return __($key);
        }

        return filled($this->label) ? (string) $this->label : $this->code;
    }

    public function allows(string $direction): bool
    {
        return $this->direction === 'both' || $this->direction === $direction;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeManual($query)
    {
        return $query->where('is_system', false);
    }
}

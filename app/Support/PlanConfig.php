<?php

namespace App\Support;

use App\Models\PlanSetting;

class PlanConfig
{
    /** @var array<string, string|null>|null */
    private ?array $settings = null;

    public function get(string $key, ?string $default = null): ?string
    {
        $this->hydrate();

        if (! array_key_exists($key, $this->settings)) {
            return $default;
        }

        return $this->settings[$key] ?? $default;
    }

    public function decimal(string $key, string $default = '0'): string
    {
        $value = $this->get($key, $default);

        return is_numeric($value) ? (string) $value : $default;
    }

    public function int(string $key, int $default = 0): int
    {
        return (int) $this->get($key, (string) $default);
    }

    public function isRemoteSyncEnabled(): bool
    {
        return filled(config('sync.remote_url'));
    }

    public function ledgerStatus(): string
    {
        return $this->isRemoteSyncEnabled() ? 'pending' : 'confirmed';
    }

    public function originDeviceId(): ?string
    {
        return config('sync.origin_device_id');
    }

    public function flush(): void
    {
        $this->settings = null;
    }

    private function hydrate(): void
    {
        if ($this->settings !== null) {
            return;
        }

        $this->settings = PlanSetting::query()
            ->pluck('value', 'key')
            ->all();
    }
}

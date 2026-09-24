<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\CashMovement;
use App\Models\Client;
use App\Models\CommissionLedger;
use App\Models\Member;
use App\Models\Product;
use App\Models\PvLedger;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SyncOutboxEntry;
use App\Support\PlanConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncEngine
{
    public function __construct(
        private PlanConfig $plan,
    ) {}

    /**
     * @return array{mode: string, pushed: int, pulled: int, confirmed: int, rejected: int, errors: list<string>}
     */
    public function run(): array
    {
        if (! $this->plan->isRemoteSyncEnabled()) {
            return [
                'mode' => 'local',
                'pushed' => 0,
                'pulled' => 0,
                'confirmed' => 0,
                'rejected' => 0,
                'errors' => [],
            ];
        }

        $errors = [];
        $pushed = 0;
        $confirmed = 0;
        $rejected = 0;
        $pulled = 0;

        try {
            $push = $this->push();
            $pushed = $push['pushed'];
            $confirmed = $push['confirmed'];
            $rejected += $push['rejected'];
            $errors = array_merge($errors, $push['errors']);
        } catch (Throwable $e) {
            Log::warning('sync.push_failed', ['message' => $e->getMessage()]);
            $errors[] = $e->getMessage();
        }

        try {
            $pull = $this->pull();
            $pulled = $pull['pulled'];
            $rejected += $pull['rejected'];
            $errors = array_merge($errors, $pull['errors']);
        } catch (Throwable $e) {
            Log::warning('sync.pull_failed', ['message' => $e->getMessage()]);
            $errors[] = $e->getMessage();
        }

        return [
            'mode' => 'remote',
            'pushed' => $pushed,
            'pulled' => $pulled,
            'confirmed' => $confirmed,
            'rejected' => $rejected,
            'errors' => $errors,
        ];
    }

    /**
     * @return array{pushed: int, confirmed: int, rejected: int, errors: list<string>}
     */
    public function push(): array
    {
        $remote = rtrim((string) config('sync.remote_url'), '/');
        if ($remote === '') {
            return ['pushed' => 0, 'confirmed' => 0, 'rejected' => 0, 'errors' => []];
        }

        $batch = SyncOutboxEntry::query()
            ->whereNull('synced_at')
            ->orderBy('id')
            ->limit((int) config('sync.batch_size', 100))
            ->get();

        if ($batch->isEmpty()) {
            return ['pushed' => 0, 'confirmed' => 0, 'rejected' => 0, 'errors' => []];
        }

        $entries = $batch->map(fn (SyncOutboxEntry $row) => $this->serializeOutgoing($row))->all();

        $response = Http::timeout((int) config('sync.timeout', 30))
            ->withHeaders($this->authHeaders())
            ->acceptJson()
            ->post($remote.'/api/sync/push', [
                'origin_device_id' => $this->plan->originDeviceId(),
                'entries' => $entries,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException(__('messages.sync_remote_error', [
                'status' => $response->status(),
            ]));
        }

        $results = $response->json('results') ?? [];
        $pushed = 0;
        $confirmed = 0;
        $rejected = 0;
        $errors = [];

        foreach ($batch as $index => $row) {
            $result = $results[$index] ?? ['status' => 'accepted'];
            $status = $result['status'] ?? 'accepted';

            if ($status === 'rejected') {
                $row->update([
                    'last_error' => $result['message'] ?? __('messages.placement_conflict_replace'),
                ]);
                $rejected++;
                $errors[] = $result['message'] ?? __('messages.placement_conflict_replace');

                continue;
            }

            if ($status === 'error') {
                $row->update([
                    'last_error' => $result['message'] ?? __('messages.sync_apply_error'),
                ]);
                $errors[] = $result['message'] ?? __('messages.sync_apply_error');

                continue;
            }

            $row->update([
                'synced_at' => now(),
                'last_error' => null,
            ]);
            $pushed++;
            $confirmed += $this->confirmEntity($row->entity_type, $row->entity_uuid);
        }

        return compact('pushed', 'confirmed', 'rejected', 'errors');
    }

    /**
     * @return array{pulled: int, rejected: int, errors: list<string>}
     */
    public function pull(): array
    {
        $remote = rtrim((string) config('sync.remote_url'), '/');
        if ($remote === '') {
            return ['pulled' => 0, 'rejected' => 0, 'errors' => []];
        }

        $response = Http::timeout((int) config('sync.timeout', 30))
            ->withHeaders($this->authHeaders())
            ->acceptJson()
            ->get($remote.'/api/sync/pull', [
                'origin_device_id' => $this->plan->originDeviceId(),
                'limit' => (int) config('sync.batch_size', 100),
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException(__('messages.sync_remote_error', [
                'status' => $response->status(),
            ]));
        }

        $entries = $response->json('entries') ?? [];
        $pulled = 0;
        $rejected = 0;
        $errors = [];

        foreach ($entries as $entry) {
            $result = $this->ingest($entry);
            if (($result['status'] ?? '') === 'rejected') {
                $rejected++;
                $errors[] = $result['message'] ?? __('messages.placement_conflict_replace');

                continue;
            }
            if (($result['status'] ?? '') === 'error') {
                $errors[] = $result['message'] ?? __('messages.sync_apply_error');

                continue;
            }
            $pulled++;
        }

        return compact('pulled', 'rejected', 'errors');
    }

    /**
     * Apply one outbox-shaped entry on this node (authoritative ingest).
     *
     * @param  array{entity_type: string, entity_uuid: string, operation: string, payload?: array, origin_device_id?: string|null}  $entry
     * @return array{status: string, message?: string}
     */
    public function ingest(array $entry): array
    {
        $type = (string) ($entry['entity_type'] ?? '');
        $uuid = (string) ($entry['entity_uuid'] ?? '');
        $operation = (string) ($entry['operation'] ?? 'create');
        $payload = is_array($entry['payload'] ?? null) ? $entry['payload'] : [];

        if ($type === '' || $uuid === '') {
            return ['status' => 'error', 'message' => __('messages.sync_apply_error')];
        }

        try {
            return DB::transaction(function () use ($type, $uuid, $operation, $payload, $entry) {
                if ($operation === 'delete') {
                    return $this->deleteByUuid($type, $uuid);
                }

                return match ($type) {
                    'member' => $this->upsertMember($uuid, $payload, $entry['origin_device_id'] ?? null),
                    'sale' => $this->upsertByUuid(Sale::class, $uuid, $this->mapSalePayload($payload), $entry['origin_device_id'] ?? null),
                    'sale_item' => $this->upsertByUuid(SaleItem::class, $uuid, $this->mapSaleItemPayload($payload), $entry['origin_device_id'] ?? null),
                    'product' => $this->upsertByUuid(Product::class, $uuid, $this->stripLocalKeys($payload), $entry['origin_device_id'] ?? null),
                    'client' => $this->upsertByUuid(Client::class, $uuid, $this->mapClientPayload($payload), $entry['origin_device_id'] ?? null),
                    'branch' => $this->upsertByUuid(Branch::class, $uuid, $this->stripLocalKeys($payload), $entry['origin_device_id'] ?? null),
                    'cash_movement' => $this->upsertByUuid(CashMovement::class, $uuid, $this->mapCashPayload($payload), $entry['origin_device_id'] ?? null),
                    'pv_ledger' => $this->upsertByUuid(PvLedger::class, $uuid, $this->mapPvPayload($payload), $entry['origin_device_id'] ?? null),
                    'commission_ledger' => $this->upsertByUuid(CommissionLedger::class, $uuid, $this->mapCommissionPayload($payload), $entry['origin_device_id'] ?? null),
                    default => ['status' => 'accepted'],
                };
            });
        } catch (Throwable $e) {
            Log::warning('sync.ingest_failed', [
                'entity_type' => $type,
                'entity_uuid' => $uuid,
                'message' => $e->getMessage(),
            ]);

            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * @param  list<array<string, mixed>>  $entries
     * @return list<array{status: string, message?: string}>
     */
    public function ingestMany(array $entries): array
    {
        $results = [];
        foreach ($entries as $entry) {
            $results[] = $this->ingest($entry);
        }

        return $results;
    }

    public function pendingCount(): int
    {
        return SyncOutboxEntry::query()->whereNull('synced_at')->count();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function pendingEntries(int $limit = 100, ?string $excludeOrigin = null): array
    {
        $query = SyncOutboxEntry::query()
            ->whereNull('synced_at')
            ->orderBy('id')
            ->limit($limit);

        if ($excludeOrigin) {
            $query->where(function ($q) use ($excludeOrigin) {
                $q->whereNull('origin_device_id')
                    ->orWhere('origin_device_id', '!=', $excludeOrigin);
            });
        }

        return $query->get()
            ->map(fn (SyncOutboxEntry $row) => $this->serializeOutgoing($row))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeOutgoing(SyncOutboxEntry $row): array
    {
        $payload = is_array($row->payload_json) ? $row->payload_json : [];
        $payload = $this->enrichPayload($row->entity_type, $payload);

        return [
            'entity_type' => $row->entity_type,
            'entity_uuid' => $row->entity_uuid,
            'operation' => $row->operation,
            'payload' => $payload,
            'origin_device_id' => $row->origin_device_id ?? $this->plan->originDeviceId(),
            'queued_at' => optional($row->queued_at)?->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function enrichPayload(string $type, array $payload): array
    {
        if ($type === 'member') {
            if (! empty($payload['sponsor_id'])) {
                $payload['sponsor_uuid'] = Member::query()->whereKey($payload['sponsor_id'])->value('uuid');
            }
            if (! empty($payload['placement_parent_id'])) {
                $payload['placement_parent_uuid'] = Member::query()->whereKey($payload['placement_parent_id'])->value('uuid');
            }
            if (! empty($payload['registration_branch_id'])) {
                $payload['registration_branch_uuid'] = Branch::query()->whereKey($payload['registration_branch_id'])->value('uuid');
            }
        }

        if ($type === 'sale') {
            if (! empty($payload['member_id'])) {
                $payload['member_uuid'] = Member::query()->whereKey($payload['member_id'])->value('uuid');
            }
            if (! empty($payload['client_id'])) {
                $payload['client_uuid'] = Client::query()->whereKey($payload['client_id'])->value('uuid');
            }
            if (! empty($payload['branch_id'])) {
                $payload['branch_uuid'] = Branch::query()->whereKey($payload['branch_id'])->value('uuid');
            }
        }

        if ($type === 'client' && ! empty($payload['referrer_member_id'])) {
            $payload['referrer_member_uuid'] = Member::query()->whereKey($payload['referrer_member_id'])->value('uuid');
        }

        if (in_array($type, ['pv_ledger', 'commission_ledger'], true) && ! empty($payload['member_id'])) {
            $payload['member_uuid'] = Member::query()->whereKey($payload['member_id'])->value('uuid');
        }

        if ($type === 'sale_item') {
            if (! empty($payload['sale_id'])) {
                $payload['sale_uuid'] = Sale::query()->whereKey($payload['sale_id'])->value('uuid');
            }
            if (! empty($payload['product_id'])) {
                $payload['product_uuid'] = Product::query()->whereKey($payload['product_id'])->value('uuid');
            }
        }

        if ($type === 'cash_movement') {
            if (! empty($payload['branch_id'])) {
                $payload['branch_uuid'] = Branch::query()->whereKey($payload['branch_id'])->value('uuid');
            }
            if (! empty($payload['sale_id'])) {
                $payload['sale_uuid'] = Sale::query()->whereKey($payload['sale_id'])->value('uuid');
            }
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{status: string, message?: string}
     */
    private function upsertMember(string $uuid, array $payload, ?string $originDeviceId): array
    {
        $sponsorId = $this->resolveMemberId($payload['sponsor_uuid'] ?? null);
        $parentId = $this->resolveMemberId($payload['placement_parent_uuid'] ?? null);
        $side = $payload['placement_side'] ?? null;
        $branchId = $this->resolveBranchId($payload['registration_branch_uuid'] ?? null);

        if ($parentId && in_array($side, ['left', 'right'], true)) {
            $occupant = Member::query()
                ->where('placement_parent_id', $parentId)
                ->where('placement_side', $side)
                ->first();

            if ($occupant && $occupant->uuid !== $uuid) {
                return [
                    'status' => 'rejected',
                    'message' => __('messages.placement_conflict_replace', [
                        'side' => $side,
                        'username' => $occupant->username,
                    ]),
                ];
            }
        }

        $data = $this->stripLocalKeys($payload);
        unset(
            $data['sponsor_uuid'],
            $data['placement_parent_uuid'],
            $data['registration_branch_uuid'],
            $data['id'],
        );

        $data['sponsor_id'] = $sponsorId;
        $data['placement_parent_id'] = $parentId;
        $data['placement_side'] = $side;
        if ($branchId) {
            $data['registration_branch_id'] = $branchId;
        }
        if ($originDeviceId) {
            $data['origin_device_id'] = $originDeviceId;
        }

        // Never overwrite password with a raw hash from another node unless provided.
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $existing = Member::withTrashed()->where('uuid', $uuid)->first();
        if ($existing) {
            if (empty($data['password'])) {
                unset($data['password']);
            }
            $existing->fill($data);
            $existing->deleted_at = null;
            $existing->save();
            $existing->increment('version');
        } else {
            $data['uuid'] = $uuid;
            if (empty($data['password'])) {
                $data['password'] = $this->plan->get('default_member_password', 'password');
            }
            Member::query()->create($data);
        }

        return ['status' => 'accepted'];
    }

    /**
     * @param  class-string  $modelClass
     * @param  array<string, mixed>  $data
     * @return array{status: string}
     */
    private function upsertByUuid(string $modelClass, string $uuid, array $data, ?string $originDeviceId): array
    {
        unset($data['id']);
        if ($originDeviceId) {
            $data['origin_device_id'] = $originDeviceId;
        }

        $existing = $modelClass::withTrashed()->where('uuid', $uuid)->first();
        if ($existing) {
            $existing->fill($data);
            if (method_exists($existing, 'trashed') && $existing->trashed()) {
                $existing->restore();
            }
            $existing->save();
            if (isset($existing->version)) {
                $existing->increment('version');
            }
        } else {
            $data['uuid'] = $uuid;
            $modelClass::query()->create($data);
        }

        return ['status' => 'accepted'];
    }

    /**
     * @return array{status: string}
     */
    private function deleteByUuid(string $type, string $uuid): array
    {
        $model = match ($type) {
            'member' => Member::class,
            'sale' => Sale::class,
            'sale_item' => SaleItem::class,
            'product' => Product::class,
            'client' => Client::class,
            'branch' => Branch::class,
            'cash_movement' => CashMovement::class,
            'pv_ledger' => PvLedger::class,
            'commission_ledger' => CommissionLedger::class,
            default => null,
        };

        if ($model) {
            $row = $model::query()->where('uuid', $uuid)->first();
            $row?->delete();
        }

        return ['status' => 'accepted'];
    }

    private function confirmEntity(string $type, string $uuid): int
    {
        $count = 0;

        if ($type === 'sale') {
            $count += Sale::query()->where('uuid', $uuid)->where('sync_status', 'pending')
                ->update(['sync_status' => 'confirmed']);
        }

        if ($type === 'pv_ledger') {
            $count += PvLedger::query()->where('uuid', $uuid)->where('sync_status', 'pending')
                ->update(['sync_status' => 'confirmed']);
        }

        if ($type === 'commission_ledger') {
            $count += CommissionLedger::query()->where('uuid', $uuid)->where('status', 'pending')
                ->update(['status' => 'confirmed']);
        }

        return $count;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function mapSalePayload(array $payload): array
    {
        $data = $this->stripLocalKeys($payload);
        if (! empty($payload['member_uuid'])) {
            $data['member_id'] = $this->resolveMemberId($payload['member_uuid']);
        }
        if (! empty($payload['client_uuid'])) {
            $data['client_id'] = $this->resolveClientId($payload['client_uuid']);
        }
        if (! empty($payload['branch_uuid'])) {
            $data['branch_id'] = $this->resolveBranchId($payload['branch_uuid']);
        }
        unset($data['member_uuid'], $data['client_uuid'], $data['branch_uuid'], $data['user_id']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function mapSaleItemPayload(array $payload): array
    {
        $data = $this->stripLocalKeys($payload);
        if (! empty($payload['sale_uuid'])) {
            $data['sale_id'] = Sale::query()->where('uuid', $payload['sale_uuid'])->value('id');
        }
        if (! empty($payload['product_uuid'])) {
            $data['product_id'] = Product::query()->where('uuid', $payload['product_uuid'])->value('id');
        }
        unset($data['sale_uuid'], $data['product_uuid']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function mapClientPayload(array $payload): array
    {
        $data = $this->stripLocalKeys($payload);
        if (! empty($payload['referrer_member_uuid'])) {
            $data['referrer_member_id'] = $this->resolveMemberId($payload['referrer_member_uuid']);
        }
        unset($data['referrer_member_uuid']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function mapPvPayload(array $payload): array
    {
        $data = $this->stripLocalKeys($payload);
        if (! empty($payload['member_uuid'])) {
            $data['member_id'] = $this->resolveMemberId($payload['member_uuid']);
        }
        if (! empty($payload['sale_uuid'])) {
            $data['sale_id'] = Sale::query()->where('uuid', $payload['sale_uuid'])->value('id');
        }
        unset($data['member_uuid'], $data['sale_uuid']);
        $data['sync_status'] = 'confirmed';

        return $data;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function mapCommissionPayload(array $payload): array
    {
        $data = $this->stripLocalKeys($payload);
        if (! empty($payload['member_uuid'])) {
            $data['member_id'] = $this->resolveMemberId($payload['member_uuid']);
        }
        unset($data['member_uuid']);
        if (($data['status'] ?? null) === 'pending') {
            $data['status'] = 'confirmed';
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function mapCashPayload(array $payload): array
    {
        $data = $this->stripLocalKeys($payload);
        if (! empty($payload['branch_uuid'])) {
            $data['branch_id'] = $this->resolveBranchId($payload['branch_uuid']);
        }
        if (! empty($payload['sale_uuid'])) {
            $data['sale_id'] = Sale::query()->where('uuid', $payload['sale_uuid'])->value('id');
        }
        unset($data['branch_uuid'], $data['sale_uuid'], $data['user_id']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function stripLocalKeys(array $payload): array
    {
        unset(
            $payload['id'],
            $payload['created_at'],
            $payload['updated_at'],
            $payload['deleted_at'],
        );

        return $payload;
    }

    private function resolveMemberId(mixed $uuid): ?int
    {
        if (! is_string($uuid) || $uuid === '') {
            return null;
        }

        $id = Member::query()->where('uuid', $uuid)->value('id');

        return $id ? (int) $id : null;
    }

    private function resolveClientId(mixed $uuid): ?int
    {
        if (! is_string($uuid) || $uuid === '') {
            return null;
        }

        $id = Client::query()->where('uuid', $uuid)->value('id');

        return $id ? (int) $id : null;
    }

    private function resolveBranchId(mixed $uuid): ?int
    {
        if (! is_string($uuid) || $uuid === '') {
            return null;
        }

        $id = Branch::query()->where('uuid', $uuid)->value('id');

        return $id ? (int) $id : null;
    }

    /**
     * @return array<string, string>
     */
    private function authHeaders(): array
    {
        $token = (string) config('sync.token', '');

        return $token !== '' ? ['X-Sync-Token' => $token] : [];
    }
}

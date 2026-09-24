<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\CashMovement;
use App\Models\CashOperationType;
use App\Models\Client;
use App\Models\Member;
use App\Models\User;
use App\Support\PlanConfig;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class MemberRegistrar
{
    public function __construct(
        private PlanConfig $plan,
        private UsernameGenerator $usernames,
        private AuditLogger $audit,
        private SyncOutbox $outbox,
        private WhatsAppNotifier $whatsapp,
        private CompensationEngine $compensation,
        private RegistrationCodeService $registrationCodes,
    ) {}

    public function register(
        User $actor,
        array $data,
        ?UploadedFile $photo = null,
        ?UploadedFile $idDocument = null,
    ): MemberRegistration {
        $plainPassword = filled($data['password'] ?? null)
            ? (string) $data['password']
            : (string) ($this->plan->get('default_member_password') ?: 'ChangeMe123');

        if (! filled($plainPassword) || strlen($plainPassword) < 8) {
            throw ValidationException::withMessages([
                'password' => __('messages.default_password_missing'),
            ]);
        }

        try {
            $result = DB::transaction(function () use ($actor, $data, $photo, $idDocument, $plainPassword) {
                $type = ($data['membership_type'] ?? 'direct') === 'indirect' ? 'indirect' : 'direct';
                $amount = $type === 'direct'
                    ? round((float) $this->plan->decimal('membership_amount_usd'), 2)
                    : 0.0;
                $pv = (float) $this->plan->decimal('membership_pv_threshold');
                $branchId = $this->resolveBranchId($actor, $data['registration_branch_id'] ?? null);

                if ($branchId === null) {
                    throw ValidationException::withMessages([
                        'registration_branch_id' => __('messages.membership_branch_required'),
                    ]);
                }

                $requiresCode = ! $actor->isAdmin();

                $member = Member::query()->create([
                    'origin_device_id' => $this->plan->originDeviceId(),
                    'member_code' => $this->nextMemberCode(),
                    'full_name' => $data['full_name'],
                    'gender' => $data['gender'] ?? null,
                    'birth_date' => $data['birth_date'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'address' => $data['address'] ?? null,
                    'photo_path' => $photo?->store('members/photos', 'public'),
                    'id_document_path' => $idDocument?->store('members/ids', 'public'),
                    'username' => $this->usernames->fromBase((string) $data['username']),
                    'password' => $plainPassword,
                    'sponsor_id' => null,
                    'placement_parent_id' => null,
                    'placement_side' => null,
                    'registration_branch_id' => $branchId,
                    'locale' => $data['locale'] ?? 'fr',
                    'status' => 'active',
                    'joined_at' => now(),
                    'membership_amount_usd' => $amount,
                    'membership_pv' => $pv,
                    'membership_type' => $type,
                    'source_client_id' => $type === 'indirect' ? (int) $data['source_client_id'] : null,
                ]);

                if ($requiresCode) {
                    $this->registrationCodes->consumeNext($branchId, $member, $actor);
                }

                if ($type === 'indirect') {
                    $this->consumeClientPv($actor, $member, (int) $data['source_client_id'], $pv);
                } else {
                    $this->recordMembershipCash($actor, $member, $branchId, $amount);
                }

                $this->compensation->creditJoinPack($member);

                $snapshot = $this->snapshot($member);
                $this->outbox->enqueue('member', $member->uuid, 'create', $snapshot, $this->plan->originDeviceId());
                $this->audit->record($actor, 'created', $member, null, $snapshot);

                return new MemberRegistration($member, $plainPassword);
            });
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (QueryException $exception) {
            report($exception);
            throw ValidationException::withMessages([
                'full_name' => __('messages.member_create_failed'),
            ]);
        }

        try {
            if (filled($result->member->phone)) {
                $this->whatsapp->enqueue(
                    $result->member->phone,
                    'welcome',
                    $result->member->locale ?: 'fr',
                    [
                        'name' => $result->member->full_name,
                        'username' => $result->member->username,
                        'password' => $result->plainPassword,
                    ],
                );
            }
        } catch (Throwable $exception) {
            report($exception);
        }

        return $result;
    }

    private function resolveBranchId(User $actor, mixed $posted): ?int
    {
        if (! $actor->isAdmin()) {
            return $actor->branch_id;
        }

        if (filled($posted) && Branch::query()->whereKey($posted)->exists()) {
            return (int) $posted;
        }

        $fallback = Branch::query()->where('is_active', true)->orderBy('id')->value('id');

        return $fallback !== null ? (int) $fallback : null;
    }

    private function recordMembershipCash(User $actor, Member $member, int $branchId, float $amount): void
    {
        $cash = CashMovement::query()->create([
            'origin_device_id' => $this->plan->originDeviceId(),
            'branch_id' => $branchId,
            'direction' => 'in',
            'category' => 'membership',
            'operation_type_id' => CashOperationType::idFor('membership'),
            'amount' => $amount,
            'currency_code' => 'USD',
            'rate_to_usd' => '1',
            'amount_usd' => $amount,
            'description' => $member->member_code,
            'user_id' => $actor->id,
            'occurred_at' => now(),
        ]);
        $this->outbox->enqueue('cash_movement', $cash->uuid, 'create', $cash->toArray(), $this->plan->originDeviceId());
    }

    private function consumeClientPv(User $actor, Member $member, int $clientId, float $pv): void
    {
        $client = Client::query()->lockForUpdate()->find($clientId);

        if ($client === null) {
            throw ValidationException::withMessages([
                'source_client_id' => __('messages.source_client'),
            ]);
        }

        if (! $client->canFundIndirectMembership($pv)) {
            throw ValidationException::withMessages([
                'source_client_id' => $client->converted_member_id
                    ? __('messages.client_pv_already_used')
                    : __('messages.client_pv_not_enough'),
            ]);
        }

        $old = $client->toArray();
        $client->update([
            'accumulated_pv' => round((float) $client->accumulated_pv - $pv, 2),
            'converted_member_id' => $member->id,
        ]);
        $client->refresh();
        $this->outbox->enqueue('client', $client->uuid, 'update', $client->toArray(), $this->plan->originDeviceId());
        $this->audit->record($actor, 'updated', $client, $old, $client->toArray());
    }

    private function nextMemberCode(): string
    {
        $sequence = 1;
        foreach (Member::withTrashed()->pluck('member_code') as $existing) {
            if (preg_match('/^HH-(\d+)$/', (string) $existing, $matches)) {
                $sequence = max($sequence, ((int) $matches[1]) + 1);
            }
        }

        do {
            $code = 'HH-'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
            $sequence++;
        } while (Member::withTrashed()->where('member_code', $code)->exists());

        return $code;
    }

    private function snapshot(Member $member): array
    {
        $payload = $member->makeHidden('password')->toArray();
        unset($payload['password']);

        return json_decode(json_encode($payload), true) ?? [];
    }
}

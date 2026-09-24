<?php

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Branch;
use App\Models\CommissionLedger;
use App\Models\Member;
use App\Services\CompensationEngine;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

$branch = Branch::query()->firstOrFail();
$engine = app(CompensationEngine::class);

$place = function (Member $parent, string $side, string $fullName, string $code) use ($branch, $engine): Member {
    $taken = Member::query()
        ->where('placement_parent_id', $parent->id)
        ->where('placement_side', $side)
        ->exists();
    if ($taken) {
        throw new RuntimeException("Side {$side} taken under {$parent->full_name}");
    }

    $member = Member::query()->create([
        'uuid' => (string) Str::uuid(),
        'member_code' => $code,
        'full_name' => $fullName,
        'username' => 'ext'.Str::lower(Str::random(6)),
        'password' => Hash::make('ChangeMe123'),
        'registration_branch_id' => $branch->id,
        'locale' => 'fr',
        'status' => 'active',
        'joined_at' => now(),
        'membership_amount_usd' => 0,
        'membership_pv' => 40,
        'membership_type' => 'direct',
        'placement_parent_id' => $parent->id,
        'placement_side' => $side,
        'sponsor_id' => $parent->id,
    ]);

    $engine->onMembership($member);
    $engine->onPlacement($member);

    return $member->fresh();
};

$chainUnder = function (Member $start, string $firstSide, array $names, string $codePrefix) use ($place): Member {
    $parent = $start;
    $side = $firstSide;
    $last = null;
    foreach ($names as $i => $name) {
        $last = $place($parent, $side, $name, sprintf('%s-%02d', $codePrefix, $i + 1));
        $parent = $last;
        $side = 'left'; // deepen on left after first placement
    }

    return $last;
};

// 1) Repair incomplete equilibria on existing placements (TEQ chain now under janvier)
$repaired = $engine->backfillPlacementEquilibria();
echo "Repaired/added equilibrium rows: {$repaired}\n";

// 2) Extend existing free sides with deeper branches (idempotent skip if code exists)
$byCode = fn (string $code) => Member::query()->where('member_code', $code)->first();

$janvier = Member::query()->where('member_code', 'HH-00004')->firstOrFail();
$alice = Member::query()->where('member_code', 'HH-00005')->firstOrFail();
$patricia = Member::query()->where('member_code', 'HH-00006')->firstOrFail();
$katungu = Member::query()->where('member_code', 'HH-00007')->firstOrFail();
$eq5 = Member::query()->where('member_code', 'TEQ-006')->first();

$created = [];

if (! $byCode('EXT-J-01') && ! Member::query()->where('placement_parent_id', $janvier->id)->where('placement_side', 'right')->exists()) {
    $created[] = $chainUnder($janvier, 'right', [
        'Branche Janvier R1',
        'Branche Janvier R2',
        'Branche Janvier R3',
        'Branche Janvier R4',
        'Branche Janvier R5',
    ], 'EXT-J');
}

if (! $byCode('EXT-A-01')) {
    $created[] = $chainUnder($alice, 'left', [
        'Branche Alice L1',
        'Branche Alice L2',
        'Branche Alice L3',
        'Branche Alice L4',
        'Branche Alice L5',
        'Branche Alice L6',
    ], 'EXT-A');
}

if (! $byCode('EXT-P-01')) {
    $created[] = $chainUnder($patricia, 'left', [
        'Branche Patricia L1',
        'Branche Patricia L2',
        'Branche Patricia L3',
        'Branche Patricia L4',
        'Branche Patricia L5',
    ], 'EXT-P');
}

if (! $byCode('EXT-K-01')) {
    $created[] = $chainUnder($katungu, 'right', [
        'Branche Katungu R1',
        'Branche Katungu R2',
        'Branche Katungu R3',
        'Branche Katungu R4',
        'Branche Katungu R5',
        'Branche Katungu R6',
        'Branche Katungu R7',
    ], 'EXT-K');
}

if ($eq5 && ! $byCode('EXT-T-01')) {
    $created[] = $chainUnder($eq5, 'left', [
        'Profondeur TEQ L1',
        'Profondeur TEQ L2',
        'Profondeur TEQ L3',
    ], 'EXT-T');
}

if ($eq5 && ! $byCode('EXT-TR-01') && ! Member::query()->where('placement_parent_id', $eq5->id)->where('placement_side', 'right')->exists()) {
    $created[] = $place($eq5, 'right', 'Profondeur TEQ Droit', 'EXT-TR-01');
}

echo 'New leaf members created: '.count(array_filter($created))."\n";

// Show deep sources with gen>=5 (1 USD)
echo "\n=== Sources with equilibrium after 4th (1 USD) ===\n";
$after = CommissionLedger::query()
    ->where('type', 'equilibrium')
    ->whereNull('sale_id')
    ->where('generation', '>=', 5)
    ->with(['member:id,full_name,member_code', 'relatedMember:id,full_name,member_code'])
    ->orderBy('related_member_id')
    ->orderBy('generation')
    ->get();

foreach ($after as $row) {
    echo sprintf(
        "source=%s gen=%d -> %s amount=%s\n",
        $row->relatedMember?->full_name,
        $row->generation,
        $row->member?->full_name,
        $row->amount_usd
    );
}

echo "\nTOTAL eq rows=".CommissionLedger::where('type','equilibrium')->count()."\n";
echo "TOTAL 1USD rows=".CommissionLedger::where('type','equilibrium')->where('amount_usd', 1)->count()."\n";
echo "TOTAL 4USD rows=".CommissionLedger::where('type','equilibrium')->where('amount_usd', 4)->count()."\n";

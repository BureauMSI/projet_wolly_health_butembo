<?php

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\CommissionLedger;
use App\Models\Member;

echo "MEMBERS\n";
foreach (Member::query()->orderBy('id')->get(['id','member_code','full_name','placement_parent_id','placement_side','sponsor_id']) as $m) {
    echo "#{$m->id} {$m->member_code} {$m->full_name} parent=".($m->placement_parent_id ?: '-')." side=".($m->placement_side ?: '-')." sponsor=".($m->sponsor_id ?: '-')."\n";
}

echo "\nEQ BY RELATED (placement)\n";
$rows = CommissionLedger::query()
    ->where('type', 'equilibrium')
    ->whereNull('sale_id')
    ->with(['member:id,full_name', 'relatedMember:id,full_name'])
    ->orderBy('related_member_id')
    ->orderBy('generation')
    ->get();

foreach ($rows->groupBy('related_member_id') as $relatedId => $group) {
    $src = $group->first()->relatedMember?->full_name;
    echo "source={$src} (#{$relatedId})\n";
    foreach ($group as $r) {
        echo "  gen={$r->generation} -> {$r->member?->full_name} {$r->amount_usd}\n";
    }
}

echo "\nOPEN SIDES (parent can take a child)\n";
$byParent = Member::query()->whereNotNull('placement_parent_id')->get()->groupBy('placement_parent_id');
foreach (Member::query()->orderBy('id')->get() as $m) {
    $kids = $byParent->get($m->id, collect());
    $left = $kids->firstWhere('placement_side', 'left');
    $right = $kids->firstWhere('placement_side', 'right');
    if (! $left || ! $right) {
        echo "#{$m->id} {$m->full_name} free=".(!$left ? 'left ' : '').(!$right ? 'right' : '')."\n";
    }
}

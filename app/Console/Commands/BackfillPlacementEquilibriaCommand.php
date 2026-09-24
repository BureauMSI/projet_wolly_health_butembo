<?php

namespace App\Console\Commands;

use App\Services\CompensationEngine;
use Illuminate\Console\Command;

class BackfillPlacementEquilibriaCommand extends Command
{
    protected $signature = 'equilibrium:backfill-placements';

    protected $description = 'Credit missing equilibrium commissions for members already placed in the tree';

    public function handle(CompensationEngine $compensation): int
    {
        $created = $compensation->backfillPlacementEquilibria();
        $this->info("Equilibrium commissions created: {$created}");

        return self::SUCCESS;
    }
}

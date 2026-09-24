<?php

namespace App\Console\Commands;

use App\Services\SyncEngine;
use Illuminate\Console\Command;

class SyncRunCommand extends Command
{
    protected $signature = 'sync:run';

    protected $description = 'Push/pull the sync outbox toward SYNC_REMOTE_URL (no-op if empty)';

    public function handle(SyncEngine $engine): int
    {
        $result = $engine->run();

        $this->info("mode={$result['mode']} pushed={$result['pushed']} pulled={$result['pulled']} confirmed={$result['confirmed']} rejected={$result['rejected']}");

        foreach ($result['errors'] as $error) {
            $this->warn($error);
        }

        return $result['errors'] === [] ? self::SUCCESS : self::FAILURE;
    }
}

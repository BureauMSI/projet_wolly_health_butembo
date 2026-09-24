<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Default after the 4th equilibrium is a flat 1 USD (parametrizable), not 0.25.
        DB::table('equilibrium_rules')
            ->where('scope', 'after_generation_4')
            ->whereNull('generation')
            ->where('amount_usd', 0.25)
            ->update(['amount_usd' => 1]);
    }

    public function down(): void
    {
        DB::table('equilibrium_rules')
            ->where('scope', 'after_generation_4')
            ->whereNull('generation')
            ->where('amount_usd', 1)
            ->update(['amount_usd' => 0.25]);
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Equilibria 1–4: flat 4 USD each (parametrizable via equilibrium_rules).
        // After the 4th: flat 1 USD (parametrizable).
        foreach ([1, 2, 3, 4] as $generation) {
            DB::table('equilibrium_rules')
                ->where('scope', 'generations_1_4')
                ->where('generation', $generation)
                ->whereNull('deleted_at')
                ->update(['amount_usd' => 4, 'is_active' => true]);
        }

        DB::table('equilibrium_rules')
            ->where('scope', 'after_generation_4')
            ->whereNull('generation')
            ->whereNull('deleted_at')
            ->update(['amount_usd' => 1, 'is_active' => true]);
    }

    public function down(): void
    {
        $previous = [
            1 => 2,
            2 => 1.5,
            3 => 1,
            4 => 0.5,
        ];

        foreach ($previous as $generation => $amount) {
            DB::table('equilibrium_rules')
                ->where('scope', 'generations_1_4')
                ->where('generation', $generation)
                ->whereNull('deleted_at')
                ->update(['amount_usd' => $amount]);
        }
    }
};

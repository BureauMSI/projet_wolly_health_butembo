<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('plan_settings')->where('key', 'equilibrium_near_count')->exists();
        if ($exists) {
            return;
        }

        DB::table('plan_settings')->insert([
            'key' => 'equilibrium_near_count',
            'value' => '4',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('plan_settings')->where('key', 'equilibrium_near_count')->delete();
    }
};

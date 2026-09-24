<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reward_tiers', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('label');
        });

        DB::table('reward_tiers')->whereIn('label', ['Bronze', 'Argent'])->update([
            'is_active' => false,
            'updated_at' => now(),
        ]);

        $prizes = [
            ['label' => 'Machine à coudre', 'min_pv' => 160, 'sort_order' => 1, 'image_path' => 'images/rewards/sewing-machine.png'],
            ['label' => 'Téléphone', 'min_pv' => 1000, 'sort_order' => 2, 'image_path' => 'images/rewards/phone.png'],
            ['label' => 'Moto', 'min_pv' => 10000, 'sort_order' => 3, 'image_path' => 'images/rewards/moto.png'],
            ['label' => 'Voiture', 'min_pv' => 24000, 'sort_order' => 4, 'image_path' => 'images/rewards/car.png'],
        ];

        foreach ($prizes as $prize) {
            $existing = DB::table('reward_tiers')->where('label', $prize['label'])->first();
            $payload = [
                'min_pv' => $prize['min_pv'],
                'max_pv' => null,
                'amount_usd' => 0,
                'image_path' => $prize['image_path'],
                'sort_order' => $prize['sort_order'],
                'is_active' => true,
                'updated_at' => now(),
            ];

            if ($existing) {
                DB::table('reward_tiers')->where('id', $existing->id)->update($payload);
                continue;
            }

            DB::table('reward_tiers')->insert($payload + [
                'label' => $prize['label'],
                'uuid' => (string) Str::uuid(),
                'version' => 1,
                'created_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('reward_tiers', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }
};

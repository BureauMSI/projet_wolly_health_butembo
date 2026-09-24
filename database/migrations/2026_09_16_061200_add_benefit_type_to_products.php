<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'benefit_type')) {
                $table->string('benefit_type', 20)->default('pv')->after('name');
            }
        });

        foreach (DB::table('products')->select('id', 'pv_per_tablet', 'box_pv', 'commission_percent')->get() as $product) {
            $isPercent = (float) $product->commission_percent > 0
                && (float) $product->pv_per_tablet <= 0
                && (float) $product->box_pv <= 0;

            DB::table('products')->where('id', $product->id)->update([
                'benefit_type' => $isPercent ? 'percent' : 'pv',
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'benefit_type')) {
                $table->dropColumn('benefit_type');
            }
        });
    }
};

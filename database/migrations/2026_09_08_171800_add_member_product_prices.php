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
            if (! Schema::hasColumn('products', 'member_unit_price_usd')) {
                $table->decimal('member_unit_price_usd', 14, 2)->nullable()->after('unit_price_usd');
            }
            if (! Schema::hasColumn('products', 'member_box_price_usd')) {
                $table->decimal('member_box_price_usd', 14, 2)->nullable()->after('box_price_usd');
            }
        });

        foreach (DB::table('products')->get() as $product) {
            DB::table('products')->where('id', $product->id)->update([
                'member_unit_price_usd' => $product->member_unit_price_usd ?? $product->unit_price_usd,
                'member_box_price_usd' => $product->member_box_price_usd ?? $product->box_price_usd,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'member_unit_price_usd')) {
                $table->dropColumn('member_unit_price_usd');
            }
            if (Schema::hasColumn('products', 'member_box_price_usd')) {
                $table->dropColumn('member_box_price_usd');
            }
        });
    }
};

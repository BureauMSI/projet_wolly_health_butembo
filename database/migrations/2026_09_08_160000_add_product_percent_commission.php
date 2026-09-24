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
            if (! Schema::hasColumn('products', 'commission_percent')) {
                $table->decimal('commission_percent', 5, 2)->default(0)->after('box_pv');
            }
        });

        Schema::table('sales', function (Blueprint $table) {
            if (! Schema::hasColumn('sales', 'benefit_mode')) {
                $table->string('benefit_mode', 20)->default('pv')->after('buyer_type');
            }
        });

        Schema::table('sale_items', function (Blueprint $table) {
            if (! Schema::hasColumn('sale_items', 'commission_percent')) {
                $table->decimal('commission_percent', 5, 2)->default(0)->after('pv');
            }
            if (! Schema::hasColumn('sale_items', 'commission_usd')) {
                $table->decimal('commission_usd', 14, 2)->default(0)->after('commission_percent');
            }
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE commission_ledger MODIFY type ENUM('sponsorship', 'equilibrium', 'reward', 'product_percent') NOT NULL");
        }
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['commission_percent', 'commission_usd']);
        });
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('benefit_mode');
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('commission_percent');
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE commission_ledger MODIFY type ENUM('sponsorship', 'equilibrium', 'reward') NOT NULL");
        }
    }
};

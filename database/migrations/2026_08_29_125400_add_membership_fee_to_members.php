<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            if (! Schema::hasColumn('members', 'membership_amount_usd')) {
                $table->decimal('membership_amount_usd', 14, 2)->nullable();
            }
            if (! Schema::hasColumn('members', 'membership_pv')) {
                $table->decimal('membership_pv', 12, 2)->nullable();
            }
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE pv_ledger MODIFY source_type ENUM('own_purchase', 'referred_client_purchase', 'sponsorship', 'membership') NOT NULL");
        }
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['membership_amount_usd', 'membership_pv']);
        });
    }
};

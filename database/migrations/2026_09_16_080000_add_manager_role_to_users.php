<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('admin', 'manager', 'cashier', 'accountant') NOT NULL");

            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 32)->default('cashier')->change();
        });
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'manager')->update(['role' => 'cashier']);

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('admin', 'cashier', 'accountant') NOT NULL");

            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 32)->default('cashier')->change();
        });
    }
};

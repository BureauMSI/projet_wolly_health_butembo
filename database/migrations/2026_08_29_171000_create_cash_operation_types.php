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
        Schema::create('cash_operation_types', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code')->unique();
            $table->string('label')->nullable();
            $table->enum('direction', ['in', 'out', 'both']);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('cash_movements', function (Blueprint $table) {
            $table->foreignId('operation_type_id')->nullable()->after('category')->constrained('cash_operation_types');
        });

        $now = now();
        $types = [
            ['code' => 'sale', 'direction' => 'in', 'sort_order' => 10, 'is_system' => true],
            ['code' => 'membership', 'direction' => 'in', 'sort_order' => 20, 'is_system' => true],
            ['code' => 'commission', 'direction' => 'out', 'sort_order' => 30, 'is_system' => true],
            ['code' => 'capital', 'direction' => 'in', 'sort_order' => 40, 'is_system' => false],
            ['code' => 'donation', 'direction' => 'in', 'sort_order' => 50, 'is_system' => false],
            ['code' => 'transfer_in', 'direction' => 'in', 'sort_order' => 60, 'is_system' => false],
            ['code' => 'other_income', 'direction' => 'in', 'sort_order' => 70, 'is_system' => false],
            ['code' => 'charge', 'direction' => 'out', 'sort_order' => 80, 'is_system' => false],
            ['code' => 'rent', 'direction' => 'out', 'sort_order' => 90, 'is_system' => false],
            ['code' => 'salary', 'direction' => 'out', 'sort_order' => 100, 'is_system' => false],
            ['code' => 'utilities', 'direction' => 'out', 'sort_order' => 110, 'is_system' => false],
            ['code' => 'supplies', 'direction' => 'out', 'sort_order' => 120, 'is_system' => false],
            ['code' => 'transport', 'direction' => 'out', 'sort_order' => 130, 'is_system' => false],
            ['code' => 'transfer_out', 'direction' => 'out', 'sort_order' => 140, 'is_system' => false],
            ['code' => 'other_expense', 'direction' => 'out', 'sort_order' => 150, 'is_system' => false],
            ['code' => 'other', 'direction' => 'both', 'sort_order' => 160, 'is_system' => false],
        ];

        foreach ($types as $type) {
            DB::table('cash_operation_types')->insert($type + [
                'uuid' => (string) Str::uuid(),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $ids = DB::table('cash_operation_types')->pluck('id', 'code');
        foreach ($ids as $code => $id) {
            DB::table('cash_movements')->where('category', $code)->update(['operation_type_id' => $id]);
        }
    }

    public function down(): void
    {
        Schema::table('cash_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('operation_type_id');
        });
        Schema::dropIfExists('cash_operation_types');
    }
};

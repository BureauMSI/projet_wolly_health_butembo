<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('members')) {
            return;
        }

        $indexName = 'members_placement_parent_id_placement_side_unique';
        $exists = collect(Schema::getIndexes('members'))->contains(function (array $index) use ($indexName) {
            return ($index['name'] ?? '') === $indexName
                || (($index['unique'] ?? false) && ($index['columns'] ?? []) === ['placement_parent_id', 'placement_side']);
        });

        if ($exists) {
            Schema::table('members', function (Blueprint $table) use ($indexName) {
                $table->dropUnique($indexName);
            });
        }
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->unique(['placement_parent_id', 'placement_side']);
        });
    }
};

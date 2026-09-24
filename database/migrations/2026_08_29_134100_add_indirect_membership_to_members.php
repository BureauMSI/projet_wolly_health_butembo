<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            if (! Schema::hasColumn('members', 'membership_type')) {
                $table->string('membership_type', 16)->default('direct');
            }
            if (! Schema::hasColumn('members', 'source_client_id')) {
                $table->foreignId('source_client_id')->nullable()->constrained('clients');
            }
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            if (Schema::hasColumn('members', 'source_client_id')) {
                $table->dropConstrainedForeignId('source_client_id');
            }
            if (Schema::hasColumn('members', 'membership_type')) {
                $table->dropColumn('membership_type');
            }
        });
    }
};

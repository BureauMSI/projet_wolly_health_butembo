<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_codes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('origin_device_id')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('branch_id')->constrained('branches');
            $table->string('code', 32)->unique();
            $table->enum('status', ['available', 'used', 'revoked'])->default('available');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('used_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('used_by_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_codes');
    }
};

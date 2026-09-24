<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institutions', function (Blueprint $table) {
            $this->syncable($table);
            $table->string('name');
            $table->string('acronym')->nullable();
            $table->string('slogan')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->default('RDC');
            $table->string('rccm')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('id_nat')->nullable();
            $table->text('invoice_footer')->nullable();
            $table->string('default_locale', 8)->default('fr');
            $table->string('default_currency_code', 3)->default('USD');
        });

        Schema::create('currencies', function (Blueprint $table) {
            $table->string('code', 3)->primary();
            $table->string('name');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('branches', function (Blueprint $table) {
            $this->syncable($table);
            $table->foreignId('institution_id')->constrained('institutions');
            $table->string('name');
            $table->string('code')->unique();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
        });

        Schema::create('users', function (Blueprint $table) {
            $this->syncable($table);
            $table->string('name');
            $table->string('username')->unique();
            $table->string('email')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', ['admin', 'manager', 'cashier', 'accountant']);
            $table->foreignId('branch_id')->nullable()->constrained('branches');
            $table->string('locale', 8)->default('fr');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
        });

        Schema::create('exchange_rates', function (Blueprint $table) {
            $this->syncable($table);
            $table->string('currency_code', 3);
            $table->decimal('rate_to_usd', 18, 8);
            $table->timestamp('effective_at');
            $table->foreign('currency_code')->references('code')->on('currencies');
        });

        Schema::create('members', function (Blueprint $table) {
            $this->syncable($table);
            $table->string('member_code')->unique();
            $table->string('full_name');
            $table->string('gender', 16)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('id_document_path')->nullable();
            $table->string('username')->unique();
            $table->string('password');
            $table->foreignId('sponsor_id')->nullable()->constrained('members');
            $table->foreignId('placement_parent_id')->nullable()->constrained('members');
            $table->enum('placement_side', ['left', 'right'])->nullable();
            $table->foreignId('registration_branch_id')->nullable()->constrained('branches');
            $table->string('locale', 8)->default('fr');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamp('joined_at')->nullable();
            $table->index('placement_parent_id');
        });

        Schema::create('clients', function (Blueprint $table) {
            $this->syncable($table);
            $table->string('name');
            $table->string('phone')->nullable();
            $table->foreignId('referrer_member_id')->constrained('members');
            $table->decimal('accumulated_pv', 12, 2)->default(0);
            $table->timestamp('threshold_alerted_at')->nullable();
            $table->foreignId('converted_member_id')->nullable()->constrained('members');
        });

        Schema::create('products', function (Blueprint $table) {
            $this->syncable($table);
            $table->string('code')->unique();
            $table->string('name');
            $table->decimal('unit_price_usd', 14, 2);
            $table->decimal('pv_per_tablet', 12, 2);
            $table->decimal('box_price_usd', 14, 2);
            $table->decimal('box_pv', 12, 2);
            $table->boolean('is_active')->default(true);
        });

        Schema::create('plan_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('reward_tiers', function (Blueprint $table) {
            $this->syncable($table);
            $table->decimal('min_pv', 12, 2);
            $table->decimal('max_pv', 12, 2)->nullable();
            $table->decimal('amount_usd', 14, 2)->nullable();
            $table->string('label');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
        });

        Schema::create('equilibrium_rules', function (Blueprint $table) {
            $this->syncable($table);
            $table->enum('scope', ['generations_1_4', 'after_generation_4']);
            $table->unsignedTinyInteger('generation')->nullable();
            $table->decimal('amount_usd', 14, 2)->nullable();
            $table->decimal('percent', 8, 4)->nullable();
            $table->decimal('min_leg_pv', 12, 2)->nullable();
            $table->boolean('is_active')->default(true);
        });

        Schema::create('sales', function (Blueprint $table) {
            $this->syncable($table);
            $table->string('number')->unique();
            $table->foreignId('branch_id')->constrained('branches');
            $table->foreignId('user_id')->constrained('users');
            $table->enum('buyer_type', ['member', 'client']);
            $table->foreignId('member_id')->nullable()->constrained('members');
            $table->foreignId('client_id')->nullable()->constrained('clients');
            $table->string('currency_code', 3);
            $table->decimal('rate_to_usd', 18, 8)->default(1);
            $table->decimal('subtotal_usd', 14, 2);
            $table->decimal('discount_usd', 14, 2)->default(0);
            $table->decimal('promo_usd', 14, 2)->default(0);
            $table->decimal('total_usd', 14, 2);
            $table->timestamp('sold_at');
            $table->enum('sync_status', ['pending', 'confirmed', 'rejected'])->default('pending');
            $table->foreign('currency_code')->references('code')->on('currencies');
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $this->syncable($table);
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->enum('packing', ['tablet', 'box']);
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price_usd', 14, 2);
            $table->decimal('pv', 12, 2);
            $table->decimal('line_total_usd', 14, 2);
        });

        Schema::create('pv_ledger', function (Blueprint $table) {
            $this->syncable($table);
            $table->foreignId('member_id')->constrained('members');
            $table->enum('source_type', ['own_purchase', 'referred_client_purchase', 'sponsorship', 'membership']);
            $table->decimal('pv_amount', 12, 2);
            $table->foreignId('sale_id')->nullable()->constrained('sales');
            $table->foreignId('client_id')->nullable()->constrained('clients');
            $table->foreignId('related_member_id')->nullable()->constrained('members');
            $table->timestamp('occurred_at');
            $table->enum('sync_status', ['pending', 'confirmed', 'rejected'])->default('pending');
        });

        Schema::create('commission_ledger', function (Blueprint $table) {
            $this->syncable($table);
            $table->foreignId('member_id')->constrained('members');
            $table->enum('type', ['sponsorship', 'equilibrium', 'reward']);
            $table->decimal('amount_usd', 14, 2);
            $table->foreignId('related_member_id')->nullable()->constrained('members');
            $table->unsignedTinyInteger('generation')->nullable();
            $table->foreignId('reward_tier_id')->nullable()->constrained('reward_tiers');
            $table->enum('status', ['pending', 'confirmed', 'paid'])->default('pending');
            $table->timestamp('occurred_at');
        });

        Schema::create('cash_movements', function (Blueprint $table) {
            $this->syncable($table);
            $table->foreignId('branch_id')->constrained('branches');
            $table->enum('direction', ['in', 'out']);
            $table->string('category');
            $table->decimal('amount', 14, 2);
            $table->string('currency_code', 3);
            $table->decimal('rate_to_usd', 18, 8)->default(1);
            $table->decimal('amount_usd', 14, 2);
            $table->foreignId('sale_id')->nullable()->constrained('sales');
            $table->foreignId('commission_id')->nullable()->constrained('commission_ledger');
            $table->text('description')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->timestamp('occurred_at');
            $table->foreign('currency_code')->references('code')->on('currencies');
        });

        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->string('locale', 8);
            $table->text('body');
            $table->timestamps();
            $table->unique(['key', 'locale']);
        });

        Schema::create('whatsapp_outbox', function (Blueprint $table) {
            $this->syncable($table);
            $table->string('to_phone');
            $table->string('template_key');
            $table->string('locale', 8);
            $table->json('payload_json')->nullable();
            $table->text('body_rendered');
            $table->enum('driver', ['wa_me', 'api'])->default('wa_me');
            $table->enum('status', ['pending', 'opened', 'failed'])->default('pending');
        });

        Schema::create('sync_outbox', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type');
            $table->uuid('entity_uuid');
            $table->enum('operation', ['create', 'update', 'delete']);
            $table->json('payload_json')->nullable();
            $table->string('origin_device_id')->nullable();
            $table->timestamp('queued_at');
            $table->timestamp('synced_at')->nullable();
            $table->text('last_error')->nullable();
            $table->index(['entity_type', 'entity_uuid']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('action');
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('sync_outbox');
        Schema::dropIfExists('whatsapp_outbox');
        Schema::dropIfExists('whatsapp_templates');
        Schema::dropIfExists('cash_movements');
        Schema::dropIfExists('commission_ledger');
        Schema::dropIfExists('pv_ledger');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('equilibrium_rules');
        Schema::dropIfExists('reward_tiers');
        Schema::dropIfExists('plan_settings');
        Schema::dropIfExists('products');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('members');
        Schema::dropIfExists('exchange_rates');
        Schema::dropIfExists('users');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('currencies');
        Schema::dropIfExists('institutions');
    }

    private function syncable(Blueprint $table): void
    {
        $table->id();
        $table->uuid('uuid')->unique();
        $table->string('origin_device_id')->nullable();
        $table->unsignedInteger('version')->default(1);
        $table->timestamps();
        $table->softDeletes();
    }
};

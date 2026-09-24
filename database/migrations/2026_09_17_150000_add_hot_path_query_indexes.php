<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->index(['branch_id', 'sold_at'], 'sales_branch_sold_at_index');
        });

        Schema::table('pv_ledger', function (Blueprint $table) {
            $table->index(['member_id', 'sync_status'], 'pv_ledger_member_sync_index');
            $table->index(['member_id', 'occurred_at'], 'pv_ledger_member_occurred_index');
        });

        Schema::table('commission_ledger', function (Blueprint $table) {
            $table->index(['member_id', 'status'], 'commission_ledger_member_status_index');
            $table->index(['member_id', 'occurred_at'], 'commission_ledger_member_occurred_index');
            $table->index(['status', 'occurred_at'], 'commission_ledger_status_occurred_index');
        });

        Schema::table('cash_movements', function (Blueprint $table) {
            $table->index(['branch_id', 'occurred_at'], 'cash_movements_branch_occurred_index');
        });

        Schema::table('sync_outbox', function (Blueprint $table) {
            $table->index(['synced_at', 'queued_at'], 'sync_outbox_synced_queued_index');
        });

        Schema::table('members', function (Blueprint $table) {
            $table->index(['placement_parent_id', 'placement_side'], 'members_placement_parent_side_index');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('sales_branch_sold_at_index');
        });

        Schema::table('pv_ledger', function (Blueprint $table) {
            $table->dropIndex('pv_ledger_member_sync_index');
            $table->dropIndex('pv_ledger_member_occurred_index');
        });

        Schema::table('commission_ledger', function (Blueprint $table) {
            $table->dropIndex('commission_ledger_member_status_index');
            $table->dropIndex('commission_ledger_member_occurred_index');
            $table->dropIndex('commission_ledger_status_occurred_index');
        });

        Schema::table('cash_movements', function (Blueprint $table) {
            $table->dropIndex('cash_movements_branch_occurred_index');
        });

        Schema::table('sync_outbox', function (Blueprint $table) {
            $table->dropIndex('sync_outbox_synced_queued_index');
        });

        Schema::table('members', function (Blueprint $table) {
            $table->dropIndex('members_placement_parent_side_index');
        });
    }
};

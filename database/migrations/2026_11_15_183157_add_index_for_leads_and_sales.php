<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->index(['id_branch', 'created_at'], 'idx_leads_branch_created');
            $table->index(['id_branch', 'status', 'closed_at'], 'idx_leads_branch_status_closed');
            $table->index(['created_by', 'created_at'], 'idx_leads_creator_created');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->index(['status', 'deal_at'], 'idx_sales_status_deal');
            $table->index(['id_branch', 'status', 'deal_at'], 'idx_sales_branch_status_deal');
            $table->index(['created_by', 'status', 'deal_at'], 'idx_sales_creator_status_deal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex('idx_leads_branch_created');
            $table->dropIndex('idx_leads_branch_status_closed');
            $table->dropIndex('idx_leads_creator_created');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('idx_sales_status_deal');
            $table->dropIndex('idx_sales_branch_status_deal');
            $table->dropIndex('idx_sales_creator_status_deal');
        });
    }
};

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
            $table->foreignUuid('id_customer')->after('id_branch')
                ->constrained('customers', 'id_customer')->cascadeOnDelete();
            $table->index('id_customer');
        });
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignUuid('id_customer')->after('id_branch')
                ->constrained('customers', 'id_customer')->cascadeOnDelete();
            $table->index('id_customer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['id_customer']);
            $table->dropColumn('id_customer');
        });
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['id_customer']);
            $table->dropColumn('id_customer');
        });
    }
};

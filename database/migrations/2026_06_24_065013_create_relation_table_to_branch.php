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
            $table->foreignUuid('id_branch')->nullable()->constrained('branches', 'id_branch')->cascadeOnDelete();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->foreignUuid('id_branch')->after('id_user')->nullable()->constrained('branches', 'id_branch')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['id_branch']);
            $table->dropColumn('id_branch');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['id_branch']);
            $table->dropColumn('id_branch');
        });
    }
};

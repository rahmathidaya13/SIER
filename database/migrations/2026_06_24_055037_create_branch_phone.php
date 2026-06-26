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
        Schema::create('branch_phone', function (Blueprint $table) {
            $table->uuid('branch_phone_id')->primary();
            $table->foreignUuid('id_branch')
                ->constrained('branches', 'id_branch')
                ->cascadeOnDelete();
            $table->string('phone', 13)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_phone');
    }
};

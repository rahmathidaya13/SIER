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
        Schema::create('branches', function (Blueprint $table) {
            $table->uuid('id_branch')->primary();
            $table->foreignUuid('created_by')
                ->nullable()
                ->constrained('users', 'id')
                ->onDelete('cascade');
            $table->string('branch_code', 20)->unique(); // nama cabang, contoh: Pekanbaru
            $table->string('name', 100); // nama cabang, contoh: Pekanbaru
            $table->text('address');
            $table->enum('is_active', ['active', 'inactive'])->default('active');
            $table->enum('status', ['official', 'unofficial'])->default('unofficial');
            $table->index(['id_branch', 'created_by', 'name']);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch');
    }
};

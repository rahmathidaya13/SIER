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
        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id_customer')->primary();
            $table->foreignUuid('created_by')->nullable()->constrained('users', 'id')->cascadeOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users', 'id')->cascadeOnDelete();
            $table->foreignUuid('created_of_branch')
                ->constrained('branches', 'id_branch')
                ->restrictOnDelete();
            $table->string('name')->index();
            $table->string('phone', 15)->index();
            $table->string('email', 100)->unique()->nullable();
            $table->enum('business_type', ['retail', 'fnb', 'service', 'manufacture', 'other'])
                ->default('retail');
            $table->enum('customer_type', ['individual', 'company', 'government'])
                ->default('individual');
            $table->string('address', 255);
            $table->softDeletes();
            $table->timestamps();
            $table->index(['created_of_branch', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};

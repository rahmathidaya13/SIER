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
        Schema::create('customer_memberships', function (Blueprint $table) {
            $table->uuid('id_membership')->primary();
            $table->foreignUuid('id_customer')->constrained('customers', 'id_customer')->cascadeOnDelete();
            $table->string('member_code', 30)->unique();
            $table->enum('tier', ['silver', 'gold', 'platinum'])->default('silver');
            $table->unsignedInteger('points')->default(0);
            $table->date('valid_until')->nullable(); // null = seumur hidup
            $table->enum('status', ['active', 'expired', 'suspended'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_memberships');
    }
};

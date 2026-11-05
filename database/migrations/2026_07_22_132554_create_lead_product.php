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
        Schema::create('lead_product', function (Blueprint $table) {
            $table->uuid('id_lead_product')->primary();
            $table->foreignUuid('id_lead')->constrained('leads', 'id_lead')->cascadeOnDelete();
            $table->foreignUuid('id_product')->constrained('products', 'id_product')->cascadeOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_product');
    }
};

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
        Schema::create('product_price', function (Blueprint $table) {
            $table->uuid('id_product_price')->primary();

            $table->foreignUuid('id_product')
                ->constrained('products', 'id_product')
                ->cascadeOnDelete();
            $table->foreignUuid('created_by')->nullable()->constrained('users', 'id')->cascadeOnDelete();

            $table->enum('status', ['draft', 'published'])->default('published')->index();
            $table->date('valid_from')->nullable()->index(); // tanggal berlaku
            $table->date('valid_until')->nullable()->index(); // tanggal berakhir opsional

            $table->unsignedBigInteger('base_price')->default(0);
            $table->unsignedBigInteger('discount_percentage')->nullable()->default(0);
            $table->unsignedBigInteger('discount_price')->nullable()->default(0);

            $table->enum('price_type', ['normal', 'discount'])->default('normal');

            $table->index(['id_product', 'created_by']);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_price');
    }
};

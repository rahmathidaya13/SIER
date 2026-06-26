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
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id_product')->primary();

            $table->foreignUuid('created_by')
                ->nullable()
                ->constrained('users', 'id')
                ->nullOnDelete();

            $table->enum('source', ['manual', 'scrape'])->default('manual')->index();

            // nama produk, penting untuk pencarian
            $table->string('name', 100)->index();
            $table->string('slug', 100)->nullable()->index();
            $table->string('category', 100)->index();

            $table->enum('item_condition', ['new', 'used', 'refurbished', 'damaged', 'discontinued'])->default('new')->index();

            $table->text('image_path')->nullable();

            // link bersifat unik agar tidak scrape ganda
            $table->string('link', 500)->nullable();
            $table->string('image_link', 500)->nullable();

            $table->unique(['link', 'source']);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

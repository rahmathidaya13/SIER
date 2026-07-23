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
        Schema::create('sales', function (Blueprint $table) {
            $table->uuid('id_sales')->primary();
            $table->string('invoice', 30)->unique(); // Cth: INV-202606-0001

            $table->foreignUuid('id_lead')->constrained('leads', 'id_lead')->cascadeOnDelete(); // Menautkan data closing ke riwayat prospeknya
            $table->foreignUuid('id_user')->constrained('users', 'id')->cascadeOnDelete(); // CS yang berhasil closing
            // $table->foreignUuid('id_product')->constrained('products', 'id_product')->cascadeOnDelete();

            $table->unsignedBigInteger('deal_price')->default(0); // Harga deal (bisa beda dengan base_price kalau dikasih diskon)
            $table->unsignedBigInteger('paid_amount')->default(0); // Berapa duit yang udah masuk ke rekening?
            $table->unsignedBigInteger('remaining_bill')->default(0); // Berapa duit yang udah masuk ke rekening?
            $table->integer('quantity')->default(0);
            $table->enum('payment_status', ['LUNAS', 'DP'])->default('LUNAS');

            $table->dateTime('deal_at');
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};

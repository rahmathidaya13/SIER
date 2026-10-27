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
        Schema::create('payment_histories', function (Blueprint $table) {
            $table->uuid('id_payment_history')->primary();
            $table->foreignUuid('id_sales')->constrained('sales', 'id_sales')->cascadeOnDelete();
            $table->foreignUuid('created_by')->constrained('users', 'id')->cascadeOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users', 'id')->cascadeOnDelete();
            $table->foreignUuid('id_lead')->constrained('leads', 'id_lead')->cascadeOnDelete();

            $table->enum('payment_method', ['cash', 'transfer', 'debit', 'qris'])->default('cash'); // Kalau Lunas, diisi tunai]; // Cth: 'BCA - 123456', 'QRIS'
            $table->string('bank_name', 50)->nullable();
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->dateTime('payment_at');
            $table->text('notes_payment')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_histories');
    }
};

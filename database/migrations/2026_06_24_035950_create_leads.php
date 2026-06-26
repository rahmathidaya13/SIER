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
        Schema::create('leads', function (Blueprint $table) {
            $table->uuid('id_lead')->primary();
            $table->foreignUuid('id_user')->constrained('users', 'id')->onDelete('cascade'); // CS/Admin yang memegang lead ini
            $table->foreignUuid('id_channel')->constrained('channels', 'id_channel')->cascadeOnDelete(); // Datang dari mana?
            $table->foreignUuid('id_product')->nullable()->constrained('products', 'id_product')->cascadeOnDelete(); // Tertarik produk apa?

            $table->string('name', 100)->index();
            $table->string('phone', 25)->index(); // WAJIB DI-INDEX karena bakal sering dicari via form pencarian!
            $table->string('email', 100)->nullable();

            // Suhu Ketertarikan
            $table->enum('temperature', ['HOT', 'WARM', 'COLD'])->default('WARM');

            // Status Hidup Prospek
            $table->enum('status', ['NEW', 'FOLLOW_UP', 'CLOSED_WON', 'CLOSED_LOST'])->default('NEW');

            $table->date('follow_up_date')->nullable();
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
        Schema::dropIfExists('leads');
    }
};

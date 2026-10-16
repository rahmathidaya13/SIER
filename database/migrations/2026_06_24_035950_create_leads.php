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

            $table->string('name', 100);
            $table->string('phone', 25); // WAJIB DI-INDEX karena bakal sering dicari via form pencarian!
            $table->string('email', 100)->unique()->nullable();

            // Suhu Ketertarikan
            $table->enum('temperature', ['HOT', 'WARM', 'COLD'])->default('WARM');

            $table->enum('customer_type', ['NEW_CUSTOMER', 'REPEAT_ORDER', 'WIN_BACK'])->default('NEW_CUSTOMER');
            // Status Hidup Prospek
            $table->enum('status', ['NEW', 'FOLLOW_UP', 'CLOSED_WON', 'CLOSED_LOST'])->default('NEW');

            $table->timestamp('follow_up_date')->nullable();
            $table->text('notes')->nullable();
            $table->datetime('closed_at')->nullable();

            $table->timestamp('archived_at')->nullable();
            $table->foreignUuid('archived_by')->nullable()->constrained('users', 'id')->onDelete('cascade');

            $table->softDeletes();
            $table->timestamps();
            $table->index(['name', 'phone', 'closed_at', 'archived_at','created_at']);
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

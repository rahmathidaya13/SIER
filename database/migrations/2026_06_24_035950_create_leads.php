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
            $table->foreignUuid('created_by')->constrained('users', 'id')->onDelete('cascade'); // CS/Admin yang memegang lead ini
            $table->foreignUuid('updated_by')->nullable()->constrained('users', 'id')->onDelete('cascade');
            $table->foreignUuid('id_channel')->constrained('channels', 'id_channel')->cascadeOnDelete(); // Datang dari mana?


            // Suhu Ketertarikan
            $table->enum('temperature', ['HOT', 'WARM', 'COLD'])->default('WARM');

            // Status Hidup Prospek
            $table->enum('status', ['NEW', 'FOLLOW_UP', 'CLOSED_WON', 'CLOSED_LOST'])->default('NEW');

            $table->timestamp('follow_up_date')->nullable();
            $table->text('notes')->nullable();
            $table->datetime('closed_at')->nullable();

            $table->timestamp('archived_at')->nullable();
            $table->foreignUuid('archived_by')->nullable()->constrained('users', 'id')->onDelete('cascade');

            $table->softDeletes();
            $table->timestamps();
            $table->index(['closed_at', 'archived_at', 'created_at']);
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

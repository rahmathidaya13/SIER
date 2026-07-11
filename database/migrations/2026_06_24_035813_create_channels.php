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
        Schema::create('channels', function (Blueprint $table) {
            $table->uuid('id_channel')->primary();
            $table->foreignUuid('created_by')
                ->nullable()
                ->constrained('users', 'id')
                ->onDelete('cascade');
            $table->string('name', 50)->index()->unique(); // Cth: 'WhatsApp Direct', 'TikTok Ads'
            $table->string('code', 50)->unique(); // Cth: 'WA', 'TT', 'FB'
            $table->string('icon', 50); // Cth: 'bi-whatsapp'
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('channels');
    }
};

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
        Schema::create('lead_activity', function (Blueprint $table) {
            $table->uuid('id_lead_activity')->primary();
            $table->foreignUuid('id_lead')->constrained('leads', 'id_lead')->cascadeOnDelete();
            $table->foreignUuid('id_user')->constrained('users', 'id')->cascadeOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users', 'id')->cascadeOnDelete();
            $table->enum('source', ['transaction', 'created', 'updated', 'deleted'])->default('created');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_activity');
    }
};

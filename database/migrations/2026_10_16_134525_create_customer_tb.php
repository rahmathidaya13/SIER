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
        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id_customer')->primary();
            $table->foreignUuid('created_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignUuid('created_of_branch')
                ->constrained('branches', 'id_branch')
                ->restrictOnDelete();
            $table->string('customer_code', 25)->unique()->nullable();
            $table->string('name', 150)->index();
            $table->string('pic_name', 100)->nullable(); // Nama penanggung jawab jika tipe company/government
            $table->string('phone', 15)->index();
            $table->string('email', 150)->unique()->nullable();
            $table->string('tax_number', 30)->nullable(); // NPWP (Opsional)
            $table->enum('business_type', ['retail', 'fnb', 'service', 'manufacture', 'other'])
                ->default('retail');
            $table->enum('customer_type', ['individual', 'company', 'government'])
                ->default('individual')->index();
            $table->enum('customer_tag', ['new', 'repeat', 'has_cancelled'])
                ->default('new')
                ->index();
            $table->enum('status', ['active', 'inactive', 'blacklisted'])
                ->default('active')
                ->index();

            // Penanda Member Sederhana (Bisa dikombinasikan dengan tabel terpisah nantinya)
            $table->boolean('is_member')->default(false)->index();
            $table->string('city', 100)->index();
            $table->string('address', 255)->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['created_of_branch', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};

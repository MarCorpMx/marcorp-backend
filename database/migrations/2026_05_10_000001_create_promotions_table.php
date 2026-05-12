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
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('branch_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // opcional: promo a servicio en sucursal
            $table->foreignId('branch_service_id')
                ->nullable()
                ->constrained('branch_services')
                ->nullOnDelete();

            // opcional: promo a variante específica
            $table->foreignId('branch_service_variant_id')
                ->nullable()
                ->constrained('branch_service_variant')
                ->nullOnDelete();

            $table->string('name');

            $table->enum('discount_type', [
                'fixed',
                'percentage'
            ]);

            $table->decimal('discount_value', 10, 2);

            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};

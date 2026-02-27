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
        Schema::create('service_variants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('service_id')
                ->constrained()
                ->onDelete('cascade');

            $table->string('name'); // Individual, Grupal, 90 min, etc

            $table->unsignedInteger('duration_minutes');

            $table->decimal('price', 10, 2)->nullable();

            $table->unsignedInteger('max_capacity')->default(1);

            $table->enum('mode', ['online', 'presential', 'hybrid'])
                ->default('presential');

            $table->boolean('includes_material')->default(false);

            $table->boolean('active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_variants');
    }
};

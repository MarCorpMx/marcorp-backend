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
        Schema::create('service_variant_staff', function (Blueprint $table) {
            $table->id();

            $table->foreignId('service_variant_id')
                ->constrained()
                ->onDelete('cascade');

            $table->foreignId('staff_id')
                ->constrained('staff_members')
                ->onDelete('cascade');

            $table->timestamps();

            $table->unique(['service_variant_id', 'staff_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_variant_staff');
    }
};

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
                ->cascadeOnDelete();

            $table->foreignId('staff_member_id') 
                ->constrained('staff_members')
                ->cascadeOnDelete();

            $table->foreignId('branch_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(
                ['service_variant_id', 'staff_member_id', 'branch_id'], 
                'svs_variant_staff_branch_unique'
            );
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

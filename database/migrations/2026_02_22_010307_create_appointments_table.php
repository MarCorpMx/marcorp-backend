<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')->unique();

            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            
            //$table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_variant_id')->constrained()->cascadeOnDelete();

            $table->foreignId('staff_member_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();

            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime');

            $table->unsignedInteger('capacity_reserved')->default(1);

            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'no_show'])
                ->default('confirmed');

            $table->enum('source', ['public_web', 'admin_panel'])
                ->default('public_web');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['organization_id', 'start_datetime']);
            $table->index(['staff_member_id', 'start_datetime']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};

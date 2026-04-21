<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blocked_slots', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('branch_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('staff_member_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();

            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime');

            $table->string('reason')->nullable();

            $table->timestamps();

            // ÍNDICES CLAVE
            $table->index(['staff_member_id', 'branch_id', 'start_datetime']);
            $table->index(['branch_id', 'start_datetime']);
            $table->index(['branch_id', 'start_datetime', 'end_datetime']);

            // OPCIONAL
            // $table->check('end_datetime > start_datetime');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blocked_slots');
    }
};

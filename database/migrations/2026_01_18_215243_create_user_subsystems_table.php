<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_subsystems', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('subsystem_id')
                ->constrained('subsystems')
                ->cascadeOnDelete();

            $table->boolean('is_paid')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'subsystem_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_subsystems');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_subsystem_roles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_subsystem_id')
                ->constrained('user_subsystems')
                ->cascadeOnDelete();

            $table->foreignId('role_id')
                ->constrained('roles')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['user_subsystem_id', 'role_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_subsystem_roles');
    }
};

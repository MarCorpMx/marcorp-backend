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

            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('subsystem_id')
                ->constrained('subsystems')
                ->cascadeOnDelete();

            $table->foreignId('role_id')
                ->constrained('roles')
                ->cascadeOnDelete();

            $table->timestamps();

            // Un usuario no puede tener el mismo rol
            // dos veces en el mismo subsistema y organización
            $table->unique(
                ['organization_id', 'user_id', 'subsystem_id', 'role_id'],
                'usr_subsys_roles_org_user_subsys_role_uq'
            );
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_subsystem_roles');
    }
};

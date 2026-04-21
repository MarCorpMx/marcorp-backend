<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {

            $table->string('website', 255)->nullable()->after('email');

            $table->string('country', 2)->nullable()->after('website'); // Para guardar MX, US, CO, AR...
            $table->string('state', 100)->nullable()->after('country');
            $table->string('city', 100)->nullable()->after('state');
            $table->string('zip_code', 20)->nullable()->after('city');
            $table->string('address', 255)->nullable()->after('zip_code');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {

            $table->dropColumn([
                'website',
                'country',
                'state',
                'city',
                'zip_code',
                'address'
            ]);

        });
    }
};
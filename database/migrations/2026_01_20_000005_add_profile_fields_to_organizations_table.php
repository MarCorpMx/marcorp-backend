<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {

            // ========================
            // 🌐 CONTACTO GENERAL
            // ========================
            $table->string('website', 255)->nullable()->after('email');

            // ========================
            // 📍 DIRECCIÓN (FISCAL)
            // ========================
            $table->string('country', 2)->nullable()->after('website'); // MX, US, etc
            $table->string('state', 100)->nullable()->after('country');
            $table->string('city', 100)->nullable()->after('state');
            $table->string('zip_code', 20)->nullable()->after('city');
            $table->string('address', 255)->nullable()->after('zip_code');

            // ========================
            // 🧾 FACTURACIÓN (SAT)
            // ========================
            $table->string('legal_name', 255)->nullable()->after('address'); // Razón social
            $table->string('tax_id', 20)->nullable()->after('legal_name'); // RFC

            $table->string('tax_regime', 10)->nullable()->after('tax_id');
            // Ej: 601, 626, etc

            $table->string('invoice_zip_code', 10)->nullable()->after('tax_regime');
            // Código postal fiscal (MUY importante para CFDI)

            $table->string('cfdi_email', 150)->nullable()->after('invoice_zip_code');
            // Email para recibir facturas

        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {

            $table->dropColumn([
                // contacto
                'website',

                // dirección
                'country',
                'state',
                'city',
                'zip_code',
                'address',

                // facturación
                'legal_name',
                'tax_id',
                'tax_regime',
                'invoice_zip_code',
                'cfdi_email',
            ]);
        });
    }
};

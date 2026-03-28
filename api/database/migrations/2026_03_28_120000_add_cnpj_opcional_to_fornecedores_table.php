<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CNPJ adicional quando o campo principal cpfcnpj contém CPF (11 dígitos).
     * Permite escolher CPF ou CNPJ na transferência XGate.
     */
    public function up(): void
    {
        Schema::table('fornecedores', function (Blueprint $table) {
            $table->string('cnpj', 20)->nullable()->after('cpfcnpj');
        });
    }

    public function down(): void
    {
        Schema::table('fornecedores', function (Blueprint $table) {
            $table->dropColumn('cnpj');
        });
    }
};

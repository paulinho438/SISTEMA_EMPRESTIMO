<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * O CPF/CNPJ deve ser único por empresa (company_id), não globalmente no projeto.
     */
    public function up(): void
    {
        Schema::table('fornecedores', function (Blueprint $table) {
            $table->dropUnique(['cpfcnpj']);
            $table->index(['company_id', 'cpfcnpj']);
        });
    }

    public function down(): void
    {
        Schema::table('fornecedores', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'cpfcnpj']);
            $table->unique('cpfcnpj');
        });
    }
};

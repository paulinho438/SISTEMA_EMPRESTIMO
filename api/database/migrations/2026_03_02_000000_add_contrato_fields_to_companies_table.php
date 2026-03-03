<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('razao_social', 255)->nullable()->after('company');
            $table->string('cnpj', 20)->nullable()->after('razao_social');
            $table->string('endereco', 500)->nullable()->after('cnpj');
            $table->string('cidade', 100)->nullable()->after('endereco');
            $table->string('estado', 2)->nullable()->after('cidade');
            $table->string('cep', 10)->nullable()->after('estado');
            $table->string('representante_nome', 200)->nullable()->after('cep');
            $table->string('representante_cpf', 14)->nullable()->after('representante_nome');
            $table->string('representante_rg', 20)->nullable()->after('representante_cpf');
            $table->string('representante_orgao_emissor', 50)->nullable()->after('representante_rg');
            $table->string('representante_cargo', 100)->nullable()->after('representante_orgao_emissor');
            $table->string('banco_nome', 100)->nullable()->after('representante_cargo');
            $table->string('banco_agencia', 20)->nullable()->after('banco_nome');
            $table->string('banco_conta', 30)->nullable()->after('banco_agencia');
            $table->string('banco_pix', 100)->nullable()->after('banco_conta');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'razao_social', 'cnpj', 'endereco', 'cidade', 'estado', 'cep',
                'representante_nome', 'representante_cpf', 'representante_rg',
                'representante_orgao_emissor', 'representante_cargo',
                'banco_nome', 'banco_agencia', 'banco_conta', 'banco_pix',
            ]);
        });
    }
};

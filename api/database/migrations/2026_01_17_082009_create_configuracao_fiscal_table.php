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
        Schema::create('configuracao_fiscal', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->foreign('company_id')->references('id')->on('companies');
            
            $table->decimal('percentual_presuncao', 5, 2)->default(32.00)->comment('Percentual de presunção de lucro (8%, 16%, 32%, etc.)');
            $table->decimal('aliquota_irpj', 5, 2)->default(15.00)->comment('Alíquota IRPJ normal');
            $table->decimal('aliquota_irpj_adicional', 5, 2)->default(10.00)->comment('Alíquota IRPJ adicional sobre excedente');
            $table->decimal('aliquota_csll', 5, 2)->default(9.00)->comment('Alíquota CSLL');
            $table->decimal('faixa_isencao_irpj', 10, 2)->default(20000.00)->comment('Faixa de isenção para IRPJ adicional (mensal)');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('configuracao_fiscal');
    }
};

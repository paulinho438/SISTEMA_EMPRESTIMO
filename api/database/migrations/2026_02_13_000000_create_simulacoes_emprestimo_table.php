<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('simulacoes_emprestimo', function (Blueprint $table) {
            $table->id();
            $table->decimal('valor_solicitado', 14, 2);
            $table->string('periodo_amortizacao', 20);
            $table->string('modelo_amortizacao', 20)->default('price');
            $table->unsignedSmallInteger('quantidade_parcelas');
            $table->decimal('taxa_juros_mensal', 10, 6);
            $table->date('data_assinatura');
            $table->date('data_primeira_parcela');
            $table->boolean('simples_nacional')->default(false);
            $table->boolean('calcular_iof')->default(true);
            $table->json('garantias')->nullable();

            $table->decimal('iof_adicional', 14, 2)->default(0);
            $table->decimal('iof_diario', 14, 2)->default(0);
            $table->decimal('iof_total', 14, 2)->default(0);
            $table->decimal('valor_contrato', 14, 2);
            $table->decimal('parcela', 14, 2);
            $table->decimal('total_parcelas', 14, 2);
            $table->decimal('cet_mes', 10, 4)->nullable();
            $table->decimal('cet_ano', 10, 4)->nullable();
            $table->json('cronograma');

            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('banco_id')->nullable();
            $table->unsignedBigInteger('costcenter_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('company_id');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('company');
        });
    }

    public function down()
    {
        Schema::dropIfExists('simulacoes_emprestimo');
    }
};

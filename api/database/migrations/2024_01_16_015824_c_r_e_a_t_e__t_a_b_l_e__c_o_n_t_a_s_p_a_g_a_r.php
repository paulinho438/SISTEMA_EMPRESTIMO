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
        Schema::create('contaspagar', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('emprestimo_id')->nullable();
            $table->foreign('emprestimo_id')->references('id')->on('emprestimos');

            $table->unsignedBigInteger('fornecedor_id')->nullable();
            $table->foreign('fornecedor_id')->references('id')->on('fornecedores');

            $table->unsignedBigInteger('costcenter_id');
            $table->foreign('costcenter_id')->references('id')->on('costcenter');

            $table->unsignedBigInteger('company_id');
            $table->foreign('company_id')->references('id')->on('companies');

            $table->unsignedBigInteger('banco_id')->nullable();
            $table->foreign('banco_id')->references('id')->on('bancos');

            $table->date('dt_baixa')->nullable();

            $table->string('status');
            $table->string('tipodoc');
            $table->string('descricao');
            $table->date('lanc');
            $table->date('venc');
            $table->float('valor', 8, 2);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contaspagar');
    }
};

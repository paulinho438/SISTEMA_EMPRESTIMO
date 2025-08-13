<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDepositoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('depositos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('banco_id');
            $table->foreign('banco_id')->references('id')->on('bancos');
            $table->unsignedBigInteger('company_id');
            $table->foreign('company_id')->references('id')->on('companies');
            $table->float('valor', 8, 2);
            $table->string('identificador')->nullable();
            $table->date('data_pagamento')->nullable();
            $table->string('chave_pix', 255)->nullable();
            $table->timestamps(); // Adiciona colunas created_at e updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('deposito');
    }
}

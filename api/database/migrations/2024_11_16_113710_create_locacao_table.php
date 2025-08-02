<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLocacaoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('locacao', function (Blueprint $table) {
            $table->string('id')->primary(); // Campo id do tipo hash
            $table->timestamps(); // Campos created_at e updated_at
            $table->string('type');
            $table->string('chave_pix', 255)->nullable();
            $table->string('identificador')->nullable();
            $table->date('data_vencimento');
            $table->date('data_pagamento')->nullable();
            $table->decimal('valor', 10, 2);
            $table->unsignedBigInteger('company_id');
            $table->foreign('company_id')->references('id')->on('companies');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('locacao');
    }
}

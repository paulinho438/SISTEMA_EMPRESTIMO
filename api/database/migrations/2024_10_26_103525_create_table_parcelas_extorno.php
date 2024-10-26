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
        Schema::create('parcela_extorno', function (Blueprint $table) {
            $table->id();
            $table->string('hash_extorno');
            $table->unsignedBigInteger('parcela_id');
            $table->foreign('parcela_id')->references('id')->on('parcelas');
            $table->string('parcela');
            $table->float('valor', 8, 2);
            $table->float('saldo', 8, 2);
            $table->date('venc');
            $table->date('venc_real');
            $table->date('dt_lancamento');
            $table->date('dt_baixa')->nullable();
            $table->string('identificador')->nullable();
            $table->string('chave_pix')->nullable();
            $table->float('valor_recebido', 8, 2)->nullable();
            $table->integer('atrasadas')->default(0)->nullable();
            $table->date('dt_ult_cobranca')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('parcelas');
    }
};

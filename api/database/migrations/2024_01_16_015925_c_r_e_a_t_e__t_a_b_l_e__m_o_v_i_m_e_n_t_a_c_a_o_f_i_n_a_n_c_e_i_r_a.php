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
        Schema::create('movimentacaofinanceira', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('banco_id');
            $table->foreign('banco_id')->references('id')->on('bancos');

            $table->string('descricao');
            $table->string('tipomov');
            $table->date('dt_movimentacao');
            $table->float('valor', 8, 2);

            $table->unsignedBigInteger('company_id');
            $table->foreign('company_id')->references('id')->on('company');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('movimentacaofinanceira');
    }
};

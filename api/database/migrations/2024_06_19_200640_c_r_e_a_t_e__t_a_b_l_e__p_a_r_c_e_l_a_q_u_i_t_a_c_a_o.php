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
        Schema::create('quitacao', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('emprestimo_id');
            $table->foreign('emprestimo_id')->references('id')->on('emprestimos');
            $table->float('valor', 8, 2);
            $table->float('saldo', 8, 2);
            $table->date('dt_baixa')->nullable();
            $table->string('identificador')->nullable();
            $table->string('chave_pix')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('quitacao');
    }
};

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
        Schema::table('movimentacaofinanceira', function (Blueprint $table) {
            $table->unsignedBigInteger('parcela_id')->nullable();
            $table->foreign('parcela_id')->references('id')->on('parcelas');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('movimentacaofinanceira', function (Blueprint $table) {
            $table->dropColumn('parcela_id'); // Revertendo a alteração, removendo a coluna status
            $table->dropColumn('whatsapp'); // Revertendo a alteração, removendo a coluna status
        });
    }
};

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
        Schema::table('parcelas', function (Blueprint $table) {
            $table->date('dt_ult_cobranca')->nullable(); // Adicionando a coluna status com o valor padrão 'Pendente'
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('parcelas', function (Blueprint $table) {
            $table->dropColumn('dt_ult_cobranca'); // Revertendo a alteração, removendo a coluna status
        });
    }
};

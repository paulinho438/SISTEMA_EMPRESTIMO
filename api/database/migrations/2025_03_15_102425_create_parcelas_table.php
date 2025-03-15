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
            $table->timestamp('ult_dt_geracao_pix')->nullable();
        });

        Schema::table('quitacao', function (Blueprint $table) {
            $table->timestamp('ult_dt_geracao_pix')->nullable();
        });

        Schema::table('pagamento_saldo_pendente', function (Blueprint $table) {
            $table->timestamp('ult_dt_geracao_pix')->nullable();
        });

        Schema::table('pagamento_personalizado', function (Blueprint $table) {
            $table->timestamp('ult_dt_geracao_pix')->nullable();
        });

        Schema::table('pagamento_minimo', function (Blueprint $table) {
            $table->timestamp('ult_dt_geracao_pix')->nullable();
        });

        Schema::table('locacao', function (Blueprint $table) {
            $table->timestamp('ult_dt_geracao_pix')->nullable();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
};

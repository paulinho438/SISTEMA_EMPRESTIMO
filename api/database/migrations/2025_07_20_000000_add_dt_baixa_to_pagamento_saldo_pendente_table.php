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
        Schema::table('pagamento_saldo_pendente', function (Blueprint $table) {
            $table->date('dt_baixa')->nullable()->after('chave_pix');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pagamento_saldo_pendente', function (Blueprint $table) {
            $table->dropColumn('dt_baixa');
        });
    }
};


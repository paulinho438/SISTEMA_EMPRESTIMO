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
            $table->string('chave_pix', 255)->change();
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
            // Reverter a alteração, se necessário
            $table->string('chave_pix')->change();
        });
    }
};

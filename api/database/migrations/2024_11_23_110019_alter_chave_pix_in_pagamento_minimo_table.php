<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterChavePixInPagamentoMinimoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pagamento_minimo', function (Blueprint $table) {
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
        Schema::table('pagamento_minimo', function (Blueprint $table) {
            $table->string('chave_pix', 100)->change();
        });
    }
}

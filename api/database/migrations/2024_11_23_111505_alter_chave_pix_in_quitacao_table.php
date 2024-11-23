<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterChavePixInQuitacaoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quitacao', function (Blueprint $table) {
            $table->string('chave_pix', 255)->change(); // Aumenta o tamanho da coluna chave_pix para 255 caracteres
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('quitacao', function (Blueprint $table) {
            $table->string('chave_pix', 100)->change(); // Reverte o tamanho da coluna chave_pix para 100 caracteres (ou o tamanho original)
        });
    }
}

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
        Schema::table('parcela_extorno', function (Blueprint $table) {
            $table->float('valor_recebido', 8, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('parcela_extorno', function (Blueprint $table) {
            $table->dropColumn('valor_recebido'); // Revertendo a alteração, removendo a coluna status
        });
    }
};



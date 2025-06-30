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
        Schema::table('webhook_cobranca', function (Blueprint $table) {
            $table->string('identificador')->nullable();
            $table->string('qt_identificadores')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('webhook_cobranca', function (Blueprint $table) {
            $table->dropColumn('identificador');
            $table->dropColumn('qt_identificadores');
        });
    }
};

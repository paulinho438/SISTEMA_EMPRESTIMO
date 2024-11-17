<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableBancos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bancos', function (Blueprint $table) {
            $table->dropColumn(['clienteid', 'clientesecret', 'certificado']);
            $table->string('document')->nullable();
            $table->boolean('wallet')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bancos', function (Blueprint $table) {
            $table->string('clienteid')->nullable();
            $table->string('clientesecret')->nullable();
            $table->string('certificado')->nullable();
            $table->string('wallet')->nullable();
            $table->dropColumn('document');
            $table->dropColumn('wallet');
        });
    }
}

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
        Schema::table('bancos', function (Blueprint $table) {
            $table->string('clienteid', 200)->nullable();
            $table->string('clientesecret', 200)->nullable();
            $table->float('juros', 8, 2)->nullable();
            $table->string('certificado', 200)->nullable();
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
            $table->dropColumn('clienteid'); // Revertendo a alteração, removendo a coluna status
            $table->dropColumn('clientesecret'); // Revertendo a alteração, removendo a coluna status
            $table->dropColumn('juros'); // Revertendo a alteração, removendo a coluna status
            $table->dropColumn('certificado'); // Revertendo a alteração, removendo a coluna status
        });
    }
};

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
            $table->decimal('lucro_real', 10, 2)->default(0)->after('valor')->comment('Lucro real da parcela (inclui multas/juros quando aplicado)');
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
            $table->dropColumn('lucro_real');
        });
    }
};


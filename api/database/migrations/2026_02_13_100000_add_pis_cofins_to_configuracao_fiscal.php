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
        Schema::table('configuracao_fiscal', function (Blueprint $table) {
            $table->decimal('aliquota_pis', 5, 2)->default(0.65)->after('aliquota_csll')->comment('Alíquota PIS sobre receita');
            $table->decimal('aliquota_cofins', 5, 2)->default(3.00)->after('aliquota_pis')->comment('Alíquota COFINS sobre receita');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('configuracao_fiscal', function (Blueprint $table) {
            $table->dropColumn(['aliquota_pis', 'aliquota_cofins']);
        });
    }
};

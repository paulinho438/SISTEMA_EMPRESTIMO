<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('razao_social', 200)->nullable()->after('nome_completo');
            $table->string('nome_fantasia', 200)->nullable()->after('razao_social');
            $table->string('orgao_emissor_rg', 50)->nullable()->after('rg');
            $table->string('estado_civil', 50)->nullable()->after('sexo');
            $table->string('regime_bens', 100)->nullable()->after('estado_civil');
            $table->decimal('renda_mensal', 14, 2)->nullable()->after('limit');
        });
    }

    public function down()
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'razao_social',
                'nome_fantasia',
                'orgao_emissor_rg',
                'estado_civil',
                'regime_bens',
                'renda_mensal',
            ]);
        });
    }
};

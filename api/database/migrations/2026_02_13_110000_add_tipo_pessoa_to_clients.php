<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('tipo_pessoa', 2)->default('PF')->after('id');
        });

        // Atualizar registros existentes: se cnpj preenchido -> PJ, senão -> PF
        DB::table('clients')->whereNotNull('cnpj')->where('cnpj', '!=', '')->update(['tipo_pessoa' => 'PJ']);
    }

    public function down()
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('tipo_pessoa');
        });
    }
};

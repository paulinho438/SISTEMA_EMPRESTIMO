<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('simulacoes_emprestimo', function (Blueprint $table) {
            $table->string('situacao', 30)->default('em_preenchimento')->after('company_id');
        });
    }

    public function down()
    {
        Schema::table('simulacoes_emprestimo', function (Blueprint $table) {
            $table->dropColumn('situacao');
        });
    }
};

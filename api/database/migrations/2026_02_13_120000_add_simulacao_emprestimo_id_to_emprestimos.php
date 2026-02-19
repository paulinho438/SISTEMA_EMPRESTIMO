<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('emprestimos', function (Blueprint $table) {
            $table->unsignedBigInteger('simulacao_emprestimo_id')->nullable()->after('company_id');
            $table->index('simulacao_emprestimo_id');
            $table->foreign('simulacao_emprestimo_id')->references('id')->on('simulacoes_emprestimo');
        });
    }

    public function down()
    {
        Schema::table('emprestimos', function (Blueprint $table) {
            $table->dropForeign(['simulacao_emprestimo_id']);
            $table->dropIndex(['simulacao_emprestimo_id']);
            $table->dropColumn('simulacao_emprestimo_id');
        });
    }
};


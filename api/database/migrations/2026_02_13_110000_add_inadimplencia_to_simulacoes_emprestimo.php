<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('simulacoes_emprestimo', function (Blueprint $table) {
            $table->json('inadimplencia')->nullable()->after('garantias');
        });
    }

    public function down()
    {
        Schema::table('simulacoes_emprestimo', function (Blueprint $table) {
            $table->dropColumn('inadimplencia');
        });
    }
};


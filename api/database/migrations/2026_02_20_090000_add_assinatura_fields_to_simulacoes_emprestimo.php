<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('simulacoes_emprestimo', function (Blueprint $table) {
            $table->string('assinatura_status', 50)->nullable()->after('situacao');
            $table->unsignedInteger('assinatura_versao')->default(0)->after('assinatura_status');

            $table->timestamp('aceite_at')->nullable()->after('assinatura_versao');
            $table->timestamp('finalizado_at')->nullable()->after('aceite_at');

            $table->string('pdf_original_path')->nullable()->after('finalizado_at');
            $table->string('pdf_original_sha256', 64)->nullable()->after('pdf_original_path');
            $table->string('pdf_final_path')->nullable()->after('pdf_original_sha256');
            $table->string('pdf_final_sha256', 64)->nullable()->after('pdf_final_path');

            $table->index(['company_id', 'client_id', 'assinatura_status'], 'sim_emp_ass_status_idx');
        });
    }

    public function down()
    {
        Schema::table('simulacoes_emprestimo', function (Blueprint $table) {
            $table->dropIndex('sim_emp_ass_status_idx');
            $table->dropColumn([
                'assinatura_status',
                'assinatura_versao',
                'aceite_at',
                'finalizado_at',
                'pdf_original_path',
                'pdf_original_sha256',
                'pdf_final_path',
                'pdf_final_sha256',
            ]);
        });
    }
};


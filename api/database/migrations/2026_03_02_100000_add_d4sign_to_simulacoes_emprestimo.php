<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('simulacoes_emprestimo', function (Blueprint $table) {
            $table->string('d4sign_uuid_document', 64)->nullable()->after('pdf_final_sha256');
            $table->text('d4sign_embed_url')->nullable()->after('d4sign_uuid_document');
        });
    }

    public function down()
    {
        Schema::table('simulacoes_emprestimo', function (Blueprint $table) {
            $table->dropColumn(['d4sign_uuid_document', 'd4sign_embed_url']);
        });
    }
};

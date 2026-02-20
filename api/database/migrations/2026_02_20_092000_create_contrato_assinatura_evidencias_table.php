<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('contrato_assinatura_evidencias', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contrato_id');

            $table->string('tipo', 30); // doc_frente|doc_verso|selfie|video
            $table->string('path');
            $table->string('sha256', 64);
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('size')->nullable();

            $table->timestamp('captured_at')->nullable();
            $table->timestamp('uploaded_at')->nullable();

            $table->json('meta_json')->nullable();
            $table->timestamps();

            $table->index(['contrato_id', 'tipo'], 'ass_evid_contrato_tipo_idx');
            $table->foreign('contrato_id')->references('id')->on('simulacoes_emprestimo')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('contrato_assinatura_evidencias');
    }
};


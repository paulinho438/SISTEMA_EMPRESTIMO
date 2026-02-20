<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('contrato_assinatura_desafios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contrato_id');

            $table->string('tipo', 20)->default('video'); // video
            $table->string('desafio_texto');
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();

            $table->json('meta_json')->nullable();
            $table->timestamps();

            $table->index(['contrato_id', 'expires_at'], 'ass_des_contrato_exp_idx');
            $table->foreign('contrato_id')->references('id')->on('simulacoes_emprestimo')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('contrato_assinatura_desafios');
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('contrato_assinatura_eventos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contrato_id');

            $table->string('ator_tipo', 20); // cliente|admin|system
            $table->unsignedBigInteger('ator_id')->nullable();

            $table->string('evento_tipo', 50);
            $table->ipAddress('ip')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('device_json')->nullable();
            $table->json('meta_json')->nullable();

            $table->timestamps();

            $table->index(['contrato_id', 'created_at'], 'ass_eventos_contrato_data_idx');
            $table->foreign('contrato_id')->references('id')->on('simulacoes_emprestimo')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('contrato_assinatura_eventos');
    }
};


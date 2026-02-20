<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('contrato_assinatura_otps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contrato_id');

            $table->string('canal', 20)->default('whatsapp');
            $table->string('code_hash');
            $table->timestamp('expires_at');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('last_sent_at')->nullable();

            $table->json('meta_json')->nullable();
            $table->timestamps();

            $table->index(['contrato_id', 'expires_at'], 'ass_otp_contrato_exp_idx');
            $table->foreign('contrato_id')->references('id')->on('simulacoes_emprestimo')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('contrato_assinatura_otps');
    }
};


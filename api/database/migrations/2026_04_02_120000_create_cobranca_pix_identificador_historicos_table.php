<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cobranca_pix_identificador_historicos', function (Blueprint $table) {
            $table->id();
            $table->string('identificador', 80)->unique();
            $table->string('tipo_entidade', 40);
            $table->unsignedBigInteger('entidade_id');
            $table->unsignedBigInteger('emprestimo_id')->nullable();
            $table->unsignedBigInteger('banco_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->decimal('valor', 15, 2)->nullable();
            $table->string('reference_interno', 120)->nullable();
            $table->string('provedor', 20)->default('xgate');
            $table->timestamps();

            $table->index(['tipo_entidade', 'entidade_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cobranca_pix_identificador_historicos');
    }
};

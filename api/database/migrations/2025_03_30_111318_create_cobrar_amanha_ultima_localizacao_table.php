<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCobrarAmanhaUltimaLocalizacaoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cobrar_amanha_ultima_localizacao', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('parcela_id');
            $table->decimal('latitude', 10, 8); // Para coordenadas de latitude com precisão
            $table->decimal('longitude', 11, 8); // Para coordenadas de longitude com precisão
            $table->timestamp('timestamp')->nullable(); // Representa o horário associado
            $table->unsignedBigInteger('company_id');
            $table->timestamps(); // Adiciona created_at e updated_at


            // Foreign key constraints (opcional)
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('parcela_id')->references('id')->on('parcelas')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cobrar_amanha_ultima_localizacao');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fornecedores', function (Blueprint $table) {
            $table->id();
            $table->string('nome_completo', 150);
            $table->string('cpfcnpj', 20)->unique();
            $table->string('telefone_celular_1', 20);
            $table->string('telefone_celular_2', 20);
            $table->string('address', 200);
            $table->string('cep', 9);
            $table->string('number', 10);
            $table->string('complement', 200)->nullable();
            $table->string('neighborhood', 100);
            $table->string('city', 100);
            $table->string('observation')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('company_id');
            $table->foreign('company_id')->references('id')->on('company');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fornecedores');
    }
};

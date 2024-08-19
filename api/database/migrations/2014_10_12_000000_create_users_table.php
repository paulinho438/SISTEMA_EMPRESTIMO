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
        Schema::create('users', function(Blueprint $table) {
            $table->id();
            $table->string('nome_completo', 150);
            $table->string('cpf', 20)->unique();
            $table->string('rg', 20)->unique();
            $table->date('data_nascimento');
            $table->enum('sexo', ['M', 'F'])->comment('Valores válidos ["M","F"]');
            $table->string('telefone_celular', 20);
            $table->string('email');
            $table->enum('status', ['A', 'I'])->default('A')->comment('Valores válidos ["A","I"]');
            $table->string('status_motivo')->nullable()->comment('Indicar o motivo pelo qual o usuário foi inativo');
            $table->integer('tentativas')->default(0)->comment('Indicar a quantidade de tentativas de login incorreto');
            $table->foreignId('permgroup_id')->nullable()->default(null)->constrained();
            $table->timestamps();
            $table->softDeletes();
            $table->string('password');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};

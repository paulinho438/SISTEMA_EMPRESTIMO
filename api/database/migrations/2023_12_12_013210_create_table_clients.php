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


        Schema::create('clients', function(Blueprint $table) {
            $table->id();
            $table->string('nome_completo', 150);
            $table->string('cpf', 20)->unique();
            $table->string('rg', 20)->unique();
            $table->date('data_nascimento');
            $table->enum('sexo', ['M', 'F'])->comment('Valores válidos ["M","F"]');
            $table->string('telefone_celular_1', 20);
            $table->string('telefone_celular_2', 20);
            $table->string('email');
            $table->enum('status', ['A', 'I'])->default('A')->comment('Valores válidos ["A","I"]');
            $table->string('status_motivo')->nullable()->comment('Indicar o motivo pelo qual o usuário foi inativo');
            $table->string('observation')->nullable();
            $table->float('limit', 8, 2);
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('company_id');
            $table->foreign('company_id')->references('id')->on('company');
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
        Schema::dropIfExists('clients');
        Schema::table('clients', function (Blueprint $table) {
            if (Schema::hasColumn('clients', 'email')) {
                $table->dropUnique('clients_email_unique');
            }
        });
    }
};

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
        Schema::create('contasreceber', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parcela_id')->nullable();
            $table->foreign('parcela_id')->references('id')->on('parcelas');

            $table->unsignedBigInteger('client_id')->nullable();
            $table->foreign('client_id')->references('id')->on('clients');

            $table->unsignedBigInteger('company_id');
            $table->foreign('company_id')->references('id')->on('companies');

            $table->unsignedBigInteger('banco_id')->nullable();
            $table->foreign('banco_id')->references('id')->on('bancos');

            $table->date('dt_baixa')->nullable();

            $table->string('descricao');
            $table->string('status');
            $table->string('tipodoc');
            $table->string('forma_recebto')->nullable();
            $table->date('lanc');
            $table->date('venc');
            $table->float('valor', 8, 2);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contasreceber');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Armazena todo o payload recebido do webhook APIX para análise posterior.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('webhook_apix', function (Blueprint $table) {
            $table->id();
            $table->json('payload')->nullable()->comment('Body da requisição (JSON)');
            $table->text('raw_body')->nullable()->comment('Body bruto quando não for JSON');
            $table->json('headers')->nullable()->comment('Headers da requisição');
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('webhook_apix');
    }
};

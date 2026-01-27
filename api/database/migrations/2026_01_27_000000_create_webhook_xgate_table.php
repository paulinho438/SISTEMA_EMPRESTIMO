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
        Schema::create('webhook_xgate', function (Blueprint $table) {
            $table->id();
            $table->json('payload');
            $table->timestamps();
            $table->boolean('processado')->default(false);
            $table->string('identificador')->nullable();
            $table->string('qt_identificadores')->nullable();
            $table->float('valor')->nullable();
            $table->string('tipo_evento')->nullable(); // deposit, withdraw, etc
            $table->string('status')->nullable(); // PENDING, COMPLETED, FAILED, etc
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('webhook_xgate');
    }
};

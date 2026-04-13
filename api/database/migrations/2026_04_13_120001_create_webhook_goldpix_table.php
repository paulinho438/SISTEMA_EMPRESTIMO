<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_goldpix', function (Blueprint $table) {
            $table->id();
            $table->json('payload')->nullable();
            $table->text('raw_body')->nullable();
            $table->json('headers')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('identificador')->nullable()->comment('transaction_id GoldPix');
            $table->float('valor')->nullable();
            $table->string('tipo_evento')->nullable();
            $table->string('status')->nullable();
            $table->boolean('processado')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_goldpix');
    }
};

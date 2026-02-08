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
        Schema::create('daytrade_meta', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->decimal('capital_inicial', 15, 2)->default(100);
            $table->decimal('meta_diaria_pct', 8, 2)->default(15.9);
            $table->integer('dias')->default(50);
            $table->string('modo_lancamento', 10)->default('pct'); // 'brl' | 'pct'
            $table->string('regra_dia', 20)->default('sobre_inicial'); // 'sobre_saldo' | 'sobre_inicial'
            $table->integer('dia_atual')->default(1);
            $table->json('lancamentos')->nullable(); // array de valores por dia
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->unique('company_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('daytrade_meta');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adiciona colunas para processamento de webhooks APIX (baixa automática).
     *
     * @return void
     */
    public function up()
    {
        Schema::table('webhook_apix', function (Blueprint $table) {
            $table->string('identificador')->nullable()->after('payload');
            $table->float('valor')->nullable()->after('identificador');
            $table->string('tipo_evento')->nullable()->after('valor')->comment('Deposit, Withdraw, etc');
            $table->string('status')->nullable()->after('tipo_evento')->comment('COMPLETED, PENDING, etc');
            $table->boolean('processado')->default(false)->after('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('webhook_apix', function (Blueprint $table) {
            $table->dropColumn(['identificador', 'valor', 'tipo_evento', 'status', 'processado']);
        });
    }
};

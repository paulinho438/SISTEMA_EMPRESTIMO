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
        Schema::table('client_locations', function (Blueprint $table) {
            // Primeiro remove a foreign key se existir
            $table->dropForeign(['user_id']);

            // Renomeia a coluna
            $table->renameColumn('user_id', 'client_id');
        });

        Schema::table('client_locations', function (Blueprint $table) {
            // Recria a foreign key apontando para clients.id
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('client_locations', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->renameColumn('client_id', 'user_id');
        });

        Schema::table('client_locations', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};

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
        Schema::table('address', function (Blueprint $table) {
            $table->string('description')->nullable()->change();
            $table->string('address')->nullable()->change();
            $table->string('cep')->nullable()->change();
            $table->string('number')->nullable()->change();
            $table->string('complement')->nullable()->change();
            $table->string('neighborhood')->nullable()->change();
            $table->string('city')->nullable()->change();
            $table->decimal('latitude', 10, 7)->nullable()->change();
            $table->decimal('longitude', 10, 7)->nullable()->change();
            $table->unsignedBigInteger('client_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('address', function (Blueprint $table) {
            $table->string('description')->nullable(false)->change();
            $table->string('address')->nullable(false)->change();
            $table->string('cep')->nullable(false)->change();
            $table->string('number')->nullable(false)->change();
            $table->string('complement')->nullable(false)->change();
            $table->string('neighborhood')->nullable(false)->change();
            $table->string('city')->nullable(false)->change();
            $table->decimal('latitude', 10, 7)->nullable(false)->change();
            $table->decimal('longitude', 10, 7)->nullable(false)->change();
            $table->unsignedBigInteger('client_id')->nullable(false)->change();
        });
    }
};

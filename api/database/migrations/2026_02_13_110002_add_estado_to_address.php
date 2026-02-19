<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('address', function (Blueprint $table) {
            $table->string('estado', 2)->nullable()->after('cep');
        });
    }

    public function down()
    {
        Schema::table('address', function (Blueprint $table) {
            $table->dropColumn('estado');
        });
    }
};

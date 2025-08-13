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
        Schema::table('charges', function (Blueprint $table) {
            $table->decimal('valor_servico', 10, 2)->default(0)->after('status');
        });
    }

    public function down()
    {
        Schema::table('charges', function (Blueprint $table) {
            $table->dropColumn('valor_servico');
        });
    }

};

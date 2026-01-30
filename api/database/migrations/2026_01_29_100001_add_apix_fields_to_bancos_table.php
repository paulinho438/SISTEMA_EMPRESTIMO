<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('bancos', function (Blueprint $table) {
            $table->string('apix_base_url', 255)->nullable()->after('xgate_password');
            $table->text('apix_api_key')->nullable()->after('apix_base_url');
        });
    }

    public function down()
    {
        Schema::table('bancos', function (Blueprint $table) {
            $table->dropColumn(['apix_base_url', 'apix_api_key']);
        });
    }
};

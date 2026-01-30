<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('bancos', function (Blueprint $table) {
            $table->string('apix_client_id', 255)->nullable()->after('apix_api_key');
            $table->text('apix_client_secret')->nullable()->after('apix_client_id');
        });
    }

    public function down()
    {
        Schema::table('bancos', function (Blueprint $table) {
            $table->dropColumn(['apix_client_id', 'apix_client_secret']);
        });
    }
};

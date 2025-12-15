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
        Schema::table('bancos', function (Blueprint $table) {
            $table->enum('bank_type', ['normal', 'bcodex', 'cora'])->default('normal')->after('wallet');
            $table->string('client_id')->nullable()->after('accountId');
            $table->string('certificate_path')->nullable()->after('client_id');
            $table->string('private_key_path')->nullable()->after('certificate_path');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bancos', function (Blueprint $table) {
            $table->dropColumn(['bank_type', 'client_id', 'certificate_path', 'private_key_path']);
        });
    }
};

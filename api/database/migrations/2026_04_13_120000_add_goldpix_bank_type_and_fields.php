<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `bancos` MODIFY COLUMN `bank_type` ENUM('normal', 'bcodex', 'cora', 'velana', 'xgate', 'apix', 'goldpix') DEFAULT 'normal'");

        Schema::table('bancos', function (Blueprint $table) {
            $table->text('goldpix_api_key')->nullable()->after('apix_client_secret');
            $table->string('goldpix_base_url', 255)->nullable()->after('goldpix_api_key');
            $table->text('goldpix_webhook_secret')->nullable()->after('goldpix_base_url');
        });
    }

    public function down(): void
    {
        Schema::table('bancos', function (Blueprint $table) {
            $table->dropColumn(['goldpix_api_key', 'goldpix_base_url', 'goldpix_webhook_secret']);
        });

        DB::statement("ALTER TABLE `bancos` MODIFY COLUMN `bank_type` ENUM('normal', 'bcodex', 'cora', 'velana', 'xgate', 'apix') DEFAULT 'normal'");
    }
};

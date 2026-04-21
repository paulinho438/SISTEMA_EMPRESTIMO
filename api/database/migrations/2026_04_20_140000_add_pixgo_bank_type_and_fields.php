<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `bancos` MODIFY COLUMN `bank_type` ENUM('normal', 'bcodex', 'cora', 'velana', 'xgate', 'apix', 'goldpix', 'pixgo') DEFAULT 'normal'");

        Schema::table('bancos', function (Blueprint $table) {
            $table->text('pixgo_api_key')->nullable()->after('goldpix_webhook_secret');
            $table->string('pixgo_base_url', 255)->nullable()->after('pixgo_api_key');
            $table->text('pixgo_webhook_secret')->nullable()->after('pixgo_base_url');
        });
    }

    public function down(): void
    {
        Schema::table('bancos', function (Blueprint $table) {
            $table->dropColumn(['pixgo_api_key', 'pixgo_base_url', 'pixgo_webhook_secret']);
        });

        DB::statement("ALTER TABLE `bancos` MODIFY COLUMN `bank_type` ENUM('normal', 'bcodex', 'cora', 'velana', 'xgate', 'apix', 'goldpix') DEFAULT 'normal'");
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE `bancos` MODIFY COLUMN `bank_type` ENUM('normal', 'bcodex', 'cora', 'velana', 'xgate', 'apix') DEFAULT 'normal'");
    }

    public function down()
    {
        DB::statement("ALTER TABLE `bancos` MODIFY COLUMN `bank_type` ENUM('normal', 'bcodex', 'cora', 'velana', 'xgate') DEFAULT 'normal'");
    }
};

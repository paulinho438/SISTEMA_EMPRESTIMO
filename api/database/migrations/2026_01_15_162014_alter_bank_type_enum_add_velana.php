<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // No MySQL, precisamos usar SQL raw para alterar um enum
        DB::statement("ALTER TABLE `bancos` MODIFY COLUMN `bank_type` ENUM('normal', 'bcodex', 'cora', 'velana') DEFAULT 'normal'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Reverter para o enum sem 'velana'
        DB::statement("ALTER TABLE `bancos` MODIFY COLUMN `bank_type` ENUM('normal', 'bcodex', 'cora') DEFAULT 'normal'");
    }
};

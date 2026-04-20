<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('custom_logs')) {
            return;
        }

        if (!Schema::hasColumn('custom_logs', 'operation')) {
            return;
        }

        DB::statement("ALTER TABLE `custom_logs` MODIFY `operation` VARCHAR(64) NULL");
    }

    public function down()
    {
        // Não é seguro reverter sem conhecer o tipo original (ENUM/VARCHAR com tamanho menor).
        // Mantemos o schema mais permissivo para evitar truncamento.
    }
};


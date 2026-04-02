<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'cobranca_pix_identificador_historicos';

    private const INDEX_TIPO_ENT = 'cb_pix_hist_tipo_ent_idx';

    public function up(): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            Schema::create(self::TABLE, function (Blueprint $table) {
                $table->id();
                $table->string('identificador', 80)->unique();
                $table->string('tipo_entidade', 40);
                $table->unsignedBigInteger('entidade_id');
                $table->unsignedBigInteger('emprestimo_id')->nullable();
                $table->unsignedBigInteger('banco_id')->nullable();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->decimal('valor', 15, 2)->nullable();
                $table->string('reference_interno', 120)->nullable();
                $table->string('provedor', 20)->default('xgate');
                $table->timestamps();

                // Nome curto: MySQL limita identificadores a 64 caracteres
                $table->index(['tipo_entidade', 'entidade_id'], self::INDEX_TIPO_ENT);
            });

            return;
        }

        // Primeira tentativa criou a tabela e falhou no índice longo: só garante o índice composto
        $this->ensureTipoEntidadeIndex();
    }

    private function ensureTipoEntidadeIndex(): void
    {
        $db = DB::connection()->getDatabaseName();
        $exists = DB::selectOne(
            'SELECT 1 AS ok FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ?
             LIMIT 1',
            [$db, self::TABLE, self::INDEX_TIPO_ENT]
        );

        if ($exists) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->index(['tipo_entidade', 'entidade_id'], self::INDEX_TIPO_ENT);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(self::TABLE);
    }
};

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
        Schema::table('parcelas', function (Blueprint $table) {
            $table->decimal('lucro_real', 10, 2)->default(0)->after('valor')->comment('Lucro real da parcela (inclui multas/juros quando aplicado)');
        });

        // Popular lucro_real das parcelas existentes
        // Calcular lucro por parcela para cada empréstimo
        \DB::statement("
            UPDATE parcelas p
            INNER JOIN emprestimos e ON p.emprestimo_id = e.id
            INNER JOIN (
                SELECT emprestimo_id, COUNT(*) as num_parcelas
                FROM parcelas
                GROUP BY emprestimo_id
            ) pc ON p.emprestimo_id = pc.emprestimo_id
            SET p.lucro_real = ROUND(e.lucro / pc.num_parcelas, 2)
            WHERE p.lucro_real = 0 OR p.lucro_real IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('parcelas', function (Blueprint $table) {
            $table->dropColumn('lucro_real');
        });
    }
};


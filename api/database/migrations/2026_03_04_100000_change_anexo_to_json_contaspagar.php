<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Altera anexo de string única para JSON array (múltiplos comprovantes)
     */
    public function up()
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE contaspagar MODIFY anexo TEXT NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE contaspagar ALTER COLUMN anexo TYPE TEXT');
        }

        // Migra dados existentes: path único -> array
        $rows = DB::table('contaspagar')->whereNotNull('anexo')->where('anexo', '!=', '')->get();
        foreach ($rows as $row) {
            $anexo = $row->anexo;
            if (!empty($anexo) && $anexo[0] !== '[') {
                DB::table('contaspagar')->where('id', $row->id)->update([
                    'anexo' => json_encode([$anexo])
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // Reverte: pega primeiro item do array
        $rows = DB::table('contaspagar')->whereNotNull('anexo')->get();
        foreach ($rows as $row) {
            $decoded = json_decode($row->anexo);
            if (is_array($decoded) && !empty($decoded)) {
                DB::table('contaspagar')->where('id', $row->id)->update([
                    'anexo' => $decoded[0]
                ]);
            }
        }

        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE contaspagar MODIFY anexo VARCHAR(500) NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE contaspagar ALTER COLUMN anexo TYPE VARCHAR(500)');
        }
    }
};

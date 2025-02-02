<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ResumoFinanceiroSeed extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table("permitems")->insert(
            [
                "name"             => "Visualizar Resumo Financeiro APP",
                "slug"             => "resumo_financeiro_aplicativo",
                "group"            => "aplicativo"
            ]
        );
    }
}

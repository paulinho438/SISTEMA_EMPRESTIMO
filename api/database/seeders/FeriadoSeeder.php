<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FeriadoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table("feriados")->insert(
            [
                "description" => "Natal 2023",
                "data_feriado" => "2023-12-25",
                "company_id" => 1,
            ]
        );

        DB::table("feriados")->insert(
            [
                "description" => "Ano Novo 2024",
                "data_feriado" => "2024-01-01",
                "company_id" => 1,
            ]
        );



    }
}

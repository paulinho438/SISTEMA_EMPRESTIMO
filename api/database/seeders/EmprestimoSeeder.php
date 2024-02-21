<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmprestimoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table("emprestimos")->insert(
            [
                "dt_lancamento" => now(),
                "valor" => 500,
                "lucro" => 10,
                "juros" => 0.1,
                "costcenter_id" => 1,
                "banco_id" => 1,
                "client_id" => 1,
                "user_id" => 4,
                "company_id" => 1,
            ]
        );

        DB::table("emprestimos")->insert(
            [
                "dt_lancamento" => now(),
                "valor" => 1000,
                "lucro" => 10,
                "juros" => 0.1,
                "costcenter_id" => 1,
                "banco_id" => 1,
                "client_id" => 1,
                "user_id" => 4,
                "company_id" => 1,
            ]
        );




    }
}

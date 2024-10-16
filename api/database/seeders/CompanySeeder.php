<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table("companies")->insert(
            [
                "company" => "BSB EMPRESTIMOS",
                "juros" => 0.5,
                "whatsapp" => "https://node1.rjemprestimos.com.br",
            ]
        );

        DB::table("companies")->insert(
            [
                "company" => "RJ EMPRESTIMOS",
            ]
        );
    }
}

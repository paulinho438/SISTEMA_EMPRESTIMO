<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BancoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table("bancos")->insert(
            [
                "name" => "Banco ITAU",
                "agencia" => "1234-1",
                "conta" => "1234-2",
                "saldo" => 10000,
                "company_id" => 1,
                "created_at" => now(),
            ]
        );



    }
}

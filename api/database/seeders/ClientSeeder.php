<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table("clients")->insert(
            [
                "nome_completo" => "Paulo Henrique",
                "cpf" => "055.463.561-54",
                "rg" => "2.834.868",
                "data_nascimento" => "1994-12-09",
                "sexo" => "M",
                "telefone_celular_1" => "(61) 9330-5267",
                "telefone_celular_2" => "(61) 9330-5268",
                "email" => "paulo.peixoto@gmail.com",
                "limit" => 1000,
                "company_id" => 1,
                "created_at" => now(),
                "password" => "1234",
            ]
        );



    }
}

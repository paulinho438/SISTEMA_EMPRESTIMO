<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table("users")->insert(
            [
                "nome_completo"             => "RAY",
                "cpf"                       => "rj",
                "rg"                        => "2834868",
                "data_nascimento"           => Carbon::now()->format("Y-m-d"),
                "sexo"                      => "M",
                "telefone_celular"          => "(61) 9 9330-5267",
                "email"                     => "admin@gmail.com",
                "status"                    => "A",
                "status_motivo"             => "",
                "tentativas"                => "0",
                "password"                  => bcrypt("1234"),
                "created_at"                => Carbon::now()->format("Y-m-d H:i:s"),
                "updated_at"                => Carbon::now()->format("Y-m-d H:i:s")
            ]
        );

        DB::table("users")->insert(
            [
                "nome_completo"             => "RJ EMPRESTIMOS",
                "cpf"                       => "11111111111",
                "rg"                        => "0000000",
                "data_nascimento"           => Carbon::now()->format("Y-m-d"),
                "sexo"                      => "M",
                "telefone_celular"          => "(61) 9 0000-0000",
                "email"                     => "admin2@gmail.com",
                "status"                    => "A",
                "status_motivo"             => "",
                "tentativas"                => "0",
                "password"                  => bcrypt("1234"),
                "created_at"                => Carbon::now()->format("Y-m-d H:i:s"),
                "updated_at"                => Carbon::now()->format("Y-m-d H:i:s")
            ]
        );

        DB::table("users")->insert(
            [
                "nome_completo"             => "MASTERGERAL",
                "cpf"                       => "MASTERGERAL",
                "login"                     => "MASTERGERAL",
                "rg"                        => "2834868",
                "data_nascimento"           => Carbon::now()->format("Y-m-d"),
                "sexo"                      => "M",
                "telefone_celular"          => "(61) 9 9330-5267",
                "email"                     => "admin@gmail.com",
                "status"                    => "A",
                "status_motivo"             => "",
                "tentativas"                => "0",
                "password"                  => bcrypt("1234"),
                "created_at"                => Carbon::now()->format("Y-m-d H:i:s"),
                "updated_at"                => Carbon::now()->format("Y-m-d H:i:s")
            ]
        );
    }
}

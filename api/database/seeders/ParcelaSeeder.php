<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ParcelaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table("parcelas")->insert(
            [
                "emprestimo_id" => 1,
                "parcela" => "001",
                "valor" => 100,
                "saldo" => 100,
                "venc" => now(),
                "venc_real" => now(),
                "dt_lancamento" => now(),
            ]
        );

        DB::table("parcelas")->insert(
            [
                "emprestimo_id" => 1,
                "parcela" => "002",
                "valor" => 100,
                "saldo" => 100,
                "venc" => now(),
                "venc_real" => now(),
                "dt_lancamento" => now(),
            ]
        );

        DB::table("parcelas")->insert(
            [
                "emprestimo_id" => 1,
                "parcela" => "003",
                "valor" => 100,
                "saldo" => 100,
                "venc" => now(),
                "venc_real" => now(),
                "dt_lancamento" => now(),
            ]
        );

        DB::table("parcelas")->insert(
            [
                "emprestimo_id" => 1,
                "parcela" => "004",
                "valor" => 100,
                "saldo" => 0,
                "venc" => now(),
                "venc_real" => now(),
                "dt_lancamento" => now(),
                "dt_baixa" => now()
            ]
        );

        DB::table("parcelas")->insert(
            [
                "emprestimo_id" => 1,
                "parcela" => "005",
                "valor" => 100,
                "saldo" => 0,
                "venc" => now(),
                "venc_real" => now(),
                "dt_lancamento" => now(),
                "dt_baixa" => now()
            ]
        );




        DB::table("parcelas")->insert(
            [
                "emprestimo_id" => 2,
                "parcela" => "001",
                "valor" => 200,
                "saldo" => 200,
                "venc" => now(),
                "venc_real" => now(),
                "dt_lancamento" => now(),
            ]
        );

        DB::table("parcelas")->insert(
            [
                "emprestimo_id" => 2,
                "parcela" => "002",
                "valor" => 200,
                "saldo" => 200,
                "venc" => now(),
                "venc_real" => now(),
                "dt_lancamento" => now(),
            ]
        );

        DB::table("parcelas")->insert(
            [
                "emprestimo_id" => 2,
                "parcela" => "003",
                "valor" => 200,
                "saldo" => 100,
                "venc" => now(),
                "venc_real" => now(),
                "dt_lancamento" => now(),
                "dt_baixa" => now()
            ]
        );

        DB::table("parcelas")->insert(
            [
                "emprestimo_id" => 2,
                "parcela" => "004",
                "valor" => 200,
                "saldo" => 0,
                "venc" => now(),
                "venc_real" => now(),
                "dt_lancamento" => now(),
                "dt_baixa" => now()
            ]
        );

        DB::table("parcelas")->insert(
            [
                "emprestimo_id" => 2,
                "parcela" => "005",
                "valor" => 200,
                "saldo" => 0,
                "venc" => now(),
                "venc_real" => now(),
                "dt_lancamento" => now(),
                "dt_baixa" => now()
            ]
        );



    }
}

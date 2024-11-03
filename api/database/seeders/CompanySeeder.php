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
        DB::table('planos')->insert([
            'nome' => 'Acima de 100 contratos / mês',
            'preco' => 99.99,
            'descricao' => 'Plano básico com funcionalidades limitadas',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('planos')->insert([
            'nome' => 'Plano intermediario',
            'preco' => 50,
            'descricao' => 'Plano intermediario',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('planos')->insert([
            'nome' => 'Plano Básico',
            'preco' => 30,
            'descricao' => 'Plano básico com funcionalidades limitadas',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table("companies")->insert(
            [
                "company" => "BSB EMPRESTIMOS",
                "juros" => 0.5,
                "whatsapp" => "https://node1.rjemprestimos.com.br",
                "plano_id" => 1
            ]
        );

        DB::table("companies")->insert(
            [
                "company" => "RJ EMPRESTIMOS",
                "plano_id" => 1
            ]
        );
    }
}

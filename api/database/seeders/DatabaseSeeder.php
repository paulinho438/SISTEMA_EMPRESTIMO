<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            UserSeeder::class,
            CompanySeeder::class,
            CompanyUserSeeder::class,

                // Cria as tabelas base
            PermGroupsSeeder::class,
            PermItemsSeeder::class,

                // Só agora faz o vínculo
            PermLinksSeeder::class,
            PermLinksUserSeeder::class,

            CategoriesSeeder::class,
            CostcenterSeeder::class,
            BancoSeeder::class,
            FeriadoSeeder::class,
            JurosSeeder::class
        ]);
    }
}

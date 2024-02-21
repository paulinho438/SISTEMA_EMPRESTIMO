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
            PermLinksSeeder::class,
            PermLinksUserSeeder::class,
            PermItemsSeeder::class,
            PermGroupsSeeder::class,
            CategoriesSeeder::class,
            EmprestimoSeeder::class,
            ClientSeeder::class,
            CostcenterSeeder::class,
            BancoSeeder::class,
            ParcelaSeeder::class,
            FeriadoSeeder::class,
            JurosSeeder::class
        ]);
    }
}

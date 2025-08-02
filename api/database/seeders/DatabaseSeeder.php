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

        // Esses precisam vir antes de PermLinksSeeder
        PermItemsSeeder::class,
        PermGroupsSeeder::class,

        PermLinksSeeder::class,
        PermLinksUserSeeder::class,

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

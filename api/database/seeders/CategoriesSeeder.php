<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table("categories")->insert(
            [
                "name" => "PIX",
                "description" => "Pagamento Pix",
                "company_id" => 1,
                "created_at" => now(),
                "standard" => true,
            ]
        );



    }
}

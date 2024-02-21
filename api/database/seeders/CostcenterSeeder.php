<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CostcenterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table("costcenter")->insert(
            [
                "name" => "Default",
                "description" => "Default",
                "company_id" => 1,
                "created_at" => now(),
            ]
        );



    }
}

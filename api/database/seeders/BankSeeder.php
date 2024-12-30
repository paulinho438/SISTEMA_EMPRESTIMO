<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;
use Carbon\Carbon;

class BankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Caminho para o arquivo CSV
        $filePath = base_path('database/seeders/data/banks.csv');

        // Carregar o arquivo CSV
        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0); // Usar a primeira linha como cabeçalho

        $banks = [];
        foreach ($csv as $record) {
            $banks[] = [
                'ispb' => $record['ISPB'],
                'short_name' => $record['Short_Name'],
                'code_number' => $record['Code_Number'] !== 'n/a' ? $record['Code_Number'] : null,
                'participation_in_compe' => $record['Participation_in_Compe'] === 'Yes' ? 1 : 0,
                'main_access' => $record['Main_Access'],
                'full_name' => $record['Full_Name'],
                'start_date' => Carbon::parse($record['Start_Date'])->format('Y-m-d'),
            ];
        }

        // Inserir dados no banco
        DB::table('banks')->insert($banks);
    }
}

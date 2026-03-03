<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $languages = [
            ['name' => 'Ateso', 'code' => 'teo'],
            ['name' => 'Luganda', 'code' => 'lg'],
        ];

        foreach ($languages as $language) {
            Language::updateOrCreate(['code' => $language['code']], $language);
        }
    }
}

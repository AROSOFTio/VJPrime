<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            LanguageSeeder::class,
            VjSeeder::class,
            GenreSeeder::class,
            AdminUserSeeder::class,
            MovieSeeder::class,
        ]);
    }
}

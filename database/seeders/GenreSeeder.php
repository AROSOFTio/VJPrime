<?php

namespace Database\Seeders;

use App\Models\Genre;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GenreSeeder extends Seeder
{
    public function run(): void
    {
        $genres = ['Action', 'Drama', 'Comedy', 'Romance', 'Sci-Fi', 'Thriller'];

        foreach ($genres as $name) {
            Genre::updateOrCreate(['slug' => Str::slug($name)], [
                'name' => $name,
                'slug' => Str::slug($name),
            ]);
        }
    }
}

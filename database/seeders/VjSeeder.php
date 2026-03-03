<?php

namespace Database\Seeders;

use App\Models\Vj;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class VjSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['VJ Suldan', 'VJ Aro', 'VJ Teso Star'] as $name) {
            Vj::updateOrCreate(['slug' => Str::slug($name)], [
                'name' => $name,
                'slug' => Str::slug($name),
            ]);
        }
    }
}

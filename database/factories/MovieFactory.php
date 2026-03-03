<?php

namespace Database\Factories;

use App\Models\Language;
use App\Models\Vj;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Movie>
 */
class MovieFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1000, 9999),
            'description' => fake()->paragraph(4),
            'poster_url' => 'https://picsum.photos/seed/'.fake()->uuid().'/600/900',
            'backdrop_url' => 'https://picsum.photos/seed/'.fake()->uuid().'/1600/900',
            'year' => fake()->numberBetween(2014, 2025),
            'duration_seconds' => fake()->numberBetween(3600, 9000),
            'age_rating' => fake()->randomElement(['PG', 'PG-13', '16', '18']),
            'language_id' => Language::query()->value('id') ?? 1,
            'vj_id' => Vj::query()->value('id') ?? 1,
            'is_featured' => fake()->boolean(30),
            'status' => 'published',
            'published_at' => now()->subDays(fake()->numberBetween(0, 40)),
        ];
    }
}

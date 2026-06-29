<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MovieType;
use App\Models\Movie;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Movie>
 */
class MovieFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'imdb_id' => fake()->unique()->regexify('tt[0-9]{7,8}'),
            'title' => fake()->sentence(3),
            'type' => MovieType::Movie,
            'year' => (string) fake()->numberBetween(1950, 2026),
            'poster_url' => fake()->imageUrl(300, 450, 'movies'),
            'plot' => fake()->paragraph(),
            'director' => fake()->name(),
            'actors' => fake()->name().', '.fake()->name().', '.fake()->name(),
            'cached_at' => now(),
        ];
    }

    public function series(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => MovieType::Series,
        ]);
    }

    public function episode(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => MovieType::Episode,
        ]);
    }

    public function withoutMetadata(): static
    {
        return $this->state(fn (array $attributes): array => [
            'year' => '',
            'poster_url' => '',
            'plot' => '',
            'director' => '',
            'actors' => '',
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Movie;
use App\Services\OmdbService;

/**
 * Resolves a movie by its IMDb id using a cache-aside strategy (level-2 cache).
 */
final class FindOrCacheMovie
{
    public function __construct(private readonly OmdbService $omdb) {}

    public function __invoke(string $imdbId): ?Movie
    {
        $movie = Movie::query()->where('imdb_id', $imdbId)->first();

        if ($movie !== null) {
            return $movie;
        }

        $data = $this->omdb->fetchByImdbId($imdbId);

        if ($data === null) {
            return null;
        }

        return Movie::updateOrCreate(
            ['imdb_id' => $data->imdbId],
            [...$data->toAttributes(), 'cached_at' => now()],
        );
    }
}

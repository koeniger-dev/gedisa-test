<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\MovieType;

final readonly class MovieData
{
    public function __construct(
        public string $imdbId,
        public string $title,
        public MovieType $type,
        public string $year,
        public string $posterUrl,
        public string $plot,
        public string $director,
        public string $actors,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromOmdbResponse(array $payload): self
    {
        return new self(
            imdbId: self::clean($payload['imdbID'] ?? ''),
            title: self::clean($payload['Title'] ?? ''),
            type: MovieType::tryFrom((string) ($payload['Type'] ?? '')) ?? MovieType::Movie,
            year: self::clean($payload['Year'] ?? ''),
            posterUrl: self::clean($payload['Poster'] ?? ''),
            plot: self::clean($payload['Plot'] ?? ''),
            director: self::clean($payload['Director'] ?? ''),
            actors: self::clean($payload['Actors'] ?? ''),
        );
    }

    /**
     * Attributes for persisting the movie locally (level-2 cache).
     *
     * The imdb_id is intentionally excluded: it is the lookup key used by the
     * caller's updateOrCreate() and must not be part of the update payload.
     *
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'title' => $this->title,
            'type' => $this->type,
            'year' => $this->year,
            'poster_url' => $this->posterUrl,
            'plot' => $this->plot,
            'director' => $this->director,
            'actors' => $this->actors,
        ];
    }

    /**
     * Normalise OMDb's "N/A" placeholder into an empty string.
     */
    private static function clean(mixed $value): string
    {
        $value = (string) $value;

        return $value === 'N/A' ? '' : $value;
    }
}

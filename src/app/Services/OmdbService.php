<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\MovieData;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Single gateway to the OMDb API.
 *
 * Responsibilities:
 *  - encapsulate every OMDb HTTP call (no Http::get() leaks into components),
 *  - map raw responses into typed {@see MovieData} objects,
 *  - apply a short-lived response cache (level-1) to save OMDb's daily api calls limit
 *    and avoid overloading the API while the user is typing
 *
 * It does NOT touch the database. Persisting movies locally (level-2 cache) is
 * the caller's concern (MovieDetail), keeping this class a pure OMDb adapter
 * that is trivially unit-testable without a database.
 */
final class OmdbService
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl,
        private readonly int $cacheTtl,
    ) {}

    /**
     * @return Collection<int, MovieData>
     */
    public function search(string $query): Collection
    {
        $query = trim($query);

        if ($query === '') {
            return collect();
        }

        $payload = $this->cachedRequest(['s' => $query], "omdb:search:{$query}");

        if (! $this->isSuccessful($payload)) {
            return collect();
        }

        return collect($payload['Search'] ?? [])
            ->map(static fn (array $movie): MovieData => MovieData::fromOmdbResponse($movie))
            ->values();
    }

    public function fetchByImdbId(string $imdbId): ?MovieData
    {
        $imdbId = trim($imdbId);

        if ($imdbId === '') {
            return null;
        }

        $payload = $this->cachedRequest(
            ['i' => $imdbId, 'plot' => 'full'],
            "omdb:movie:{$imdbId}",
        );

        if (! $this->isSuccessful($payload)) {
            return null;
        }

        return MovieData::fromOmdbResponse($payload);
    }

    /**
     * Save the raw OMDb response for a request (level-1 cache).
     *
     * @param  array<string, string>  $params
     * @return array<string, mixed>
     */
    private function cachedRequest(array $params, string $cacheKey): array
    {
        return Cache::remember(
            $cacheKey,
            now()->addSeconds($this->cacheTtl),
            fn (): array => $this->request($params),
        );
    }

    /**
     * @param  array<string, string>  $params
     * @return array<string, mixed>
     */
    private function request(array $params): array
    {
        try {
            $response = Http::baseUrl($this->baseUrl)
                ->get('/', [...$params, 'apikey' => $this->apiKey]);
        } catch (ConnectionException) {
            // Network/DNS/timeout failure: degrade to an OMDb-shaped error so
            // callers only ever branch on the "Response" flag.
            return ['Response' => 'False'];
        }

        if ($response->failed()) {
            return ['Response' => 'False'];
        }

        return $response->json() ?? ['Response' => 'False'];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function isSuccessful(array $payload): bool
    {
        return ($payload['Response'] ?? 'False') === 'True';
    }
}

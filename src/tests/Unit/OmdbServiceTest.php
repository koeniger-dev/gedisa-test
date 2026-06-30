<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\DTOs\MovieData;
use App\Enums\MovieType;
use App\Services\OmdbService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class OmdbServiceTest extends TestCase
{
    private function service(): OmdbService
    {
        return new OmdbService('test-key', 'https://www.omdbapi.com/', 600);
    }

    public function test_search_maps_results_into_movie_data_collection(): void
    {
        Http::fake([
            'www.omdbapi.com/*' => Http::response([
                'Response' => 'True',
                'Search' => [
                    ['Title' => 'Batman Begins', 'Year' => '2005', 'imdbID' => 'tt0372784', 'Type' => 'movie', 'Poster' => 'https://img/1.jpg'],
                    ['Title' => 'The Dark Knight', 'Year' => '2008', 'imdbID' => 'tt0468569', 'Type' => 'movie', 'Poster' => 'N/A'],
                ],
            ]),
        ]);

        $results = $this->service()->search('batman');

        $this->assertCount(2, $results);
        $this->assertInstanceOf(MovieData::class, $results->first());
        $this->assertSame('tt0372784', $results->first()->imdbId);
        $this->assertSame('Batman Begins', $results->first()->title);
        $this->assertSame(MovieType::Movie, $results->first()->type);
        // OMDb's "N/A" placeholder is normalised to an empty string.
        $this->assertSame('', $results->last()->posterUrl);
    }

    public function test_search_returns_empty_collection_when_omdb_reports_no_results(): void
    {
        Http::fake([
            '*' => Http::response(['Response' => 'False', 'Error' => 'Movie not found!']),
        ]);

        $this->assertTrue($this->service()->search('zzzzzzzz')->isEmpty());
    }

    public function test_search_short_circuits_blank_query_without_http_call(): void
    {
        Http::fake();

        $this->assertTrue($this->service()->search('   ')->isEmpty());
        Http::assertNothingSent();
    }

    public function test_fetch_by_imdb_id_maps_full_detail_payload(): void
    {
        Http::fake([
            '*' => Http::response([
                'Response' => 'True',
                'Title' => 'Batman Begins',
                'Year' => '2005',
                'Type' => 'movie',
                'imdbID' => 'tt0372784',
                'Poster' => 'https://img/1.jpg',
                'Plot' => 'Bruce Wayne becomes Batman.',
                'Director' => 'Christopher Nolan',
                'Actors' => 'Christian Bale, Michael Caine, Liam Neeson',
            ]),
        ]);

        $movie = $this->service()->fetchByImdbId('tt0372784');

        $this->assertInstanceOf(MovieData::class, $movie);
        $this->assertSame('Christopher Nolan', $movie->director);
        $this->assertSame('Bruce Wayne becomes Batman.', $movie->plot);
        $this->assertSame('Christian Bale, Michael Caine, Liam Neeson', $movie->actors);
    }

    public function test_fetch_by_imdb_id_returns_null_when_not_found(): void
    {
        Http::fake([
            '*' => Http::response(['Response' => 'False', 'Error' => 'Incorrect IMDb ID.']),
        ]);

        $this->assertNull($this->service()->fetchByImdbId('tt0000000'));
    }

    public function test_fetch_by_imdb_id_returns_null_on_http_failure(): void
    {
        Http::fake([
            '*' => Http::response(null, 500),
        ]);

        $this->assertNull($this->service()->fetchByImdbId('tt0372784'));
    }

    public function test_request_appends_api_key_and_query_parameter(): void
    {
        Http::fake([
            '*' => Http::response(['Response' => 'True', 'Search' => []]),
        ]);

        $this->service()->search('batman');

        Http::assertSent(function (Request $request): bool {
            return str_contains($request->url(), 'apikey=test-key')
                && str_contains($request->url(), 's=batman');
        });
    }

    public function test_repeated_identical_search_is_served_from_level1_cache(): void
    {
        Http::fake([
            '*' => Http::response(['Response' => 'True', 'Search' => []]),
        ]);

        $service = $this->service();
        $service->search('batman');
        $service->search('batman');

        // Second call hits the cache, so OMDb is only contacted once.
        Http::assertSentCount(1);
    }

    public function test_cache_expires_after_ttl_and_refetches(): void
    {
        Http::fake([
            '*' => Http::response(['Response' => 'True', 'Search' => []]),
        ]);

        // TTL is 600s (see service() helper).
        $service = $this->service();
        $service->search('batman');

        $this->travel(11)->minutes();
        $service->search('batman');

        // After the TTL elapsed the second call goes back out to OMDb.
        Http::assertSentCount(2);

        $this->travelBack();
    }

    public function test_search_degrades_gracefully_on_connection_error(): void
    {
        Http::fake(function (): never {
            throw new ConnectionException('Could not resolve host: www.omdbapi.com');
        });

        $this->assertTrue($this->service()->search('batman')->isEmpty());
    }

    public function test_fetch_returns_null_on_connection_error(): void
    {
        Http::fake(function (): never {
            throw new ConnectionException('Connection timed out');
        });

        $this->assertNull($this->service()->fetchByImdbId('tt0372784'));
    }
}

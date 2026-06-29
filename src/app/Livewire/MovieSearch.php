<?php

declare(strict_types=1);

namespace App\Livewire;

use App\DTOs\MovieData;
use App\Models\Movie;
use App\Services\OmdbService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;

final class MovieSearch extends Component
{
    /**
     * Minimum query length before contacting OMDb. Guards against noisy
     * single-character lookups and needless API usage.
     */
    private const int MIN_QUERY_LENGTH = 2;

    /**
     * Bound to the search box. Mirrored into the URL (?q=) so a search is
     * shareable and survives a refresh / back-button.
     */
    #[Url(as: 'q', except: '')]
    public string $query = '';

    public function render(OmdbService $omdb): View
    {
        $results = $this->searchMovies($omdb);

        return view('livewire.movie-search', [
            'results' => $results,
            'stats' => $this->localRatingStats($results),
        ]);
    }

    /**
     * @return Collection<int, MovieData>
     */
    private function searchMovies(OmdbService $omdb): Collection
    {
        $query = trim($this->query);

        if (mb_strlen($query) < self::MIN_QUERY_LENGTH) {
            return collect();
        }

        return $omdb->search($query);
    }

    /**
     * Local rating aggregates (average + count) for the current OMDb results,
     * keyed by imdb_id. Resolved in a single query with SQL-side aggregation —
     * no N+1, no averaging in PHP. Movies that have never been cached/rated
     * locally simply have no entry.
     *
     * @param  Collection<int, MovieData>  $results
     * @return Collection<string, Movie>
     */
    private function localRatingStats(Collection $results): Collection
    {
        if ($results->isEmpty()) {
            return collect();
        }

        return Movie::query()
            ->whereIn('imdb_id', $results->pluck('imdbId'))
            ->withAvg('ratings as average_rating', 'rating')
            ->withCount('ratings as ratings_count')
            ->get(['id', 'imdb_id'])
            ->keyBy('imdb_id');
    }
}

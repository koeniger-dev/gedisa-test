<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\FindOrCacheMovie;
use App\Models\Movie;
use App\Models\Rating;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class MovieDetail extends Component
{
    /**
     * The movie being viewed. Locked so the client cannot swap it between
     * requests (it is the FK target for the rating action).
     */
    #[Locked]
    public Movie $movie;

    /**
     * The current user's own rating (null for guests or not-yet-rated).
     */
    public ?int $userRating = null;

    /**
     * Whether the movie is on the current user's watchlist.
     */
    public bool $inWatchlist = false;

    public function mount(string $imdbId, FindOrCacheMovie $findOrCacheMovie): void
    {
        $movie = $findOrCacheMovie($imdbId);

        abort_if($movie === null, 404);

        $this->movie = $movie;
        $this->userRating = $this->resolveUserRating();
        $this->inWatchlist = $this->resolveInWatchlist();
    }

    /**
     * Add or remove the movie from the current user's watchlist.
     */
    public function toggleWatchlist(): void
    {
        abort_unless(Auth::check(), 403);

        Auth::user()->watchlistMovies()->toggle($this->movie->id);

        $this->inWatchlist = $this->resolveInWatchlist();
    }

    /**
     * @throws ValidationException
     * @throws AuthorizationException
     */
    public function rate(int $rating): void
    {
        abort_unless(Auth::check(), 403);

        $validated = Validator::make(
            ['rating' => $rating],
            ['rating' => ['required', 'integer', 'between:1,5']],
        )->validate();

        $existing = Rating::query()
            ->where('user_id', Auth::id())
            ->where('movie_id', $this->movie->id)
            ->first();

        if ($existing !== null) {
            $this->authorize('update', $existing);
            $existing->update(['rating' => $validated['rating']]);
        } else {
            Rating::create([
                'user_id' => Auth::id(),
                'movie_id' => $this->movie->id,
                'rating' => $validated['rating'],
            ]);
        }

        $this->userRating = $validated['rating'];

        // Invalidate the memoised aggregates so the average reflects this vote.
        unset($this->averageRating, $this->ratingsCount);
    }

    #[Computed]
    public function averageRating(): float
    {
        return (float) $this->movie->ratings()->avg('rating');
    }

    #[Computed]
    public function ratingsCount(): int
    {
        return $this->movie->ratings()->count();
    }

    public function render(): View
    {
        return view('livewire.movie-detail');
    }

    private function resolveUserRating(): ?int
    {
        if (! Auth::check()) {
            return null;
        }

        return $this->movie->ratings()
            ->where('user_id', Auth::id())
            ->value('rating');
    }

    private function resolveInWatchlist(): bool
    {
        if (! Auth::check()) {
            return false;
        }

        return Auth::user()->watchlistMovies()
            ->whereKey($this->movie->id)
            ->exists();
    }
}

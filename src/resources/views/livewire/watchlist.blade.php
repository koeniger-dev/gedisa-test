<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-semibold text-gray-900">Watchlist</h1>
    <p class="mt-1 text-sm text-gray-500">Deine vorgemerkten Filme.</p>

    @if ($movies->isNotEmpty())
        <div class="mt-8 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-6">
            @foreach ($movies as $movie)
                <div wire:key="watch-{{ $movie->id }}" class="relative rounded-lg bg-white shadow-sm ring-1 ring-gray-200 overflow-hidden">
                    <button
                        type="button"
                        wire:click="remove({{ $movie->id }})"
                        title="Aus Watchlist entfernen"
                        class="absolute top-2 right-2 z-10 flex h-7 w-7 items-center justify-center rounded-full bg-black/60 text-white text-sm hover:bg-black/80"
                    >&times;</button>

                    <a href="{{ route('movies.show', $movie->imdb_id) }}" wire:navigate class="group block">
                        <x-movie-poster :url="$movie->poster_url" :alt="$movie->title" class="h-72" />

                        <div class="p-4">
                            <h2 class="font-medium text-gray-900 line-clamp-1 group-hover:underline">{{ $movie->title }}</h2>
                            <div class="mt-1 flex items-center justify-between text-sm text-gray-500">
                                <span>{{ $movie->year }}</span>
                                @if ($movie->ratings_count > 0)
                                    <span class="inline-flex items-center gap-1 font-medium text-amber-600">
                                        ★ {{ number_format((float) $movie->average_rating, 1) }}
                                        <span class="text-gray-400 font-normal">({{ $movie->ratings_count }})</span>
                                    </span>
                                @else
                                    <span class="text-gray-300">Noch keine Bewertung</span>
                                @endif
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    @else
        <div class="mt-8 rounded-lg bg-white shadow-sm ring-1 ring-gray-200 p-6 text-gray-500">
            Deine Watchlist ist noch leer. Filme kannst du in der Detailansicht vormerken.
        </div>
    @endif
</div>

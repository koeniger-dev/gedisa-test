<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Bewertete Filme</h1>
            <p class="mt-1 text-sm text-gray-500">Alle intern bewerteten Filme mit Durchschnittsbewertung.</p>
        </div>

        <div class="flex items-center gap-2">
            <label for="sort" class="text-sm text-gray-500">Sortierung</label>
            <select
                id="sort"
                wire:model.live="sort"
                class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-gray-900 focus:ring-gray-900"
            >
                <option value="best">Beste zuerst</option>
                <option value="popular">Meiste Bewertungen</option>
                <option value="recent">Neueste</option>
            </select>
        </div>
    </div>

    @if ($movies->isNotEmpty())
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-6">
            @foreach ($movies as $movie)
                <a
                    wire:key="rated-{{ $movie->id }}"
                    href="{{ route('movies.show', $movie->imdb_id) }}"
                    wire:navigate
                    class="group block rounded-lg bg-white shadow-sm ring-1 ring-gray-200 overflow-hidden hover:ring-gray-900 transition"
                >
                    <x-movie-poster :url="$movie->poster_url" :alt="$movie->title" class="h-72" />

                    <div class="p-4">
                        <h2 class="font-medium text-gray-900 line-clamp-1 group-hover:underline">{{ $movie->title }}</h2>
                        <div class="mt-1 flex items-center justify-between text-sm text-gray-500">
                            <span>{{ $movie->year }}</span>
                            <span class="inline-flex items-center gap-1 font-medium text-amber-600">
                                ★ {{ number_format((float) $movie->average_rating, 1) }}
                                <span class="text-gray-400 font-normal">({{ $movie->ratings_count }})</span>
                            </span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $movies->links() }}
        </div>
    @else
        <p class="text-gray-400">Es wurden noch keine Filme bewertet.</p>
    @endif
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <h1 class="text-2xl font-semibold text-gray-900">Filme suchen</h1>
        <p class="mt-1 text-sm text-gray-500">Suche über die OMDb-Datenbank und sieh interne Durchschnittsbewertungen.</p>
    </div>

    {{-- Search box: .live + debounce keeps OMDb calls in check while typing. --}}
    <div class="relative max-w-xl">
        <input
            type="search"
            wire:model.live.debounce.400ms="query"
            placeholder="Filmtitel eingeben…"
            autofocus
            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900"
        >

        {{-- Spinner shown only while the query roundtrip is in flight. --}}
        <div
            wire:loading
            wire:target="query"
            class="absolute inset-y-0 right-3 flex items-center text-gray-400"
        >
            <svg class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
        </div>
    </div>

    {{-- Results --}}
    @if ($results->isNotEmpty())
        <div class="mt-8 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-6">
            @foreach ($results as $movie)
                @php($stat = $stats->get($movie->imdbId))
                <a
                    wire:key="movie-{{ $movie->imdbId }}"
                    href="/movies/{{ $movie->imdbId }}"
                    wire:navigate
                    class="group block rounded-lg bg-white shadow-sm ring-1 ring-gray-200 overflow-hidden hover:ring-gray-900 transition"
                >
                    <x-movie-poster :url="$movie->posterUrl" :alt="$movie->title" class="h-72" />

                    <div class="p-4">
                        <h2 class="font-medium text-gray-900 line-clamp-1 group-hover:underline">{{ $movie->title }}</h2>
                        <div class="mt-1 flex items-center justify-between text-sm text-gray-500">
                            <span>{{ $movie->year }}</span>

                            @if ($stat && $stat->ratings_count > 0)
                                <span class="inline-flex items-center gap-1 font-medium text-amber-600">
                                    ★ {{ number_format((float) $stat->average_rating, 1) }}
                                    <span class="text-gray-400 font-normal">({{ $stat->ratings_count }})</span>
                                </span>
                            @else
                                <span class="text-gray-300">Noch keine Bewertung</span>
                            @endif
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @else
        <div class="mt-8">
            @if (mb_strlen(trim($query)) >= 2)
                <p class="text-gray-500">Keine Filme für „{{ $query }}" gefunden.</p>
            @else
                <p class="text-gray-400">Gib mindestens zwei Zeichen ein, um die Suche zu starten.</p>
            @endif
        </div>
    @endif
</div>

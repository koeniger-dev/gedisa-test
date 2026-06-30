# Movie Rating App — TALL Stack

Code-Challenge (GEDISA): Eine Movie-Rating-App auf Basis des **TALL-Stacks**
(Tailwind, Alpine, Livewire, Laravel). User suchen Filme über die
[OMDb-API](https://www.omdbapi.com/), sehen Details und vergeben 1–5-Sterne-
Bewertungen. Eine öffentliche Liste zeigt alle intern bewerteten Filme mit
Durchschnittsbewertung.

## Stack

- **PHP 8.3**, **Laravel 13**
- **Livewire 3** (klassische Komponenten — Klasse + Blade, kein Volt)
- **Tailwind CSS 3** + Alpine.js (mit Livewire gebündelt)
- **PHPUnit** für Tests, **Laravel Pint** für Code-Style
- **Laravel Breeze** (Authentifizierung)
- **MariaDB 11** (Container `mysql`); Tests laufen auf In-Memory-SQLite
- Docker-basierte Umgebung (`app`, `nginx`, `mysql`, `node`)

## Architektur — die wichtigsten Entscheidungen

| Thema | Entscheidung | Warum |
|---|---|---|
| **OMDb-Zugriff** | Gekapselt in `OmdbService`, Antworten in `MovieData` (readonly DTO) gemappt | Kein `Http::get()` in Komponenten, typsicher, isoliert testbar |
| **Zwei Cache-Ebenen** | (1) `Cache::remember` auf rohe OMDb-Antworten; (2) Filme lokal in `movies` persistiert (`FindOrCacheMovie`, cache-aside) | Schont das OMDb-Limit, macht die App unabhängig von OMDb, liefert den lokalen FK für Ratings |
| **Durchschnitt** | DB-seitig via `withAvg`/`withCount` (Subqueries) | Keine PHP-Schleifen, kein N+1 |
| **Rating-Sicherheit** | `unique(user_id, movie_id)` + CHECK `1..5` in der DB, `RatingPolicy`, Auth-Guard in der Action | Defense in depth — DB als letzte Verteidigungslinie |
| **Watchlist** | Reiner Many-to-Many-Pivot (`User::watchlistMovies`) | Keine eigene Domain-Klasse nötig (bewusste Einfachheit) |
| **Livewire** | `wire:model.live.debounce`, `#[Computed]`-Aggregate, `wire:loading`, `wire:navigate` | Reaktive UI ohne API-Spam, ohne Reload |

## Seiten / Routen

| Route | Komponente | Zugriff |
|---|---|---|
| `/` | `MovieSearch` | öffentlich |
| `/movies/{imdbId}` | `MovieDetail` (Details + Rating + Watchlist-Toggle) | öffentlich (Bewerten nur eingeloggt) |
| `/rated` | `RatedMoviesList` (bewertete Filme, Ø-Bewertung, Sortierung, Pagination) | öffentlich |
| `/dashboard` | `Watchlist` | nur eingeloggt |

## Setup

Die Umgebung läuft in Docker: **PHP/Artisan/Composer** im Container `app`,
**Node/npm** im Container `node`.

```bash
# 1. Images bauen & Container starten
#    (--build baut das app-Image aus docker/php/Dockerfile)
docker compose build
docker compose up -d

# 2. Abhängigkeiten installieren
docker compose exec app composer install
docker compose exec node npm install

# 3. Environment
cp .env.example .env
docker compose exec app php artisan key:generate
```

**OMDb-Key eintragen** in `.env` (kostenlos unter
<https://www.omdbapi.com/apikey.aspx>):

```env
OMDB_API_KEY=dein_key_hier
OMDB_URL=https://www.omdbapi.com/
OMDB_CACHE_TTL=600
```

```bash
# 4. Datenbank
docker compose exec app php artisan migrate

# 5. Frontend bauen
docker compose exec node npm run build
```

App läuft unter <http://localhost:8000>.

> **Hinweis zum Dev-Server:** `npm run dev` (HMR) erfordert, dass Vite im
> Container auf `0.0.0.0` lauscht (`server.host` in `vite.config.js`), sonst
> ist der Dev-Server vom Host-Browser nicht erreichbar. Für die Bewertung
> genügt `npm run build` — der statische Build braucht keinen Dev-Server.

## Tests

73 Tests (Unit + Feature) auf In-Memory-SQLite. OMDb wird durchgängig mit
`Http::fake()` gemockt — keine echten API-Calls in der Suite.

```bash
docker compose exec app php artisan test            # alle
docker compose exec app php artisan test --compact   # kompakte Ausgabe
docker compose exec app php artisan test --filter=MovieDetail
```

Code-Style:

```bash
docker compose exec app vendor/bin/pint
```

## Projektstruktur

```
app/
├── Actions/FindOrCacheMovie.php   # cache-aside: lokal finden oder von OMDb holen & persistieren
├── DTOs/MovieData.php             # readonly DTO, einzige Stelle des OMDb-Mappings
├── Services/OmdbService.php       # alle OMDb-HTTP-Calls + Level-1-Cache
├── Livewire/
│   ├── MovieSearch.php            # Suche + lokales Avg-Enrichment
│   ├── MovieDetail.php            # Details, Rating, Watchlist-Toggle
│   ├── RatedMoviesList.php        # öffentliche Liste
│   └── Watchlist.php              # Dashboard
├── Models/{Movie,Rating,User}.php
└── Policies/RatingPolicy.php
resources/views/
├── components/movie-poster.blade.php   # Poster mit graceful Fallback (Alpine)
└── livewire/…                          # zugehörige Views
```

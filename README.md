# AroStream

A Netflix-like streaming platform for Ateso/Luganda translated movies, built with:

- Laravel 11 (PHP 8.3)
- PostgreSQL
- Redis (cache + queues + rate limiting support)
- Blade + Tailwind
- Sanctum API for future Flutter app

## Implemented MVP

- Auth (web + API token auth with Sanctum)
- Roles (`user`, `admin`) + subscription status (`free`, `premium`)
- Movie catalog with filters: genre, language, VJ, search
- Trending movies (cached in Redis for 10 minutes)
- Favorites (watchlist)
- Reviews + ratings (1 review per user per movie, update-able)
- Continue watching (watch progress tracking)
- Player page with `hls.js` + resolution selection
- Preview clips on hover/tap (or poster fallback)
- Free watch limit: `30 minutes / rolling 24 hours` for free users
- Signed streaming playlist URLs
- Signed expiring download URLs (default 10 minutes)
- Download access/limits via config
- Admin CRUD for Movies, Genres, Languages, VJs
- Docker Compose stack: PHP-FPM, Nginx, PostgreSQL, Redis, queue worker

## Demo Seed Data

`php artisan migrate:fresh --seed` seeds:

- Languages: Ateso, Luganda
- VJs: VJ Suldan, VJ Aro, VJ Teso Star
- Genres: Action, Drama, Comedy, Romance, Sci-Fi, Thriller
- Sample published movies with placeholder posters + demo HLS/download URLs
- Demo users:
  - Admin: `admin@arostream.local` / `password`
  - Free user: `free@arostream.local` / `password`
  - Premium user: `premium@arostream.local` / `password`

## Local Run (Without Docker)

1. Install deps:
```bash
composer install
npm install
```

2. Configure env:
```bash
cp .env.example .env
php artisan key:generate
```

3. Set DB/Redis in `.env` (PostgreSQL + Redis recommended), then:
```bash
php artisan migrate --seed
```

4. Start:
```bash
php artisan serve
php artisan queue:work
npm run dev
```

## Run With Docker

1. Copy env and key:
```bash
cp .env.example .env
```

2. Start containers:
```bash
docker compose up -d --build
```

3. Install app deps inside container (first run):
```bash
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

4. Build frontend:
```bash
npm install
npm run build
```

5. Open:
- App: `http://localhost:8000`

## Quota + Download Config

Set in `.env`:

```env
FREE_DAILY_SECONDS=1800
DOWNLOADS_PREMIUM_ONLY=true
FREE_DAILY_DOWNLOAD_LIMIT=1
DOWNLOAD_URL_MINUTES=10
PLAYLIST_URL_MINUTES=10
```

## API Endpoints (Implemented)

Base URL: `/api`

### Auth (Sanctum)

- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/auth/logout` (auth)

Example login:
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"login":"free@arostream.local","password":"password"}'
```

### Catalog

- `GET /api/movies?search=&genre=&language=&vj=&sort=trending|new|rating&page=`
- `GET /api/movies/{slug}`
- `GET /api/trending`

### Actions (auth required)

- `POST /api/movies/{id}/favorite`
- `DELETE /api/movies/{id}/favorite`
- `POST /api/movies/{id}/review`
- `POST /api/playback/start`
- `POST /api/playback/heartbeat`
- `POST /api/playback/stop`
- `POST /api/movies/{id}/download-link`

Playback start payload:
```json
{
  "movie_id": 1
}
```

Playback heartbeat payload:
```json
{
  "movie_id": 1,
  "view_id": 12,
  "seconds_watched_delta": 15,
  "last_position_seconds": 320
}
```

### Profile (auth required)

- `GET /api/me`

Returns user, profile, favorites, and free quota state.

## Signed Streaming + Downloads

- Playlist route: `GET /stream/{movie}/master.m3u8` (signed)
- Download route: `GET /download/{movie}` (signed, expiring)

Both links are generated from backend endpoints; direct access without valid signature is blocked.

## Adding Real HLS Assets

1. Upload HLS outputs (`master.m3u8` + variant playlists/segments) to your storage disk (local/S3).
2. In admin movie edit, set:
   - `hls_master_path` (URL or storage path)
   - optional `preview_clip_path`
   - optional `download_file_path`
   - `renditions_json` as comma list (`auto,360p,480p,720p,1080p`)
3. Publish movie (`status=published`).

If you use S3-compatible storage in production, configure `FILESYSTEM_DISK=s3` + standard AWS/S3 env vars.

## Key Backend Files

- Quota logic: `app/Services/FreeQuotaService.php`
- Playback tracking: `app/Services/PlaybackService.php`
- Trending cache: `app/Services/TrendingService.php`
- Download signing/limits: `app/Services/DownloadService.php`
- API controllers: `app/Http/Controllers/Api/*`
- Web streaming routes/controllers: `routes/web.php`, `app/Http/Controllers/StreamController.php`
- Schema: `database/migrations/*`
- Docker: `docker-compose.yml`, `docker/php/Dockerfile`, `docker/nginx/default.conf`

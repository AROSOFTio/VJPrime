# VJPrime

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
- Movie + series episode support (`content_type`, season/episode metadata)
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
- Admin upload workflow for poster/backdrop/preview/download files
- Pesapal premium checkout (web + API checkout endpoint)
- Docker Compose stack: PHP-FPM, Nginx, PostgreSQL, Redis, queue worker

## Demo Seed Data

`php artisan migrate:fresh --seed` seeds:

- Languages: Ateso, Luganda
- VJs: VJ Suldan, VJ Aro, VJ Teso Star
- Genres: Action, Drama, Comedy, Romance, Sci-Fi, Thriller
- Sample published movies with placeholder posters + demo HLS/download URLs
- Demo users:
  - Admin: `admin@vjprime.local` / `password`
  - Free user: `free@vjprime.local` / `password`
  - Premium user: `premium@vjprime.local` / `password`

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

PESAPAL_ENABLED=false
PESAPAL_BASE_URL=https://pay.pesapal.com/v3
PESAPAL_CONSUMER_KEY=
PESAPAL_CONSUMER_SECRET=
PESAPAL_CURRENCY=UGX
PESAPAL_CALLBACK_URL=https://vjprime.arosoft.io/billing/pesapal/callback
PESAPAL_IPN_URL=https://vjprime.arosoft.io/billing/pesapal/ipn
PESAPAL_NOTIFICATION_TYPE=GET
PESAPAL_NOTIFICATION_ID=
PESAPAL_DEFAULT_PLAN=daily
PESAPAL_PLAN_DAILY_AMOUNT=1000
PESAPAL_PLAN_DAILY_DAYS=1
PESAPAL_PLAN_WEEKLY_AMOUNT=6000
PESAPAL_PLAN_WEEKLY_DAYS=7
PESAPAL_PLAN_BIWEEKLY_AMOUNT=11000
PESAPAL_PLAN_BIWEEKLY_DAYS=14
PESAPAL_PLAN_MONTHLY_AMOUNT=21000
PESAPAL_PLAN_MONTHLY_DAYS=30
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
  -d '{"login":"free@vjprime.local","password":"password"}'
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
- `POST /api/billing/pesapal/checkout`
- `GET /api/billing/payments`

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

## Pesapal Checkout (Web)

1. Set all `PESAPAL_*` values in `.env`.
2. Login as a free user.
3. Go to `Account` -> `Upgrade`.
4. Choose a plan and click `Pay with Pesapal`.
5. After successful callback/IPN, user subscription is set to `premium`.

## Signed Streaming + Downloads

- Playlist route: `GET /stream/{movie}/master.m3u8` (signed)
- Download route: `GET /download/{movie}` (signed, expiring)

Both links are generated from backend endpoints; direct access without valid signature is blocked.

## Adding Real HLS Assets / Uploading Content

1. In admin, open `Movies` -> `Add Movie or Series Episode`.
2. Choose `Movie` or `Series Episode`, then fill metadata.
3. You can provide direct URLs/paths or upload files:
   - `poster_file` / `backdrop_file` (stored on public disk)
   - `hls_master_upload` (stored on default filesystem disk)
   - `preview_clip_upload` (stored on public disk)
   - `download_file_upload` (stored on default filesystem disk)
4. For full HLS adaptive playback, upload the full HLS output (`master.m3u8` + variants + segments) to your storage disk and set `hls_master_path`.
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

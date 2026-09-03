# CoCAI — Agent Guide

## Project Overview

**CoCAI** is a Persian-language Clash of Clans AI assistant built with **Laravel 11 + Vue 3 + Inertia + Tailwind CSS**. Players enter their Clash player tag, the app fetches live profile data from the Clash of Clans player API, and then provides:

- AI-powered daily upgrade task & calendar
- War / farm strategy recommendations
- Free-form Persian chatbot coach
- Progress / "rush" analysis based on real hero & lab data
- Town Hall and Builder Hall base map browser (sourced from Clasher.us)
- AI Cloner (multi-game): upload a screenshot → Clash of Clans Home/Builder layout reconstruction (44×44, public share link, in-game copy link only via archive match) or Clash Royale deck reading → official deck copy link

## Architecture

| Layer | Tech |
|-------|------|
| Backend | Laravel 11 (PHP 8.2+), SQLite default, Pest tests |
| Frontend | Vue 3 + Inertia.js v2 + Vite + Tailwind CSS |
| AI Gateway | NabuGate (OpenAI-compatible), configured in `config/services.php` |
| Game API | Clash of Clans player API (token required) |
| Data | Reference JSONs in `database/data/coc/` (heroes, units, armies) |
| Crawlers | Node/Puppeteer (`crawler.js`) + Laravel command (`fetch:clasher`) |

## Important Code Paths

- `routes/web.php` — all web routes including dashboard and AI endpoints.
- `app/Http/Controllers/DashboardController.php` — main dashboard data + map filtering.
- `app/Http/Controllers/TaskController.php` & `ChatbotController.php` — AI task & chat.
- `app/Services/ProgressionService.php` — deterministic player analysis (no invented numbers).
- `app/Services/ChatbotService.php` — LLM calls grounded by `ProgressionService` facts.
- `app/Services/ClashOfClansService.php` — fetches & caches player data, manages trophy logs.
- `app/Console/Commands/FetchClasher.php` — imports maps, units, and guides from Clasher.us.
- `app/Services/BaseClone/` — Cloner engine: `Games/GameRegistry` + adapters (`CocHomeAdapter`, `CocBuilderAdapter`, `ClashRoyaleDeckAdapter`), `LayoutGridMapper` (image % → 44×44 grid, deterministic), `BuildingCatalog`/`BuilderBaseCatalog` (footprints/labels), `CardCatalog` (Clash Royale card ids from `database/data/cr/cards.json`), `ImageHasher` + `LayoutMatcher` (dHash match against `maps.image_hash`), `BaseCloneService` (orchestration). Add a new Supercell game by implementing `GameAdapter` and registering it in `GameRegistry`.
- `app/Services/AI/LayoutVisionExtractor.php` / `DeckVisionExtractor.php` — full-layout and deck Vision prompts (extend `BaseVisionAnalyzer`).
- `app/Http/Controllers/BaseCloneController.php` — `/api/base-clones` + public `/base/{slug}` page (`resources/js/Pages/BaseClone/Show.vue`).
- `resources/js/Pages/Dashboard.vue` — main SPA page.
- `resources/js/Components/Dashboard/` — reusable dashboard widgets.

## Known Mismatches / Things to Watch

1. **Map schema mismatch**: `app/Models/Map.php` uses `name/image_url/...`, the old standalone `crawler.js` uses `title/image_path/town_hall`. The canonical schema is the Laravel migration (`maps` table). Prefer `FetchClasher` over `crawler.js`.
2. **`topics.hall_type` / `hall_level` queried but missing**: `DashboardController` filters by these columns. If filtering maps by Town Hall level, ensure the columns exist and `FetchClasher` populates them.
3. **`MapCrawlerService` is a stub**: Do not rely on `MapController::crawlMaps` for production data; use `php artisan fetch:clasher`.
4. **`PlayerTagForm.vue` is empty**: The player-tag form is currently inline inside `Dashboard.vue`.
5. **No node_modules by default**: Run `npm install` before `npm run dev` / `npm run build`.
6. **Map archive**: the real 13k-map archive (with in-game links) comes from the iCloud dump `cocai.sql` (2025-01-22); prod and local `cocai` were restored from it on 2026-09-03. Run `maps:hash` (full images) and `maps:signature` after importing.
7. **In-game layout links cannot be synthesized**: `link.clashofclans.com/...action=OpenLayout&id=TH16:HV:<32 chars>` is a 24-byte opaque reference to a layout stored on Supercell's servers. The Base Cloner therefore only returns a `copy_link` when the uploaded image matches an archived map (`maps.image_hash`, populated by `maps:hash`).

## Development Commands

```bash
# PHP dependencies
composer install

# JS dependencies
npm install

# Local dev (runs Laravel, queue, logs, Vite)
composer run dev

# Run tests (in-memory SQLite so the dev MySQL DB is NOT wiped)
DB_CONNECTION=sqlite DB_DATABASE=:memory: CACHE_STORE=array ./vendor/bin/pest

# Refresh game profiles (cron-friendly)
php artisan update:game_profiles

# Import maps/units/guides from Clasher.us
php artisan fetch:clasher

# Compute perceptual hashes for archived map images (needed for Base Cloner in-game link matching)
php artisan maps:hash

# Refresh building sprites (local units + Fandom wiki API)
php artisan coc:sprites

# Refresh Clash Royale card ids (official API if CLASH_ROYALE_API_TOKEN is set, else RoyaleAPI data)
php artisan cr:cards
```

## Environment Variables

Copy `.env.example` to `.env` and set at minimum:

```env
APP_KEY=...                 # generated by `php artisan key:generate`
DB_CONNECTION=sqlite
CLASH_API_BASE=https://api.clashofclans.com/v1/players/
CLASH_API_TOKEN=...         # developer token from Supercell
NABU_BASE_URL=...           # your OpenAI-compatible gateway
NABU_API_KEY=...
NABU_MODEL=nabu-smart
```

## Conventions

- Use **Persian (Farsi)** for user-facing copy.
- Keep AI answers grounded by `ProgressionService` facts; never invent upgrade costs or caps.
- Prefer deterministic calculations for features (tasks, calendar, rush score); use LLM only for natural-language polish.
- Vue components are Options API; match existing style for consistency.
- Run `./vendor/bin/pest` and `npm run build` before declaring work done.

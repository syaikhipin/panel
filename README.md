# Ploi Panel + Bundled API

The [ploi-core](https://github.com/ploi/ploi-core) hosting panel and a Ploi-API-compatible backend bundled as **one Docker image** — the panel runs without the ploi.io backbone. One Caddy instance (FrankenPHP) serves both:

- `:{$PORT:80}` → the panel (`panel/`, what your users visit)
- `:{$API_PORT:8081}` → the bundled Ploi-compatible API (`api/`, reached by the panel over `127.0.0.1` only)

Both apps share one external PostgreSQL database (disjoint tables: the panel owns its tables, the API owns `provisioned_resources`). Provisioning is stubbed: resources report `busy` → `active` after `PLOI_STUB_PROVISION_DELAY` seconds, mirroring the panel's `Fetch*Status` polling.

## Layout

```
panel/   ploi-core — the Laravel 12 + Filament v4 hosting panel
api/     Ploi-API-compatible backend (the "core-api.ploi.io" drop-in)
docker/  Caddyfile + entrypoint for the combined image
```

## Run

```bash
docker build -t ploi-panel .
docker run -p 8080:8080 \
  -e PORT=8080 \
  -e DATABASE_URL='pgsql://user:pass@host:5432/ploi_panel' \
  -e APP_KEY=base64:... \
  -e PLOI_TOKEN=change-me \
  -e ADMIN_EMAIL=admin@example.com \
  -e ADMIN_PASSWORD=secret \
  ploi-panel
```

The entrypoint (retries while the database comes up): migrates both apps, seeds the panel admin (`ADMIN_EMAIL`/`ADMIN_PASSWORD`, skipped if the user exists), publishes Filament assets, then starts Caddy. Log in to the panel with the admin credentials.

## Configuration

| Env | Default | Meaning |
|---|---|---|
| `DATABASE_URL` (or `DB_URL`) | — | External PostgreSQL connection string (both apps) |
| `APP_KEY` | generated at boot | Set for a stable key across deploys |
| `PLOI_TOKEN` | — | Bearer token the panel uses against the bundled API (also gates "installation complete") |
| `ADMIN_EMAIL` / `ADMIN_PASSWORD` / `ADMIN_NAME` | — | First panel admin, seeded once at boot |
| `PLOI_API_URL` | `http://127.0.0.1:8081/` | Where the panel reaches the API (internal) |
| `PLOI_STUB_PROVISION_DELAY` | `2` | Seconds until a stubbed resource reports `active` |
| `SESSION_DRIVER` / `QUEUE_CONNECTION` / `CACHE_STORE` | `database` / `sync` / `file` | Panel runtime (sessions in Postgres survive redeploys; jobs run inline) |

## Deploy (Render)

`render.yaml` provisions one Postgres, links `DATABASE_URL`, generates `APP_KEY` + `PLOI_TOKEN`, and prompts for `ADMIN_EMAIL`/`ADMIN_PASSWORD`. Health check `/` (redirects to login). External Postgres: delete the `databases:` block and set `DATABASE_URL` yourself.

## Tests (API contract)

```bash
cd api && vendor/bin/pest
```

Defaults in `api/phpunit.xml`: `ploi_core_api_test` on `127.0.0.1:5432` (override via env vars).

## Known ploi-core client bugs (not caused by the bundled API)

`Daemon::delete()`, `NetworkRule::delete()`, `SystemUser::delete()`, `Queue::delete()` are typed `: stdClass` but have no `return` statement — calling them throws a `TypeError` regardless of backend. The DELETE endpoints work fine over plain HTTP.

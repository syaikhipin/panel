# Ploi Core API Stub

A standalone, Ploi-API-compatible backend for [ploi-core](https://github.com/ploi/ploi-core). It implements the exact HTTP surface the ploi-core client (`app/Services/Ploi/**`) consumes, so the panel runs **without the ploi.io backbone**.

All provisioning is stubbed: resources are recorded in PostgreSQL and report `busy` → `active` after a configurable delay, mirroring ploi-core's `Fetch*Status` polling jobs. No servers are actually provisioned.

## Requirements

- PHP 8.4+ with `pdo_pgsql`
- An external PostgreSQL database (any provider — Render Postgres, Neon, RDS, self-hosted)

## Configuration

| Env | Default | Meaning |
|---|---|---|
| `DB_URL` (or `DATABASE_URL`) | — | Postgres connection string, e.g. `pgsql://user:pass@host:5432/db` |
| `DB_CONNECTION` | `pgsql` | Database driver |
| `DB_HOST` / `DB_PORT` / `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | — | Alternative to `DB_URL`, standard Laravel variables |
| `APP_KEY` | generated at boot | Set it for a stable key across deploys |
| `PLOI_STUB_TOKEN` | `null` | Required bearer token; when empty, any non-empty token is accepted |
| `PLOI_STUB_PROVISION_DELAY` | `2` | Seconds until a created resource reports status `active` |

## Running

```bash
composer install
php artisan migrate
php artisan serve --host=0.0.0.0 --port=8899
```

## Wiring up ploi-core

In ploi-core's `.env`:

```dotenv
PLOI_API_URL=http://127.0.0.1:8899/
PLOI_TOKEN=any-token
```

`PLOI_API_URL` is read from `config/services.php` (`services.ploi-api.url`) — every API call goes through `Ploi::makeAPICall()` with this base URL and `Authorization: Bearer {PLOI_TOKEN}`.

## Tests

```bash
vendor/bin/pest
```

Tests run against PostgreSQL (defaults in `phpunit.xml`: `ploi_core_api_test` on `127.0.0.1:5432`, user/password `postgres`/`postgres`; override via real env vars). The contract tests in `tests/Feature/PloiContractTest.php` pin the response shapes ploi-core's jobs/actions depend on (`data.*` fields, the `422` duplicate-domain message, top-level `database_name`, exactly-200 deletes).

## Deploying

### Docker (Render, Fly.io, or any container host)

The image is FrankenPHP (Caddy + PHP in one binary). It runs migrations on boot (with retry while the database comes up) and binds Caddy to `$PORT`.

```bash
docker build -t ploi-core-api .
docker run -p 8899:8899 \
  -e PORT=8899 \
  -e DB_URL='pgsql://user:pass@host:5432/ploi_core_api' \
  -e APP_KEY=base64:... \
  -e PLOI_STUB_TOKEN=change-me \
  ploi-core-api
```

Health check path: `/up`.

### Render Blueprint

`render.yaml` provisions a Render Postgres database, links it via `DB_URL`, and generates `APP_KEY` + `PLOI_STUB_TOKEN`. Connect the repo in Render and create a Blueprint. To use an **external** Postgres instead: delete the `databases:` section and set `DB_URL` manually (Render also exposes `DATABASE_URL`, which the entrypoint picks up automatically).

## Implemented endpoints

All responses use the Ploi envelope `{"data": ...}` (object for singletons, arrays for collections); deletes that ploi-core checks return exactly HTTP 200; validation errors return `422` with `{"errors": {"field": ["message"]}}`.

| Family | Endpoints |
|---|---|
| Ping | `GET/POST /ping` (`GET` reports `user.subscription: "unlimited"`) |
| User | `GET /user`, `GET /user/server-providers[/{id}]` (stub Hetzner + DigitalOcean plans/regions) |
| Servers | `POST/GET /servers`, `GET/DELETE /servers/{id}`, `GET /servers/{id}/logs` |
| Sites | `POST/GET /servers/{sid}/sites`, `GET/DELETE /servers/{sid}/sites/{id}`, `.../log`, `.../php-version`, `.../repository`, `.../deploy`, `.../deploy/script` (GET/PATCH) |
| Site children | `aliases` (GET/POST/DELETE by alias), `certificates`, `redirects`, `queues` (+ `restart`, `toggle-pause`), app install/uninstall `.../sites/{id}/{wordpress\|nextcloud}` |
| Server children | `databases` (+ `.../users/{id}/reset-password`), `crontabs`, `daemons` (+ `restart`, `toggle-pause`), `network-rules`, `system-users` |
| Synchronize | `GET /synchronize/servers[/{id}]`, `GET /synchronize/sites[/{id}]` |

Response fields match what ploi-core's jobs/actions read: `data.id`, `data.status` (`created`/`busy`/`active`), server `ip_address`/`internal_ip`/`ssh_port`/`installed_php_versions`, site `domain`/`server_id`/`php_version`/`project_type`/`system_user`, database create `data.users[0].id`, app install top-level `database_name`, `reset-password` `data.new_password`, duplicate site domain `errors.root_domain: ["The root domain has already been taken."]`.

## Going beyond the stub

Everything remote lives in `app/Http/Controllers/Ploi/*` over the `provisioned_resources` table. To do real provisioning, replace the create/destroy handlers with calls to your own engine (Ansible, an agent on the target server, another panel's API) and keep the response shapes unchanged.

## Known ploi-core client bugs (not caused by this service)

`Daemon::delete()`, `NetworkRule::delete()`, `SystemUser::delete()`, `Queue::delete()` are typed `: stdClass` but have no `return` statement — calling them throws a `TypeError` regardless of backend. The corresponding DELETE endpoints here work fine over plain HTTP.

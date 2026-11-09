# Animal Facts API — PHP Interview Challenge

A small REST API built with **Laravel 9 / PHP 8.1** that serves curated facts about animals.
The original project only exposed **Cat Facts**; this submission fixes the broken Cat Facts
storage path and adds a complete, convention-consistent **Dog Facts** resource.

---

## Table of contents

1. [What was delivered](#what-was-delivered)
2. [Requirements](#requirements)
3. [Installation](#installation)
   - [Option A — Laravel Sail (recommended)](#option-a--laravel-sail-recommended)
   - [Option B — plain Docker Compose](#option-b--plain-docker-compose)
4. [Running the tests](#running-the-tests)
5. [API reference](#api-reference)
   - [Cat Facts](#cat-facts)
   - [Dog Facts](#dog-facts)
   - [Pagination](#pagination)
   - [Error format](#error-format)
6. [Project structure](#project-structure)
7. [Design decisions](#design-decisions)
8. [The bug that was fixed](#the-bug-that-was-fixed)
9. [Verification checklist](#verification-checklist)
10. [Troubleshooting](#troubleshooting)

---

## What was delivered

| Requirement | Status | Where |
|---|---|---|
| Fix Cat Facts storing | ✅ Fixed | `app/Http/Requests/API/V1/CatFacts/Rest/StoreRequest.php` |
| All Cat Facts feature tests pass | ✅ 18/18 | `tests/Feature/API/V1/CatFacts` |
| List Dog Facts (paginated) | ✅ | `GET /api/v1/dog-facts` |
| Configurable page size | ✅ `pageSize` query parameter | `IndexRequest` + `DogFactRepository` |
| Default page size of 25 | ✅ | `config/pagination.php` |
| Request additional pages | ✅ `page` query parameter | Laravel paginator |
| Store / Show / Update / Delete Dog Facts | ✅ | `app/Http/Controllers/API/V1/DogFacts/Rest` |
| `fact` required on store **and** update | ✅ 422 on missing/`null`/non-string | `StoreRequest`, `UpdateRequest` |
| Dog Facts feature tests | ✅ 26 tests | `tests/Feature/API/V1/DogFacts` |
| JSON in, JSON out | ✅ including framework errors | `ForceJsonResponse` middleware + exception handler |
| Detailed README | ✅ this file | |
| `prompt.md` with the full AI conversation | ✅ | [`prompt.md`](prompt.md) |

**Test suite: 44 tests, 131 assertions, all green.**

---

## Requirements

- Docker Engine **20.10+** with Docker Compose v2 (`docker compose`)
- Nothing else — PHP, Composer and MySQL all run in containers.

> Tested with Docker 29.9.3 / Compose v5.7.0 on Arch Linux (WSL2).

---

## Installation

```bash
git clone <this-repository-url>
cd php-interview-challenge

cp .env.example .env
```

`.env.example` is complete and needs **no changes** for local use (app runs on
`http://localhost:8042`, MySQL is forwarded on `33042`).

### Option A — Laravel Sail (recommended)

```bash
docker run --rm -v "$(pwd)":/opt -w /opt composer:2 \
  bash -c "composer install --ignore-platform-reqs"

vendor/bin/sail up -d
vendor/bin/sail artisan key:generate
vendor/bin/sail artisan migrate --seed
```

Then open <http://localhost:8042/api/v1>.

> **Why `--ignore-platform-reqs`?**
> The official `composer:2` image now ships PHP 8.5, while this project's
> `composer.lock` was resolved for PHP 8.1 and pins packages (`nette/schema`,
> `nette/utils`) that declare `php < 8.2`. The flag only relaxes the *install-time*
> platform check inside the throwaway Composer container; the code still runs on
> the project's PHP 8.1 runtime. If you happen to have PHP 8.1 on the host you can
> run `composer install` without the flag.

### Option B — plain Docker Compose

Sail is a thin wrapper around `docker compose`, so the same environment can be
driven without it:

```bash
docker compose build laravel.test
docker compose up -d
docker compose exec laravel.test composer install --no-interaction
docker compose exec laravel.test php artisan key:generate
docker compose exec laravel.test php artisan migrate --seed
```

Both options use the **same** `docker-compose.yml`. `WWWUSER` / `WWWGROUP`
default to `1000` when they are not exported, so `docker compose` works with a
blank environment; Sail overrides them with your real `UID`/`GID`.

### Ports

| Service | Host port | Container port |
|---|---|---|
| API (`app`) | **8042** (`APP_PORT`) | 80 |
| MySQL | 33042 (`FORWARD_DB_PORT`) | 3306 |

### Useful commands

```bash
vendor/bin/sail down          # stop the environment
vendor/bin/sail down -v       # stop and drop the database volume
vendor/bin/sail test          # run the whole test suite
vendor/bin/sail artisan migrate:fresh --seed
vendor/bin/sail shell         # bash inside the app container
```

---

## Running the tests

```bash
vendor/bin/sail test
```

or, if you are not using Sail:

```bash
docker compose exec laravel.test ./vendor/bin/phpunit
```

Expected output:

```
Tests:    44 passed (131 assertions)
```

Tests run against the `testing` MySQL database created automatically by
`docker/mysql/create-testing-database.sh`, so they never touch your development
data.

To run a single group:

```bash
vendor/bin/sail test --filter=DogFacts
vendor/bin/sail test --filter=CatFacts
```

---

## API reference

Base URL: `http://localhost:8042/api/v1`

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1` | API name, version and available resources |
| `GET` | `/api/v1/cat-facts` | Paginated list of cat facts |
| `POST` | `/api/v1/cat-facts` | Store a cat fact |
| `GET` | `/api/v1/cat-facts/{id}` | Retrieve one cat fact |
| `PUT` | `/api/v1/cat-facts/{id}` | Update a cat fact |
| `DELETE` | `/api/v1/cat-facts/{id}` | Delete a cat fact |
| `GET` | `/api/v1/dog-facts` | Paginated list of dog facts |
| `POST` | `/api/v1/dog-facts` | Store a dog fact |
| `GET` | `/api/v1/dog-facts/{id}` | Retrieve one dog fact |
| `PUT` | `/api/v1/dog-facts/{id}` | Update a dog fact |
| `DELETE` | `/api/v1/dog-facts/{id}` | Delete a dog fact |

All requests and responses use `application/json`. The API also returns JSON for
framework-level errors (`404`, `422`, `500`, …) regardless of the `Accept` header
sent by the client.

### Root

```bash
curl http://localhost:8042/api/v1
```

```json
{
  "name": "Jobsity PHP Human Interview challenge API",
  "version": 1,
  "resources": {
    "cat-facts": "http://localhost:8042/api/v1/cat-facts",
    "dog-facts": "http://localhost:8042/api/v1/dog-facts"
  }
}
```

### Pagination

Both list endpoints accept:

| Query parameter | Type | Default | Description |
|---|---|---|---|
| `pageSize` | integer ≥ 1 | **25** | Number of facts per page |
| `page` | integer ≥ 1 | `1` | Page to return |

```bash
curl "http://localhost:8042/api/v1/dog-facts?page=2&pageSize=3"
```

```json
{
  "current_page": 2,
  "data": [
    { "id": 4, "fact": "This is dog fact number 4", "created_at": "…", "updated_at": "…" },
    { "id": 5, "fact": "This is dog fact number 5", "created_at": "…", "updated_at": "…" },
    { "id": 6, "fact": "This is dog fact number 6", "created_at": "…", "updated_at": "…" }
  ],
  "first_page_url": "http://localhost:8042/api/v1/dog-facts?page=1",
  "from": 4,
  "last_page": 84,
  "last_page_url": "http://localhost:8042/api/v1/dog-facts?page=84",
  "links": [ … ],
  "next_page_url": "http://localhost:8042/api/v1/dog-facts?page=3",
  "path": "http://localhost:8042/api/v1/dog-facts",
  "per_page": 3,
  "prev_page_url": "http://localhost:8042/api/v1/dog-facts?page=1",
  "to": 6,
  "total": 250
}
```

The default page size is 25 for both resources and comes from
`config/pagination.php` (`PAGINATION_DEFAULT_SIZE`).

Invalid pagination input is rejected with `422` instead of blowing up:

```bash
curl "http://localhost:8042/api/v1/dog-facts?pageSize=abc"
```

```json
{
  "message": "The page size must be an integer.",
  "errors": { "pageSize": ["The page size must be an integer."] }
}
```

### Dog Facts

#### Store

```bash
curl -X POST http://localhost:8042/api/v1/dog-facts \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"fact":"A dog'"'"'s sense of smell is 40 times better than ours."}'
```

`200 OK`

```json
{
  "fact": "A dog's sense of smell is 40 times better than ours.",
  "updated_at": "2026-11-09T12:41:19.000000Z",
  "created_at": "2026-11-09T12:41:19.000000Z",
  "id": 275
}
```

Missing / `null` / non-string `fact` → `422`:

```json
{
  "message": "The fact field is required.",
  "errors": { "fact": ["The fact field is required."] }
}
```

#### Show

```bash
curl http://localhost:8042/api/v1/dog-facts/275
```

```json
{
  "id": 275,
  "fact": "A dog's sense of smell is 40 times better than ours.",
  "created_at": "2026-11-09T12:41:19.000000Z",
  "updated_at": "2026-11-09T12:41:19.000000Z"
}
```

#### Update

`PUT` (full replacement of the fact text, which is required):

```bash
curl -X PUT http://localhost:8042/api/v1/dog-facts/275 \
  -H "Content-Type: application/json" \
  -d '{"fact":"Dogs have three eyelids."}'
```

```json
{
  "id": 275,
  "fact": "Dogs have three eyelids.",
  "created_at": "2026-11-09T12:41:19.000000Z",
  "updated_at": "2026-11-09T12:41:19.000000Z"
}
```

#### Delete

```bash
curl -X DELETE http://localhost:8042/api/v1/dog-facts/275
```

`204 No Content` (empty body). Subsequent `GET` returns `404`.

#### Cat Facts

Identical contract under `/api/v1/cat-facts`. The Cat Facts tests are unchanged
apart from the bug fix described below.

### Error format

| Status | Body |
|---|---|
| `404` | `{"message":"Not Found"}` |
| `422` | `{"message":"…","errors":{"field":["…"]}}` |
| `500` | `{"message":"…"}` |

---

## Project structure

```
app/
├── Exceptions/Handler.php                     # JSON rendering for HTTP errors
├── Http/
│   ├── Controllers/API/V1/
│   │   ├── CatFacts/Rest/                     # Index/Show/Store/Update/Destroy
│   │   └── DogFacts/Rest/                     # mirrored implementation
│   ├── Middleware/ForceJsonResponse.php       # API always negotiates JSON
│   └── Requests/API/V1/
│       ├── CatFacts/Rest/{Index,Store,Update}Request.php
│       └── DogFacts/Rest/{Index,Store,Update}Request.php
├── Models/{CatFact,DogFact}.php
└── Repositories/{CatFactRepository,DogFactRepository}.php

config/pagination.php                          # default page size (25)
database/
├── migrations/2022_10_18_225007_create_cat_facts_table.php
├── migrations/2022_10_19_000000_create_dog_facts_table.php
└── seeders/{DatabaseSeeder,CatFactSeeder,DogFactSeeder}.php

routes/
├── api.php                                    # v1 group
└── v1/{cat-facts,dog-facts}.php               # per-resource route files

tests/Feature/API/V1/{CatFacts,DogFacts}/Rest/ # 44 feature tests

docker/8.1/                                    # vendored + patched Sail runtime
docker/mysql/create-testing-database.sh
docker-compose.yml
```

Each resource follows the same layered convention that already existed for Cat
Facts: **route → invokable controller → FormRequest (validation) → repository →
model**. Dog Facts mirrors that structure one-to-one instead of inventing a
different pattern.

---

## Design decisions

### Repositories keep persistence out of controllers

`DogFactRepository` is intentionally a sibling of `CatFactRepository` with the
same public surface (`getAllWithPagination`, `getById`, `store`, `update`,
`destroy`). Controllers never touch Eloquent directly, so the storage layer can
be swapped later without changing the HTTP layer — which is exactly the
"flexible / well-designed" goal from the brief.

### Validation lives in FormRequests

Creating `DogFacts/Rest/{Index,Store,Update}Request` keeps the controller bodies
free of validation noise and makes the rules reusable and independently testable.
`Store` and `Update` both require `fact` to be a non-empty string, satisfying the
"the fact text is required" rule for both resources.

### `validated()` instead of `all()`

Controllers now pass `$request->validated()` to the repository rather than
`$request->all()`. This means unknown fields sent by a client can never reach the
persistence layer (no accidental mass assignment), and only whitelisted input is
used.

### JSON is enforced at the edge

`ForceJsonResponse` sets `Accept: application/json` for every request in the
`api` group. Combined with the `renderable` hook in `app/Exceptions/Handler.php`,
every error path — including `abort(404)`, throttling and uncaught exceptions —
returns JSON. Previously a `404` returned an HTML error page, which is wrong for
a REST API.

### Pagination input is validated

`page` and `pageSize` are validated as positive integers. Before this change,
`?pageSize=abc` produced a `500 TypeError` deep inside the query builder. Now it
is a clean `422`.

---

## The bug that was fixed

**Symptom:** every `POST /api/v1/cat-facts` request returned `403 Forbidden`,
failing 4 of the 18 existing Cat Facts tests.

**Root cause:** in
`app/Http/Requests/API/V1/CatFacts/Rest/StoreRequest.php`, the `authorize()`
method returned `false`:

```php
public function authorize()
{
    return false;   // ← every request was rejected before validation ran
}
```

Laravel's `FormRequest` runs `authorize()` *before* validation and aborts with
`403` when it returns `false`. `UpdateRequest` correctly returned `true`, which
is why storing was the only broken operation.

**Fix:** return `true` (the endpoint is public, so there is nothing to authorise
beyond validation):

```php
public function authorize()
{
    return true;
}
```

**Verification:** `vendor/bin/sail test` went from `4 failed, 14 passed` to
`18 passed` for Cat Facts, and 44/44 once Dog Facts was added.

---

## Verification checklist

Everything below was executed against a clean environment
(`vendor/bin/sail down -v` then the full setup) before submitting:

1. `vendor/bin/sail test` → **44 passed (131 assertions)**.
2. `docker compose` (no Sail) from a blank environment → build, install,
   migrate, seed and tests all succeed.
3. `GET /api/v1/dog-facts` → `per_page` is `25` by default.
4. `GET /api/v1/dog-facts?page=2&pageSize=3` → `current_page: 2`, `per_page: 3`,
   3 items, correct `next_page_url` / `prev_page_url`.
5. `POST` → `200` with the created resource; missing/`null`/numeric `fact` → `422`.
6. `PUT` → `200` with updated text; missing/`null`/numeric `fact` → `422`;
   unknown id → `404`.
7. `DELETE` → `204`, record gone; unknown id → `404`.
8. `GET /api/v1/dog-facts/999999` → `404` **with** `Content-Type: application/json`.
9. `GET /api/v1/dog-facts?pageSize=abc` → `422` (was `500` before validation).
10. Cat Facts endpoints behave exactly as before the fix, now with storing working.

---

## Troubleshooting

**`vendor/bin/sail` not found** — Composer dependencies are not installed yet:
`docker run --rm -v "$(pwd)":/opt -w /opt composer:2 bash -c "composer install --ignore-platform-reqs"`.

**`SQLSTATE[HY000] [2002] Connection refused`** — MySQL is still booting. Wait a
few seconds and re-run `vendor/bin/sail artisan migrate --seed`, or check
`docker compose logs mysql`.

**Port 8042 / 33042 already in use** — change `APP_PORT` / `FORWARD_DB_PORT`
in `.env`, then `vendor/bin/sail up -d`.

**Permission denied writing to `storage/`** — the containers must be started by
your own user (`vendor/bin/sail up -d` as a member of the `docker` group, not
via `sudo`), because the container runs as your host `UID`.

**Stale configuration after editing PHP files** — the app container has OPcache
enabled; restart it with `vendor/bin/sail restart laravel.test` if a change does
not appear.

---

## AI usage

See [`prompt.md`](prompt.md) for the full, annotated AI conversation, including
which parts were AI-assisted and what was reviewed, corrected or rejected.

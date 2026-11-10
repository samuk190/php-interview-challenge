# Code review — Animal Facts API

Date: 2026-11-10 · Reviewed commit: `a33e51e` · Scope: everything added or changed for
the challenge (Cat Facts fix, Dog Facts resource, middleware, Docker, tests, docs).

Method: every finding below was **reproduced against the running stack**
(`vendor/bin/sail up`, live `curl`/`phpunit`/`pint`). Nothing here is a hypothesis from
reading alone; each item states the command or observation that proves it.

**Verdict:** the challenge requirements are met and the suite is green (44 tests, 131
assertions, ~91% line coverage). But there is **one severe correctness bug** (route-model
`{id}` coercion allowing a delete of the wrong row) and **one high-severity data leak**
(exception messages returned to clients) that I would not ship as-is.

| # | Severity | Issue | Status |
|---|---|---|---|
| 1 | **Critical** | `{id}` coerced to int — `DELETE /dog-facts/1.5` deleted record 1 | Open |
| 2 | **High** | 500 bodies leak SQL/exception text to the client | Open |
| 3 | **High** | Non-numeric `{id}` → `500 TypeError` instead of `404` | Open |
| 4 | Medium | `fact` longer than 255 chars → `500`, unvalidated | Open |
| 5 | Medium | Tests run against the **dev** database (no isolation) | Open |
| 6 | Medium | `pageSize` unbounded (`?pageSize=100000` serialises everything) | Open |
| 7 | Low | No `orderBy` — pagination order is not guaranteed stable | Open |
| 8 | Low | Missing factories; tests hand-roll models, ignore `RefreshDatabase` | Open |
| 9 | Low | README claims tests use the `testing` DB — false | Open |
| 10 | Low | 28 files fail Pint; style differs from the project's own tooling | Open |
| 11 | Nit | Unused `use Illuminate\Http\Request` in `routes/api.php` | Open |
| 12 | Nit | Duplicate validation messages on empty `pageSize` | Open |

---

## 1. Critical — `{id}` is coerced to `int`, so aliases hit the wrong row

Controllers declare `int $id`:

```
app/Http/Controllers/API/V1/DogFacts/Rest/ShowController.php:18    __invoke(int $id)
app/Http/Controllers/API/V1/DogFacts/Rest/DestroyController.php:19 __invoke(int $id)
app/Http/Controllers/API/V1/CatFacts/Rest/DestroyController.php:19 __invoke(int $id)
```

Laravel passes the raw route segment as a string; PHP then coerces it to `int`
non-strictly unless the file declares `strict_types=1` (the controllers do not).
Anything PHP accepts as a leading-numeric string becomes that integer.

Reproduced — all of these return **record 1**:

```
/dog-facts/1       -> 200  id=1
/dog-facts/01      -> 200  id=1
/dog-facts/1.0     -> 200  id=1
/dog-facts/1.5     -> 200  id=1     <-- silently truncated
/dog-facts/1e0     -> 200  id=1
/dog-facts/%201    -> 200  id=1
```

The destructive case is the one that matters:

```
$ curl -X DELETE .../api/v1/dog-facts/1.5
HTTP 204
$ SELECT count(*) FROM dog_facts WHERE id=1;   -- 0
```

**A `DELETE` addressed to `1.5` permanently deleted resource `1`.** Two clients using
different URL spellings can silently operate on the same row, and an idempotent-looking
request destroys the wrong record. This affects **both** resources, and it is in the
pre-existing Cat Facts code too — the Dog Facts copy inherited it.

**Fix:** constrain the segment at the routing layer, which also fixes issue 3 in one move:

```php
// routes/v1/dog-facts.php (and cat-facts.php)
Route::get('{id}', ShowController::class)->whereNumber('id')->name('...');
Route::put('{id}', UpdateController::class)->whereNumber('id')->name('...');
Route::delete('{id}', DestroyController::class)->whereNumber('id')->name('...');
```

`whereNumber()` makes non-numeric segments fall through to a clean `404` and prevents
coercion of `"1.5"`. If you prefer route-model binding, `Route::get('{dogFact}', ...)`
with an `int $dogFact` type-hint plus `->whereNumber(...)` is equally safe and removes
the manual `getById`/`abort(404)` pairs. Add a regression test:

```php
public function testDoesNotDeleteOnCoercedId(): void
{
    $response = $this->deleteJson("/api/v1/dog-facts/{$this->dogFact->id}.5");
    $response->assertNotFound();
    $this->assertDatabaseHas('dog_facts', ['id' => $this->dogFact->id]);
}
```

---

## 2. High — 500 responses leak internal exception text

Every controller wraps persistence in a block that copies the raw exception message into
the response (6 occurrences):

```
app/Http/Controllers/API/V1/DogFacts/Rest/StoreController.php:25    abort(500, $exception->getMessage());
app/Http/Controllers/API/V1/DogFacts/Rest/UpdateController.php:31   abort(500, $exception->getMessage());
app/Http/Controllers/API/V1/DogFacts/Rest/DestroyController.php:30  abort(500, $exception->getMessage());
app/Http/Controllers/API/V1/CatFacts/Rest/*.php                     (same, 3x)
```

`abort(500, $msg)` builds an `HttpException` **carrying `$msg` as its own message**, so
it is not the "internal error" path of the framework — the message is rendered to the
client no matter what `APP_DEBUG` is set to.

Reproduced with `APP_DEBUG=false` (production-like) by booting the kernel and posting a
317-character fact:

```
POST /api/v1/dog-facts   -> 500
{"message":"SQLSTATE[22001]: String data, right truncated: 1406 Data too long for
 column 'fact' at row 1 (SQL: insert into `dog_facts` (`fact`, `updated_at`,
 `created_at`) values (xxxx..."}
```

That discloses the driver, the server version behaviour, the exact table, the column list
and the query. Compare the same app on an input that raises a *real* internal error
(`/api/v1/dog-facts/abc`), which correctly returns only `{"message":"Server Error"}` —
proving the leak is caused by this pattern, not by configuration.

**Fix:** never put exception text in a client-facing HTTP exception. Log it and return a
generic message:

```php
} catch (Exception $exception) {
    report($exception);              // or Log::error($exception)
    abort(500, 'Dog fact could not be saved');
}
```

The existing `!$updated` branch already uses exactly that wording, so the two paths then
behave consistently. `tests/.../StoreControllerTest::testHandlesExceptionDuringSave`
asserts only the status code, so it keeps passing.

---

## 3. High — non-numeric ids produce `500`, not `404`

```
GET  /api/v1/dog-facts/abc                 -> 500
DELETE /api/v1/dog-facts/abc               -> 500
GET  /api/v1/cat-facts/abc                 -> 500
GET  /api/v1/dog-facts/99999999999999999999 -> 500   (exceeds PHP_INT_MAX)
```

Body (with debug on) is a `TypeError`: `__invoke(): Argument #1 ($id) must be of type
int, string given`. A malformed path is a client error; `500` misreports it as a server
fault and pollutes error monitoring. Fixed by the `whereNumber()` constraint in issue 1.

---

## 4. Medium — `fact` length is validated nowhere but constrained by the schema

`Schema::create(...)` uses `$table->string('fact')` — `VARCHAR(255)` — for both resources:

```
database/migrations/2022_10_18_225007_create_cat_facts_table.php:18
database/migrations/2022_10_19_000000_create_dog_facts_table.php:18
```

Validation only says `required|string`, so a longer fact is accepted by validation and
then fails at the database:

```
$ fact='Dogs are excellent companions. ' * 12   (317 chars)
POST /api/v1/dog-facts -> 500 SQLSTATE[22001] String data, right truncated
```

The brief's rule is only "the fact text is required", so this is not a requirement miss —
but a 500 on ordinary user input is a defect. Pick one and be explicit:

```php
'fact' => ['required', 'string', 'max:255'],
```

or widen the column (`$table->text('fact')`) if facts may be longer. `max:255` turns it
into a `422` and (with issue 2 fixed) removes the last way to trigger the SQL leak
through a normal payload.

---

## 5. Medium — the test suite runs against the development database

`phpunit.xml:22` has the isolation override **commented out**:

```xml
<!-- <env name="DB_DATABASE" value="testing"/> -->
```

so `APP_ENV=testing` keeps `DB_DATABASE=api_php` from `.env`. Verified:

```
$ php artisan tinker --execute='echo config("database.connections.mysql.database");'
api_php
$ mysql -e "select count(*) from testing.cat_facts;"
ERROR 1146 (42S02): Table 'testing.cat_facts' doesn't exist
```

The `testing` database is created by `docker/mysql/create-testing-database.sh` but is
**never used and never migrated**.

Impact, measured rather than assumed:

* The suite **passes against an empty database** (I truncated both tables and got
  44/44), so there is no hidden dependency on seeded rows today.
* The suite leaves the row counts unchanged (250/250 before and after), so today's tests
  do not corrupt dev data either.
* The hazard is latent but real: any future test using `RefreshDatabase` or
  `truncate()` will **wipe the developer's seeded database**, and test runs contend with
  whatever state the dev DB is in. Also, `processIsolation="true"` in `phpunit.xml:6`
  spins up a process per test — that is inherited config, but it is why nothing is shared
  and why this went unnoticed.

**Fix:** uncomment line 22 and ensure the schema exists there:

```xml
<env name="DB_DATABASE" value="testing"/>
```

then `php artisan migrate --database=mysql --env=testing` (or add `RefreshDatabase` to the
base `TestCase`, which is the conventional route and would also remove the `tearDown`
cleanup dance).

---

## 6. Medium — `pageSize` has no upper bound

```
GET /api/v1/dog-facts?pageSize=100000   -> 200, per_page 100000, 250 rows serialised
GET /api/v1/dog-facts?pageSize=100000000 -> 200
```

`IndexRequest` only enforces `min:1`. On a seeded table this is harmless, but it is an
unbounded `LIMIT` driven straight by the query string — a cheap way to make the server
materialise and JSON-encode an entire table. Cap it:

```php
'pageSize' => 'sometimes|integer|min:1|max:100',
```

(The brief asks for a configurable page size with a default of 25; it does not ask for an
uncapped one.)

---

## 7. Low — pagination has no deterministic ordering

```
app/Repositories/CatFactRepository.php:15   CatFact::with($relations)->paginate($pageSize)
app/Repositories/DogFactRepository.php:15   DogFact::with($relations)->paginate($pageSize)
```

No `orderBy`. MySQL is free to return rows in any order without `ORDER BY`, and that
order can change between pages as rows are inserted or the plan shifts — producing
duplicated and skipped facts across pages. It happens to come back in primary-key order
today, which is exactly why this is easy to miss. Add `->orderBy('id')` (or
`->latest('id')`).

---

## 8. Low — no factories; tests build models by hand

`database/factories/` contains only `UserFactory.php`, yet both models `use HasFactory`.
Tests instead do:

```php
$this->dogFact = new DogFact();
$this->dogFact->fact = 'This is the original fact';
$this->dogFact->save();
```

plus a bespoke `cleanUpModels()` in `tests/TestCase.php` driven from `tearDown()` in every
class. This works, but it is the pattern that makes issue 5 dangerous (manual cleanup is
what keeps the dev DB intact) and it makes the tests longer than they need to be. A
`DogFactFactory`/`CatFactFactory` plus `RefreshDatabase` would delete a lot of
boilerplate.

---

## 9. Low — README states something untrue about test isolation

`README.md:185-186` claims:

> Tests run against the `testing` MySQL database created automatically by
> `docker/mysql/create-testing-database.sh`, so they never touch your development data.

Per issue 5, they run against `api_php`. This must be corrected in the same commit that
fixes issue 5 — a reviewer following the README would be misled about their own data.

---

## 10. Low — the code does not pass the project's own formatter

`laravel/pint` is a declared dev dependency. `vendor/bin/pint --test` reports **28 files**
failing, including files added in this change:

```
app/Exceptions/Handler.php
app/Http/Controllers/API/V1/DogFacts/Rest/{Destroy,Show,Store,Update}Controller.php
tests/Feature/API/V1/DogFacts/Rest/*Test.php
```

This is largely because the new code imitates the existing `CatFacts` style (`!$catFact`,
`declare(strict_types=1)` immediately after `<?php`, etc.), which itself predates Pint.
That is a defensible convention-over-novelty call, but if Pint is in `require-dev` the
expectation is that `pint --test` is green. Either run `vendor/bin/pint` once (it will
reformat the legacy files too — a separate, mechanical commit) or drop Pint from
`require-dev`. Silently failing the project's formatter is the worst of the three options.

---

## 11. Nit — unused import

`routes/api.php:3` imports `Illuminate\Http\Request` and never uses it. Pre-existing, but
it is one line to remove.

## 12. Nit — duplicated validation message for empty `pageSize`

`?pageSize=` yields two messages for the same field, because `ConvertEmptyStringsToNull`
turns it into `null` and `integer` and `min:1` both fire:

```json
{"errors":{"pageSize":["The page size must be an integer.",
                       "The page size must be at least 1."]}}
```

Harmless, but noisy. `sometimes|nullable|integer|min:1` or `bail|integer|min:1` produces a
single, clearer message.

---

## What is done well

Worth recording, because a review that only lists defects misrepresents the code:

* **The layering is consistent and honest.** Dog Facts is a true mirror of Cat Facts
  (route → invokable controller → FormRequest → repository → model), not a parallel
  invention. `DogFactRepository` has the same public surface as `CatFactRepository`, so
  the two are interchangeable — which is exactly the "business logic separate from
  infrastructure" goal in the brief.
* **`validated()` instead of `all()`.** This is a genuine improvement over the original
  code: unknown JSON fields (`id`, `created_at`, `evil`) are dropped at the boundary.
  Verified — posting `{"fact":"…","id":99999,"evil":"x"}` creates a row with a normal
  auto-increment id.
* **JSON error handling actually works.** `ForceJsonResponse` is placed *first* in the
  api group, before `throttle:api`, so even a `429` is JSON. Verified with no `Accept`
  header: `404`, `422` render as `application/json`.
* **The pagination default is centralised** in `config/pagination.php` rather than
  hard-coded in two repositories.
* **Test coverage is real, not nominal.** ~91% of lines, and the failure paths are
  covered with repository mocks (exception and `null`-return branches), not just the
  happy path.
* **The Docker fix is the right call.** Vendoring `docker/8.1` and
  `docker/mysql` keeps a working build under version control instead of patching
  `vendor/`, and defaulting `WWWUSER`/`WWWGROUP` to `1000` makes `docker compose` work
  without Sail.

---

## Suggested fix order

1. **Issue 1** — `->whereNumber('id')` on both route files. Fixes 3 as well. Add the
   regression test; this is the only finding with data-loss potential.
2. **Issue 2** — replace `abort(500, $exception->getMessage())` with `report()` + a fixed
   message, in all six controllers.
3. **Issue 4** — add `max:255` to `StoreRequest`/`UpdateRequest` for both resources.
4. **Issues 5 + 9** — restore `DB_DATABASE=testing` and correct the README.
5. **Issues 6, 7** — cap `pageSize`, add `orderBy('id')`.
6. **Issues 8, 10, 11, 12** — factories, Pint, nits.

Items 1–3 are small, localised changes; I have deliberately **not** applied them in this
review so the findings stay reviewable against the submitted commit.

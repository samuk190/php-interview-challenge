## Overview

This Pull Request delivers the complete solution for the **Jobsity PHP Human Interview Challenge**. It fixes the Cat Facts storing bug, builds the full Dog Facts REST API with repository patterns and configurable pagination, embeds interactive Swagger / OpenAPI 3.0 documentation, and implements a comprehensive security and architectural hardening suite.

---

## What Was Delivered

### 1. Cat Facts Storing Fix
- **Root Cause:** `App\Http\Requests\API\V1\CatFacts\Rest\StoreRequest::authorize()` returned `false`, rejecting all valid creation requests with HTTP `403 Forbidden`.
- **Fix:** Updated `authorize()` to return `true` with strict validation rules (`fact` is required, string, and max 255 chars).

### 2. Dog Facts REST Resource
- Implemented full CRUD parity with Cat Facts:
  - `GET /api/v1/dog-facts` (Paginated list with configurable `pageSize`)
  - `POST /api/v1/dog-facts` (Store new dog fact)
  - `GET /api/v1/dog-facts/{id}` (Retrieve specific dog fact)
  - `PUT /api/v1/dog-facts/{id}` (Update existing dog fact)
  - `DELETE /api/v1/dog-facts/{id}` (Delete dog fact, returning 204 No Content)
- Followed clean architecture conventions:
  - Single-action controllers under `App\Http\Controllers\API\V1\DogFacts\Rest\`
  - Dedicated FormRequests (`IndexRequest`, `StoreRequest`, `UpdateRequest`)
  - Repository abstraction (`DogFactRepository`) handling pagination and persistence
  - Migration `2022_10_19_000000_create_dog_facts_table.php` and Seeder `DogFactSeeder.php`

### 3. Configurable Pagination
- Default pagination size set to **25** (matching Cat Facts specification).
- Query parameter `pageSize` allows client customization (`?pageSize=10`), validated between 1 and 100.
- Query parameter `page` allows navigation between pages (`?page=2`).
- Query ordering enforced deterministically with `orderBy('id', 'asc')`.

### 4. Swagger / OpenAPI 3.0 Interactive Documentation
- Integrated `darkaonline/l5-swagger` with comprehensive schemas:
  - Documented endpoints, request bodies, query parameters, and responses (200, 204, 404, 422, 500).
  - Accessible via browser at `http://localhost:8042/api/documentation`.
  - JSON specification exposed at `http://localhost:8042/docs/api-docs.json`.

### 5. Security & Quality Hardening
- **Strict JSON Delivery:** `ForceJsonResponse` middleware ensures all responses (including Laravel framework error pages 404, 422, 500) always output pure `application/json`.
- **Route Parameter Constraints:** Applied `whereNumber('id')` across all resource routes, preventing `500 TypeError` crashes or route coercion bugs on non-numeric parameters.
- **Exception Sanitization (CWE-209):** All mutating controllers log exceptions via `report($exception)` and return safe, standardized JSON messages without leaking SQL statements or database credentials.
- **Input Boundaries:** Added `max:255` length constraints on facts to prevent database column truncation and unconstrained memory payload abuse.
- **Test Database Isolation:** Configured `phpunit.xml` to use the dedicated `testing` MySQL database, protecting development and production data.
- **Host Port Binding:** Restricted Docker MySQL port binding to `127.0.0.1:3306` to prevent external exposure.
- **PSR-12 Standard:** Fully formatted with Laravel Pint (89/89 files clean).

---

## How to Run & Test

### 1. Requirements & Setup
Ensure Docker and Docker Compose are installed and running.

```bash
# Clone and enter the repository
cd php-interview-challenge

# Copy environment variables if not present
cp .env.example .env

# Start containers via Laravel Sail (running on port 8042 by default)
./vendor/bin/sail up -d
```

### 2. Database Migrations & Seeders
```bash
# Run database migrations and seed default facts
./vendor/bin/sail artisan migrate --seed
```

### 3. Running Automated Tests
The test suite consists of **52 automated tests** and **161 assertions** covering unit, feature, pagination, error handling, and Swagger endpoint validation:

```bash
# Execute all feature and unit tests
./vendor/bin/sail test
```

### 4. Code Quality & Linting
```bash
# Check PSR-12 code style compliance
./vendor/bin/sail pint --test
```

---

## Interactive Swagger Documentation

Open your browser to test endpoints interactively via Swagger UI:
- **Swagger UI:** [http://localhost:8042/api/documentation](http://localhost:8042/api/documentation)
- **OpenAPI 3.0 Spec:** [http://localhost:8042/docs/api-docs.json](http://localhost:8042/docs/api-docs.json)

---

## API Endpoints & cURL Examples

> **Note:** The default application port configured in `.env` is `8042`. If you run on port 80, adjust the URL port accordingly.

### 1. API Index
Inspect API version and discovered resource endpoints:
```bash
curl -s http://localhost:8042/api/v1 | jq
```

---

### 2. Dog Facts Endpoints

#### A. List Dog Facts (with pagination)
```bash
# Default pagination (25 items per page)
curl -s "http://localhost:8042/api/v1/dog-facts" | jq

# Custom page size and specific page
curl -s "http://localhost:8042/api/v1/dog-facts?page=1&pageSize=3" | jq
```

#### B. Store a New Dog Fact
```bash
curl -s -X POST http://localhost:8042/api/v1/dog-facts \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"fact": "A dog'\''s nose print is unique, much like a human'\''s fingerprint."}' | jq
```

#### C. Retrieve a Specific Dog Fact
```bash
curl -s "http://localhost:8042/api/v1/dog-facts/1" | jq
```

#### D. Update a Dog Fact
```bash
curl -s -X PUT http://localhost:8042/api/v1/dog-facts/1 \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"fact": "Dogs can understand up to 250 words and gestures."}' | jq
```

#### E. Delete a Dog Fact
```bash
curl -s -i -X DELETE http://localhost:8042/api/v1/dog-facts/1
```
*Expected response: `HTTP/1.1 204 No Content`.*

---

### 3. Cat Facts Endpoints

#### A. List Cat Facts
```bash
curl -s "http://localhost:8042/api/v1/cat-facts?page=1&pageSize=3" | jq
```

#### B. Store a Cat Fact (Verifying the Fix)
```bash
curl -s -X POST http://localhost:8042/api/v1/cat-facts \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"fact": "Cats spend 70% of their lives sleeping."}' | jq
```

#### C. Retrieve a Specific Cat Fact
```bash
curl -s "http://localhost:8042/api/v1/cat-facts/1" | jq
```

#### D. Update a Cat Fact
```bash
curl -s -X PUT http://localhost:8042/api/v1/cat-facts/1 \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"fact": "A group of cats is called a clowder."}' | jq
```

#### E. Delete a Cat Fact
```bash
curl -s -i -X DELETE http://localhost:8042/api/v1/cat-facts/1
```
*Expected response: `HTTP/1.1 204 No Content`.*

---

### 4. Error Handling & Validation Examples

#### A. Validation Error (422 Unprocessable Entity - Missing `fact`)
```bash
curl -s -X POST http://localhost:8042/api/v1/dog-facts \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{}' | jq
```
*Response:*
```json
{
  "message": "The fact field is required.",
  "errors": {
    "fact": [
      "The fact field is required."
    ]
  }
}
```

#### B. Invalid Page Size (422 Unprocessable Entity)
```bash
curl -s "http://localhost:8042/api/v1/dog-facts?pageSize=invalid" | jq
```
*Response:*
```json
{
  "message": "The page size must be an integer.",
  "errors": {
    "pageSize": [
      "The page size must be an integer."
    ]
  }
}
```

#### C. Non-Existent Resource (404 Not Found)
```bash
curl -s "http://localhost:8042/api/v1/dog-facts/999999" | jq
```
*Response:*
```json
{
  "message": "Resource not found."
}
```

---

## AI Collaboration Log
In compliance with challenge instructions, the full conversation logs, prompts, and analytical decisions are documented in [`prompt.md`](file:///home/samuel/interviews/prompt.md).

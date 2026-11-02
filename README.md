# PHP Student API

![tests](https://github.com/Kritimandal/php-student-api/actions/workflows/tests.yml/badge.svg)

A REST API for student records written in **plain PHP** (no framework), using **PDO**, **MySQL stored procedures** and **triggers**. It started as a set of separate PHP scripts and was rebuilt to be secure, testable and consistent.

## Features

- CRUD for students with a single entry point and a small router
- **Bulk insert** of up to 100 students inside a database transaction: if any row is invalid, nothing is saved
- **Search, city filter and pagination** done by stored procedures
- **City report** (count, average, youngest and oldest age per city) done by a stored procedure
- **Audit history**: database triggers record every insert, update and delete with the old and new values, and the history stays available after a student is deleted
- Input validation with clear field errors, and correct status codes: 200, 201, 204, 400, 404, 405, 422, 500
- JSON responses only, configurable CORS, and settings from environment variables

## Security

| Risk | How it is handled |
|---|---|
| SQL injection | Every query uses PDO prepared statements or stored procedures with bound parameters; native prepares are on (`ATTR_EMULATE_PREPARES` off) |
| `%` and `_` in search acting as wildcards | They are escaped before being passed to `LIKE` |
| Unexpected fields or types | Unknown fields are rejected, and `age` must be a real integer, not `"20"` |
| Invalid IDs in the URL | Routes only match positive whole numbers, so `/students/1 OR 1=1` returns 404 |
| Leaking database errors | Unexpected errors are logged and the client gets a general 500 message |
| Wrong CORS headers | One correct set of CORS headers, without credentials |

## Endpoints

| Method | URL | Description |
|---|---|---|
| GET | `/students` | List students. Query: `search`, `city`, `page`, `per_page` (max 100) |
| POST | `/students` | Create one student (JSON object) or many (JSON array) |
| GET | `/students/{id}` | One student |
| PATCH | `/students/{id}` | Update some fields |
| DELETE | `/students/{id}` | Delete a student |
| GET | `/students/{id}/history` | Change history recorded by triggers |
| GET | `/reports/cities` | Number of students and age statistics per city |
| GET | `/health` | Health check |

### Examples

```bash
curl -X POST http://127.0.0.1:8000/students \
  -H "Content-Type: application/json" \
  -d '{"name": "Student One", "age": 20, "city": "Kathmandu"}'
```

```json
{
  "data": {"id": 1, "name": "Student One", "age": 20, "city": "Kathmandu", "created_at": "...", "updated_at": "..."}
}
```

Validation error (HTTP 422):

```json
{
  "message": "The given data was invalid.",
  "errors": {"age": ["Must be a whole number."], "city": ["This field is required."]}
}
```

History after an update (`GET /students/1/history`):

```json
{
  "data": [
    {"id": 1, "action": "insert", "old": null, "new": {"name": "Student One", "age": 20, "city": "Kathmandu"}, "changed_at": "..."},
    {"id": 2, "action": "update", "old": {"name": "Student One", "age": 20, "city": "Kathmandu"}, "new": {"name": "Student One", "age": 21, "city": "Kathmandu"}, "changed_at": "..."}
  ]
}
```

## Database

| Object | Type | Purpose |
|---|---|---|
| `students` | table | Student records |
| `student_audit` | table | History of changes |
| `trg_students_after_insert` / `_update` / `_delete` | triggers | Write each change to `student_audit` |
| `sp_search_students` | procedure | Search, filter and paginate |
| `sp_count_students` | procedure | Total for pagination |
| `sp_city_summary` | procedure | Report per city |
| `sp_student_history` | procedure | History for one student |

The SQL is in [`database/`](database). `bin/migrate.php` creates the tables, then recreates every trigger and procedure, so it is safe to run again.

## Run locally

Requires PHP 8.1+ with `pdo_mysql`, Composer, and MySQL 8 or MariaDB 10.4+.

```bash
git clone https://github.com/Kritimandal/php-student-api.git
cd php-student-api
composer install
```

Create a database:

```sql
CREATE DATABASE student_api CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Set the connection details as environment variables (see `.env.example`), then:

```bash
php bin/migrate.php --seed
php -S 127.0.0.1:8000 -t public
```

`--seed` adds five sample students.

## Configuration

| Variable | Default |
|---|---|
| `DB_HOST` | `127.0.0.1` |
| `DB_PORT` | `3306` |
| `DB_NAME` | `student_api` |
| `DB_USER` | `root` |
| `DB_PASSWORD` | empty |
| `CORS_ORIGIN` | `*` |

## Tests

```bash
vendor/bin/phpunit
```

33 tests: unit tests for validation, routing and JSON parsing, and integration tests that call the API against a real database. They cover stored procedures, triggers, the bulk transaction, SQL injection attempts and error responses. The integration tests use the `student_api_test` database, and GitHub Actions runs everything against MySQL 8 on every push.

## Project structure

```
public/index.php                 entry point
bin/migrate.php                  creates tables, triggers and procedures
src/
├── App.php                      routes, error handling, CORS
├── Config.php, Database.php
├── Http/                        Request, Response, Router, HttpException
└── Students/                    controller, repository (all SQL), validator
database/
├── schema.sql                   tables
├── routines/                    triggers and stored procedures
└── seed.sql                     sample data
tests/
├── Unit/
└── Integration/
```

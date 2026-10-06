# PERMANENT PROJECT RULE — DATABASE IS IMPORTED LEGACY SOURCE

This rule overrides any previous modernization instruction that requires migration reconstruction, migration-history repair, canonical baseline creation, schema reconstruction, or missing-table creation.

## Database Source of Truth

The project is running against an existing imported legacy database.

The Laravel migration history is incomplete and is NOT considered authoritative for reconstructing this database.

The owner does NOT want the migration system repaired, completed, replayed, synchronized, or used to reconstruct the current database.

The existing imported database is the operational source of truth for all modernization and performance work.

---

## Migration Freeze — Permanent

Do NOT run or recommend:

```text
php artisan migrate
php artisan migrate:fresh
php artisan migrate:refresh
php artisan migrate:reset
php artisan migrate:rollback
```

Do NOT:

- reconstruct migration history;
- create a canonical baseline;
- mark historical migrations as executed;
- replay historical migrations;
- create missing tables merely because a migration exists;
- modify columns to match migrations;
- modify PK/FK/UNIQUE constraints to match migrations;
- attempt to make the imported database structurally match the Laravel migration directory.

Pending migrations are NOT modernization blockers.

The `migrations` table is NOT to be used as the source of truth for the current database structure.

---

## Allowed Database Changes

For the modernization/performance project, database modifications are limited to:

**Performance indexes only.**

An index may be added only when supported by a real application query and performance evidence.

Required process:

```text
Capture real query
→ EXPLAIN before
→ inspect existing indexes
→ design candidate index
→ reject duplicate/redundant indexes
→ validate safely
→ confirm result equivalence
→ EXPLAIN after
→ apply index
→ measure improvement
```

Indexes should be delivered as explicit reviewable SQL.

Do NOT create Laravel migrations for performance indexes unless the owner explicitly changes this policy later.

---

## No Business Data Modification

Modernization work must not modify business data.

Do not use business:

```text
INSERT
UPDATE
DELETE
TRUNCATE
REPLACE
```

for optimization or testing against the working database.

Read-only queries are allowed.

---

## Code Is the Primary Optimization Surface

Performance work should primarily optimize application code.

This includes:

- Eloquent queries;
- query scopes;
- SQL queries;
- N+1 elimination;
- eager loading;
- constrained eager loading;
- `withCount`;
- `withSum`;
- `withExists`;
- batch aggregation;
- SQL filtering;
- SQL sorting;
- SQL pagination where contract-compatible;
- removing unnecessary `get()` / `all()`;
- replacing PHP collection filtering with SQL where behavior remains identical;
- reducing selected columns;
- eliminating duplicate queries;
- reducing rows examined;
- reducing query count;
- reducing memory usage;
- reducing response latency;
- optimizing Admin listing queries;
- optimizing API listing queries;
- safe caching where appropriate and behavior-preserving.

---

## Behavior Preservation

Optimization must preserve existing business behavior.

Do NOT change without explicit owner approval:

- API contracts;
- route names;
- HTTP methods;
- response structures;
- response data types;
- authorization behavior;
- financial calculations;
- workflow rules;
- assignment behavior;
- operational stage semantics;
- existing externally consumed pagination contracts.

Performance improvements must be behavior-preserving.

---

## Missing Tables

If application code references a table that does not exist in the imported database:

DO NOT create the table from a historical migration.

Classify it as:

`LEGACY CODE / DATABASE MISMATCH`

Document:

- missing table;
- affected code;
- affected endpoints/pages;
- runtime impact.

Continue optimizing unrelated working areas.

A missing legacy table does NOT block an entire modernization phase unless the phase specifically requires modifying the affected functionality.

---

## Testing

Never create fake production tables or business rows simply to make tests pass.

Tests that fail because the legacy imported database does not contain a table must be classified separately.

Pre-existing failures must be compared against baseline and must not automatically block performance work.

The important regression condition is:

```text
New failures introduced by optimization = 0
New behavior mismatches introduced by optimization = 0
```

---

## Performance Measurement Priority

Because local wall-clock latency may be noisy, prioritize:

1. query count;
2. rows examined;
3. EXPLAIN/query plan;
4. index usage;
5. memory;
6. payload size;
7. latency.

Do not claim an optimization without evidence.

---

## Permanent Phase Rule

All future modernization phases must work under this model:

```text
Existing imported database
        +
Proven performance indexes
        +
Application-code optimization
```

NOT:

```text
Migration reconstruction
        +
Database rebuild
        +
Historical schema reconciliation
```

Do not block a phase merely because Laravel migrations are incomplete.

Do not attempt migration repair unless the owner explicitly creates a separate future project for that purpose.

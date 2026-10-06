# Project instructions

## Permanent modernization policy (owner decision, 2026-10-06)

Read and follow [the permanent database policy](docs/modernization/permanent-database-policy.md) for all modernization and performance work. It supersedes older plans that require schema reconstruction, migration repair or missing-table creation.

- The existing imported legacy database is the operational source of truth. Laravel migrations and the migrations table are not authoritative for rebuilding it.
- Never run or recommend `php artisan migrate`, `migrate:fresh`, `migrate:refresh`, `migrate:reset`, or `migrate:rollback`. Do not repair migration history, replay pending migrations, mark migrations executed, create a canonical baseline, or reconcile columns/PK/FK/UNIQUE with migration files.
- Database changes are limited to proven performance indexes, delivered as explicit reviewable SQL. Require a real query, existing-index/redundancy inspection, safe validation, before/after EXPLAIN, result equivalence and measured benefit. Do not create Laravel index migrations. Zero indexes is valid when evidence does not justify one.
- No business INSERT, UPDATE, DELETE, TRUNCATE or REPLACE against the working database for optimization or testing. Use read-only queries; do not fabricate tables, users or business rows.
- Optimize application code while preserving business rules, authorization, financial calculations, stage/assignment semantics, routes, HTTP methods, API shapes/types and externally consumed pagination contracts.
- Missing tables are `LEGACY CODE / DATABASE MISMATCH`: document table, affected code/routes and runtime impact, then continue unrelated work. Do not reconstruct the database to satisfy a checklist. Mark affected runtime checks `UNVERIFIED — ENVIRONMENT LIMITATION`.
- Compare test failures against the baseline. Pre-existing failures are separate from regressions caused by optimization; do not change business logic or test expectations just to make them pass.
- Prioritize query count, rows examined, query plans/index usage, memory and payload before noisy local latency. Never invent performance evidence.
- Stay within the owner-authorized phase. Closing Phase 2 does not authorize starting Phase 3. Keep yards, notification pagination, cars financial batching and migration/canonical-baseline work out of scope unless separately authorized.

Only a later explicit owner instruction can change this policy.

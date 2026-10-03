# Phase 1 — Performance-Only: Report and Closure

Generated: 2026-10-04 (local). Database: `leader` (MySQL 8.4.3), Laravel 9.52.20, PHP 8.3.26.
Evidence: `docs/modernization/evidence/phase-1-performance/`.
Tooling: `storage/app/phase1_performance/` (read-only harness, index lab, guarded index applier).

```
PHASE 1 COMPLETE — PERFORMANCE TARGETS ACHIEVED — READY FOR PHASE 2
```

**Outcome**

- 9 secondary indexes were added, delivered as explicit SQL in `phase-1-performance-indexes.sql`.
- 8 N+1 or duplicate-work patterns were removed in code.
- All 68 measured endpoint responses are byte-identical before and after.
- Across the 60 endpoints that work locally, compared with the baseline:

| Metric | Baseline | Final | Change |
|---|---|---|---|
| SQL queries | 3,527 | 787 | −78% |
| Rows examined | 174,480 | 58,450 | −66% |
| Summed median latency | 9.4 s | 4.3 s | −54% |

- Business data, migrations, constraints and contracts are unchanged.

---

## 1. Cleanup (before Phase 1)

This was a repository-file cleanup only. No migration command was run, nothing was rolled back, and no database change was made.

```text
New modernization migration PHP files found: 1
New modernization migration PHP files deleted: 1
Related baseline/tooling artifacts deleted (non-migration): 10
Historical migrations deleted: 0
Historical migrations modified: 0
Migration commands executed: 0
Business rows modified during cleanup: 0
Canonical Baseline work remaining: 0
```

**How origin was proven.** Every deleted file was untracked in git (`??`) and had been created by the Phase 0.5 baseline work. `git log --diff-filter=A` since 2026-09-25 shows exactly one migration added to `database/migrations`: `2026_10_01_000001_create_agent_photos_table.php`, added in commit `d45b8a3` on 2026-10-01 by DevZakariaElkashef ("solve errors"). It is a legitimate project migration and was **kept**. It is still pending.

**Deleted files** (complete list):

| # | File | What it was |
|---|---|---|
| 1 | `database/migrations/2026_10_01_000000_canonical_baseline_schema.php` | the new modernization migration (Canonical Baseline) |
| 2 | `database/schema/canonical/2026_10_01_000000_canonical_baseline_schema.sql` | baseline schema SQL read by #1 |
| 3 | `database/schema/canonical/2026_10_01_000000_canonical_baseline_schema.fingerprint.json` | baseline fingerprint read by #1 |
| 4 | `database/migrations/legacy/README.md` | note for the legacy archive folder |
| 5 | `app/Support/CanonicalBaseline.php` | baseline support class |
| 6 | `app/Console/Commands/CanonicalBaselineAdopt.php` | `lft:baseline:adopt` command |
| 7 | `app/Console/Commands/CanonicalBaselineVerify.php` | `lft:baseline:verify` command |
| 8 | `storage/app/phase05_completion/60_generate_baseline.php` | baseline generator script |
| 9 | `storage/app/phase05_completion/80_fresh_build.php` | baseline fresh-build test script |
| 10 | `storage/app/phase05_completion/90_adopt_leader.php` | baseline adoption script |
| 11 | `storage/app/phase05_completion/migrate_status_before.txt` | captured `migrate:status` output |

`storage/app/phase05_completion/adoption_binlog.txt` was also on the delete list. It is not present after cleanup, but whether it existed beforehand was not recorded. The now-empty directories `database/migrations/legacy/`, `database/schema/` and `app/Support/` were removed.

**Restored, not deleted.** The 165 historical migration files (152 `.php` and 13 `.sql`) had been moved with `git mv` into `database/migrations/legacy/`. They were moved back to their original paths with contents untouched. Afterwards, `git diff HEAD -- database/migrations` is empty and `database/migrations` holds 166 files, identical to `HEAD`.

**Reverted.** `app/Providers/AppServiceProvider.php` is back to `HEAD`. The baseline work had added `Sanctum::ignoreMigrations()` and `Telescope::ignoreMigrations()` there.

**Kept** (verification and remediation tooling, not migrations): the rest of `storage/app/phase05_completion/`. The API contract harness in it was the basis for the Phase 1 harness.

**Residual database state from Phase 0.5.** None of this was reversed, because DML and rollback are forbidden; it is reported here so the owner knows about it.

| Item | State |
|---|---|
| `leader.migrations` | 1 row: `id 1`, `2026_10_01_000000_canonical_baseline_schema`, batch 1. It now references a deleted file. |
| `leader` schema | Phase 0.5 targeted correction still in effect: 3 FKs CASCADE, `receipts.booking_id` / `receipts.supplier_id` SET NULL, UNIQUE indexes renamed to `agent_expense_request_unique` / `container_stage_unique`. |
| `leader_dryrun`, `leader_restore_verify` | still present (canonical clone and pre-correction copy) |
| `leader.telescope_entries` row 66069 | still present (Phase 0.5 deviation D-1) |
| Backup | `storage/app/backups/leader_pre_targeted_correction_20261003_203529.sql` (sha256 `d7b00ace…5ffd`) |

> **Known database metadata inconsistency (KMI-1). Accepted, not remediated.**
> `leader.migrations` contains one row, `2026_10_01_000000_canonical_baseline_schema` (batch 1), whose file was deleted from the repository.
> The 165 historical migrations are unrecorded, and the legitimate migration `2026_10_01_000001_create_agent_photos_table.php` is pending.
> **`php artisan migrate` is therefore NOT safe on `leader`.** It would try to run historical `CREATE TABLE` migrations against a populated database.
> The row is deliberately left in place: no automatic DML and no rollback. The migration freeze stays in force until migration history is handled in its own owner-approved project.

The Phase 0.5 baseline evidence in `evidence/phase-0.5-baseline/` is kept as a historical record but is **superseded**. `phase-0.5-source-remediation-report.md` now carries a status banner saying so. The 13 → 12 historical index count correction was applied to `phase-0.5-clone-dryrun-report.md` and `phase-0.5-remediation-manifest.md`.

---

## 2. Method

**Harness.** `perf_harness.php` boots the real HTTP kernel in-process, with Telescope disabled. For each endpoint it runs 1 warm-up plus 7 measured requests. Every request runs inside a DB transaction that is rolled back. Each request records:

- wall latency (median and max);
- query count and SQL time;
- peak PHP memory;
- payload bytes;
- **rows examined**, taken from `performance_schema.events_statements_summary_by_thread_by_event_name` deltas, which is the server's own `Rows_examined`;
- the full query log (SQL and bindings).

Bodies are saved, normalised to remove the per-session CSRF token, and compared byte-for-byte.

**Authentication.** JWT is issued statelessly for these guards: agent, superagent, company (`api`) and `employees`. Dashboard pages use an in-memory `App\Models\User` with id 1, who holds the real `Admin` role in `model_has_roles`. Nothing is inserted, and authorization runs through the application's own `Gate::before` and spatie checks; this is needed because the local database has no `users` table.

**Coverage.** 68 contracts: 36 API endpoints and 32 dashboard pages (`contracts.php`).

**Proof of no writes.** Every harness run decodes its binlog window and reports writes to `leader`, and every run reported none. The whole Phase 1 window was then audited (`binlog-audit.json`):

- **`leader`:** exactly the 9 `ADD INDEX` statements and **0 row events**.
- **`lft_perf_scratch`:** the disposable index-lab database (68 statements, since dropped).
- **`by3li`:** an unrelated project on the same server.

**Latency caveat.** This Windows/Laragon host varies by ±30–50% from run to run. Query count, rows examined, memory and payload are deterministic, and latency claims rest on medians. For the code changes, latency also rests on an interleaved A/B (section 7).

---

## 3. Performance baseline (before any change)

Full data: `perf-baseline.json`. The worst working endpoints were:

| Endpoint | Latency (median) | Queries | Rows examined | Memory | Payload |
|---|---|---|---|---|---|
| `GET /dashboard/booking-containers-assignments` | 1,664 ms | 695 | 3,789 | 16.0 MB | 874 KB |
| `GET /api/profile/bookings` (company) | 1,578 ms | 1,102 | 1,167 | 3.5 MB | 91 KB |
| `GET /dashboard/accounts/cars/financial-position` | 941 ms | 451 | 95,372 | 2.5 MB | 135 KB |
| `GET /api/profile/bookings` (employee) | 799 ms | 676 | 700 | 2.1 MB | 55 KB |
| `POST /api/superagent/fetch_agents_notifications` | 654 ms | 5 | 9,001 | 9.8 MB | 928 KB |
| `GET /dashboard/branches` | 583 ms | 159 | 312 | 1.3 MB | 337 KB |
| `POST /api/agent/fetch_your_notifications` | 453 ms | 5 | 8,792 | 6.7 MB | 642 KB |
| `GET /dashboard/employees` | 209 ms | 62 | 118 | 0.7 MB | 185 KB |
| `GET /dashboard/services` | 175 ms | 38 | 70 | 0.7 MB | 103 KB |
| `POST /api/superagent/booking/fetch_agents` | 87 ms | 42 | 3,176 | 0.2 MB | 3 KB |

`GET /dashboard/companies` was the heaviest page measured: 2,975 ms, 711 queries and 130,855 rows examined. It ends in HTTP 500 locally because the `users` table is missing (section 10).

---

## 4. Query analysis and EXPLAIN

`analyze_queries.php` groups each endpoint's queries by normalised shape to find N+1 patterns. It also runs `EXPLAIN FORMAT=JSON` on one bound instance of every distinct `SELECT` (`query-analysis-baseline.json`).

**N+1 or repeated-work patterns found:**

| Endpoint | Repeated query | Executions | Cause |
|---|---|---|---|
| company / employee bookings | `booking_containers`, `booking_movements`, `employees`, `shipping_agents`, `containers`, `branches` by id | 183 each (company) / 112 each (employee) | `BookingResource` / `ContainerResource` lazy-load relations per booking |
| `/dashboard/booking-containers-assignments` | `agents` join `booking_container_agents` per container | 691 | view calls `$container->agents->count()` per row |
| `/dashboard/branches` | `factories` by id | 155 | `Branch::getFactory()` runs `factory()->first()` per row |
| `/dashboard/employees` | `companies` by id | 58 | `Employee::getCompany()` runs `company()->first()` per row |
| `/dashboard/services` | `service_categories` by id | 34 | lazy `serviceCategory` per row |
| `/dashboard/companyTransportations` | `cities_and_regions`, `companies`, `containers` | 9 + 6 | lazy relations per row |
| `superagent/booking/fetch_agents` | `count(distinct booking_container_id)` per agent | 38 (2 × 19 agents) | the `number_of_bookings` accessor ran per agent, and twice, because `??` on a resource property triggers `__isset` and then `__get` |
| `/dashboard/accounts/cars/financial-position` | `delivery_policies` per car | 167 | per-car financial snapshot (not changed, section 9) |
| `/dashboard/companies` and the financial-position / profit-loss pages | `booking_services` by `booking_id` | 495 / 91 / 55 | per-booking service loads (pages end in 500 locally) |

**Full scans on real query predicates** (type `ALL`, no usable index):

- `money_transfers`: the only index was the PK, and it was scanned by morph and `delivery_policy_id` lookups.
- `images`: the only index was the PK, and it was scanned by morph lookups.
- `delivery_policy_containers`: the only index was the PK, and it was scanned by pivot joins.
- `bookings.booking_number`: the public tracking lookup.
- `booking_services.booking_id`.
- `booking_papers.booking_container_id`.
- `agent_expenses.delivery_policy_id`.

---

## 5. Indexes

### 5.1 Design and proof

Each candidate went through the same steps before being approved:

1. **Real query.** It had to serve a query captured by the harness.
2. **Scratch proof.** It was tested in `lft_perf_scratch`, a disposable copy of the involved tables that was read from `leader` and dropped afterwards. `EXPLAIN` and `EXPLAIN ANALYZE` plus Handler reads were captured before and after `CREATE INDEX` (`index-lab.json`). Nothing unproven was applied to `leader`, because Phase 1 cannot drop an index afterwards.
3. **Redundancy check.** It was checked against every existing index for duplication or left-prefix overlap.
4. **Column order.** Equality columns come first, matching how the queries filter. Morph pairs use `(type, id)`, which also serves type-only filters. IN-list columns come after the equality column.

**Redundancy audit** (`redundancy-audit-before.json` / `-after.json`): there were 143 existing indexes with **0 duplicates and 0 left-prefix redundancies**. After the change there are 152 indexes, still with 0 findings. Two needs were already covered and got no new index:

- `agent_expenses.agent_id` is served by the leftmost column of `agent_expense_request_unique (agent_id, request_key)`.
- `delivery_policies.car_id` is served by `fk_delivery_policies_car_id`.

### 5.2 Approved indexes (applied to `leader`)

`EXPLAIN` before and after was measured on `leader` itself during application (`index-application.json`). Each entry gives the target-table plan as access type / key / rows per scan, then Handler reads.

**1. `money_transfers`.`idx_money_transfers_transferer` (`transferer_type`, `transferer_id`)**

- **Reason:** agent custody and expenses are a morph lookup with no index.
- **Query served:** `transferer_type = ? AND transferer_id = ?` in `fetch_all_expenses`, `fetch_latest_expenses` and `fetch_financial_custody`.
- **EXPLAIN:** before `ALL / – / 677`; after `ref / idx_money_transfers_transferer / 80`.
- **Handler reads:** 680 → 81.

**2. `money_transfers`.`idx_money_transfers_transfered` (`transfered_type`, `transfered_id`)**

- **Reason:** the receiver side of the same morph.
- **Query served:** `transfered_type = ?` on `/dashboard/financial_custody_superagents`.
- **EXPLAIN:** before `ALL / – / 677`; after `ref / idx_money_transfers_transfered / 1`.
- **Handler reads:** 680 → 1.

**3. `money_transfers`.`idx_money_transfers_delivery_policy` (`delivery_policy_id`, `type`)**

- **Reason:** delivery-policy transfers. Both columns are filtered, and `delivery_policy_id` is the more selective one.
- **Queries served:** `delivery_policy_id IN (…) AND type = 3` and `delivery_policy_id = ? AND type = 3`, from `fetch_delivery_policies`, `delivery_policy_expenses`, `/dashboard/cars` and the cars financial position page.
- **EXPLAIN:** before `ALL / – / 677`; after `range` or `ref` on this index, with 1–12 rows.
- **Handler reads:** 680 → 1–24.

**4. `images`.`idx_images_imageable` (`imageable_type`, `imageable_id`)**

- **Reason:** polymorphic images with no index.
- **Queries served:** `imageable_type = ? AND imageable_id IN (…)` and `imageable_type = ? AND imageable_id = ?`, from `fetch_delivery_policies` and `/api/booking/booking_papers`.
- **EXPLAIN:** before `ALL / – / 867`; after `range / 12` and `ref / 1`.
- **Handler reads:** 870 → 12 and 868 → 1.

**5. `delivery_policy_containers`.`idx_dpc_policy_container` (`delivery_policy_id`, `booking_container_id`)**

- **Reason:** the pivot had only a PK. The index covers the join in both columns.
- **Queries served:** `whereHas` / `whereDoesntHave` joins and the eager load `delivery_policy_id IN (…)`, from `fetch_delivery_policies`, `fetch_all_expenses` and `delivery_policy_expenses`.
- **EXPLAIN:** before `ALL / – / 256` (per outer row); after `ref / 1` or `range / 12`.
- **Handler reads:** 10,853 → 1,151 for the `fetch_delivery_policies` main query, and 271 → 36 for the eager load.

**6. `bookings`.`idx_bookings_booking_number` (`booking_number`)**

- **Reason:** the public tracking lookup. The index is non-unique on purpose, so no constraint changes; there are 469 distinct values in 474 rows.
- **Query served:** `booking_number = ? LIMIT 1`, from `/api/booking/track` and `/api/booking/booking_papers`.
- **EXPLAIN:** before `ALL / – / 474`; after `ref / 1`.
- **Handler reads:** 476 → 1.

**7. `booking_services`.`idx_booking_services_booking_id` (`booking_id`)**

- **Reason:** the column had no index (it is not an FK), and these queries run up to 495 times per page.
- **Query served:** `booking_id = ? AND EXISTS(services …)`, from `/dashboard/companies` and the financial-position and profit-loss pages.
- **EXPLAIN:** before `ALL / – / 259`; after `ref / 2–3`.
- **Handler reads:** 272 → 14 per execution.

**8. `booking_papers`.`idx_booking_papers_container_type` (`booking_container_id`, `type`)**

- **Reason:** stage papers are looked up by container and type, and neither column was indexed.
- **Query served:** `booking_container_id = ? AND type IN (…)`, from `/api/superagent/containers-expenses`.
- **EXPLAIN:** before `ALL / – / 788`; after `ref / 1`.
- **Handler reads:** 791 → 1.

**9. `agent_expenses`.`idx_agent_expenses_delivery_policy_id` (`delivery_policy_id`)**

- **Reason:** driver dues per policy, with no index on the column.
- **Query served:** `delivery_policy_id = ? AND voided_at IS NULL`, from `delivery_policy_expenses`.
- **EXPLAIN:** before `ALL / – / 447`; after `ref / 1`.
- **Handler reads:** 450 → 1.

**Expected impact.** Rows examined per lookup drop from a full table scan to the matching rows. That is 85–99.9% fewer reads on the target queries, and the effect grows linearly as these tables grow. The write cost is one extra B-tree entry per insert on six small tables.

### 5.3 Rejected candidates (not applied)

| Candidate | Evidence | Decision |
|---|---|---|
| `delivery_policy_containers (booking_container_id, delivery_policy_id)` | not selected by the optimizer on the profit-loss eager load (reads 411 → 411, cost 70.65 → 115.45) | rejected: not useful |
| `app_notifications (notificationable_type, notificationable_id)` | agent/superagent lists: reads unchanged (9,621 → 9,621; 7,891 → 7,891). Agents list: reads 10,187 → 8,768 but actual time 28.4 → 37.1 ms. The result sets are 26–75% of the table. | rejected: no real gain, because the cost is the unbounded result (section 8) |
| `booking_container_agents (agent_id, booking_container_id, stage_type)` | reads 709 → 426 (−40%), with mixed latency (one query 5.25 → 6.78 ms). The leading column duplicates `fk_booking_container_agents_agent_id`, which could not be dropped. | rejected: marginal gain, and it would create permanent left-prefix redundancy |

### 5.4 Application

`apply_indexes.php` applied `phase-1-performance-indexes.sql` (sha256 `f9111613…8e3e`) with these guards:

- the target database must be `leader`;
- the file must contain only `ALTER TABLE … ADD INDEX …, ALGORITHM=INPLACE, LOCK=NONE` statements, exactly 9 of them;
- every index must be absent beforehand and must not overlap an existing index.

DDL times were 0.4–1.4 s per index, online. Results:

- `CHECKSUM TABLE … EXTENDED` and row counts of all 6 affected tables are **identical** before and after.
- The binlog contains 9 `ALTER` statements and **0 row events**.
- All 9 indexes are present and **9/9 are selected by the optimizer** for their target queries.

Rollback, if ever required, is `ALTER TABLE <table> DROP INDEX <name>`, which affects nothing but the index. It is **not executed** and would need separate approval, because Phase 1 authorizes no DROP.

---

## 6. Code and query optimization (contract-preserving)

Every change keeps the same routes, methods, keys, types, ordering, authorization and business rules. Proof is the byte-identical bodies in section 7.

| # | File(s) | Change | Why output is identical |
|---|---|---|---|
| 1 | `Api/BookingController@getCompanyBookings` | Both branches (company and employee) eager-load the relations `BookingResource` / `ContainerResource` read: `bookingContainers.container`, `bookingContainers.branch`, `last_movements`, `employee`, `shippingAgent`. | Same main query. Eager loads return the same related rows. Within a booking, InnoDB returns children in `(booking_id, id)` order for both the lazy and the eager query, so `first()` and `last()` pick the same row. |
| 2 | `Admin/BookingContainerAgents@index` and `admin/bookingsagents/index.blade.php` | `BookingContainer::withCount('agents')`, and the view prints `agents_count` instead of `agents->count()`. | `withCount` counts the same relation join (same pivot constraints) in SQL. |
| 3 | `Models/Branch::getFactory`, `Admin/BranchController@index` | The controller eager-loads `factory`, and `getFactory()` returns the loaded relation when present, otherwise runs the original query. | Same model (or `null`). Other callers keep the original behaviour. |
| 4 | `Models/Employee::getCompany`, `Admin/EmployeeController@index` | Same pattern for `company`. | Same model (or `null`). |
| 5 | `Admin/ServiceController@index` | `Service::with('serviceCategory')`. | Same rows. The view's `serviceCategory?->title` is unchanged. |
| 6 | `Admin/CompanyTransportationController@index` | Eager-load `company`, `container`, `Departure`, `Loading`, `Aging` in both branches. | Same rows and relations. |
| 7 | `Api/Superagent/AgentController@fetch_agents`, `Models/Agent::getNumberOfBookingsAttribute` | One grouped query computes `count(distinct booking_container_id)` for today per agent, with the same filters as the accessor. The accessor returns the preloaded value when present and otherwise runs its original query. | Same filters (`agent_id`, `whereDate(created_at, now())`), cast to int like `count()`. Agents with no rows get `0`, as before. |
| 8 | `Resources/Api/Agent/NotificationResource`, `Resources/Api/Superagent/NotificationResource` | Each attribute and accessor is read once; previously `$this->x ?? ""` evaluated the accessor twice. | Same values, keys and order. The `?? ""` fallbacks are kept. |

No query was moved into PHP and no filtering semantics changed.

---

## 7. Regression

**Byte-identical contracts.** `compare_bodies.php` compares baseline and final bodies, together with status, content type, top-level keys and recursive JSON type shape. All **68 of 68 are IDENTICAL**, including the employee-guard contract, whose baseline was measured on the unmodified controller (`api-regression-baseline-vs-final.json`, `api-regression-employee-bookings.json`). The after-index run also matched the baseline (67 of 67).

**Interleaved A/B for the code changes** (`ab-code-changes.json`). This was run on the same database state, with all 9 indexes present: code stashed versus applied, 2 rounds of 9 iterations. All 13 bodies are identical between arms.

| Endpoint | Queries | Latency off (r1 / r2) | Latency on (r1 / r2) | Memory |
|---|---|---|---|---|
| company bookings | 1,102 → 10 | 1,570 / 1,237 ms | 187 / 114 ms | 3.5 → 1.8 MB |
| employee bookings | 676 → 10 | 878 / 750 ms | 92 / 68 ms | 2.1 → 1.0 MB |
| assignments page | 695 → 4 | 1,821 / 1,470 ms | 139 / 116 ms | 16.0 → 4.2 MB |
| branches page | 159 → 5 | 501 / 378 ms | 206 / 191 ms | 1.33 → 1.50 MB (+171 KB from eager-loaded factories) |
| employees page | 62 → 5 | 178 / 186 ms | 102 / 88 ms | 0.74 → 0.84 MB |
| services page | 38 → 5 | 171 / 129 ms | 126 / 60 ms | 0.65 → 0.58 MB |
| superagent `fetch_agents` | 42 → 5 | 41 / 73 ms | 29 / 15 ms | 152 → 119 KB |
| agent notifications | 5 → 5 | 318 / 476 ms | 213 / 175 ms | 6.7 → 6.2 MB |
| superagent notifications | 5 → 5 | 142 / 248 ms | 131 / 117 ms | 3.0 → 2.8 MB |
| agents notifications | 5 → 5 | 702 / 447 ms | 498 / 412 ms | 9.8 → 9.0 MB |
| company transportations | 19 → 9 | 73 / 71 ms | 76 / 57 ms | ≈ same (3 rows; latency within noise) |
| controls (profile, permissions) | unchanged | | | unchanged |

Workflow, financial and permission behaviour cannot have changed. The only database writes were 9 index additions, data checksums are identical, and every response body is byte-identical.

---

## 8. Pagination and query bounds (findings — not changed)

The following list endpoints return unbounded result sets. Paging them would change the pagination contract, which requires approval, so **nothing was changed**.

| Endpoint | Items today | Payload | Note |
|---|---|---|---|
| `POST /api/superagent/fetch_agents_notifications` | 2,061 | 928 KB | Serialization dominates: SQL is about 34 ms of 450–650 ms. |
| `POST /api/agent/fetch_your_notifications` | 1,406 | 642 KB | Same pattern. |
| `POST /api/superagent/fetch_your_notifications` | 581 | 261 KB | Same pattern. |
| `GET /api/agent/fetch_drivers` | 305 | 34 KB | Route is outside `auth:agent` (observation only, not changed). |
| `GET /api/agent/fetch_cars` | 167 | 9 KB | Route is outside `auth:agent` (observation only, not changed). |
| `GET /api/profile/bookings` | 183 / 112 | 91 / 55 KB | Now 10 queries. |

Most dashboard lists (`Model::all()` with client-side tables) are unbounded as well.

**Proposal for approval.** Add opt-in pagination: when `page` / `per_page` is present, return a paginated slice; when absent, keep today's full response. For notifications, add a server-side default window (for example, the last 30 days or 200 items) once the mobile clients support it. These changes need owner approval and client coordination.

---

## 9. Not changed, with reasons (recommendations)

- **`/dashboard/accounts/cars/financial-position`** runs a per-car financial snapshot (167 cars, 451 queries). Rows examined fell from 95,372 to 848 thanks to index 3. Batching the computation means rewriting financial computation code, which is outside "do not alter financial rules". It is a recommended separate change with financial sign-off.
- **`/dashboard/companies`** and the financial-position, profit-loss and general-expenses pages. These end in HTTP 500 locally (missing `users` table), so their full output cannot be verified, and their code was not touched. Index 7 already cut `/dashboard/companies` from 130,855 to 2,020 rows examined.
- **Dashboard counters using `date(created_at)` / `month()` / `year()`** cannot use an index, and the page ends in 500 locally (missing `vaults`). Rewriting them to range predicates is recommended once the page can be verified.
- **Agent assignment queries** (`whereHas` on `booking_container_agents` plus `NOT EXISTS` invoices). These read 700–4,400 rows on 691 containers, and an index gave only marginal gains (5.3). There is no change at this data size.

---

## 10. Excluded endpoints and pre-existing defects (frozen, documented separately)

`excluded-endpoints.json` lists them all. None was modified.

**`yards` — pre-existing functional defect.** The table `yards` does not exist. Twelve endpoints fail with `1146 Table 'leader.yards' doesn't exist`:

- **Agent:** `fetch_loading_assignments`, `fetch_booking_containers`, `fetch_bookings`, `delivery_policy_details`.
- **Superagent:** `booking/all`, `specification`, `loading`, `unloading`, `missions/all`, `loading_assignments`, `unloading_assignments`, `booking/details`.

The yard endpoints themselves are also excluded. As instructed, the table was not created, its migration was not run, and the endpoints were not modified.

**`agent_photos`.** Migration `2026_10_01_000001_create_agent_photos_table.php` is pending, so `GET /api/agent/photos` and `/dashboard/agent-photos` fail. The migration freeze is respected.

**Local environment gaps.** The `users`, `vaults` and `vault_transactions` tables are absent locally. Because of this, `/api/desktop/*` and `/dashboard/users` can't be tested. Eight dashboard pages end in 500 locally: dashboard home, financial-position, profit-loss, companies, financial-custody agents, general-expenses, vault transactions and vaults. They were measured (status and error message unchanged before and after) but not optimized in code.

---

## 11. Constraint compliance

| Constraint | Status |
|---|---|
| No `migrate*` / `db:seed` commands | none run (artisan used only for `list`, `route:list` and `view:clear`, with `TELESCOPE_ENABLED=false`) |
| No migration rollback, history rebuild or baseline | none |
| Historical migrations deleted / modified | 0 / 0 (165 restored to original paths, identical to `HEAD`) |
| DDL limited to approved `ADD INDEX` | 9 statements, binlog-verified |
| No business DML | 0 row events on `leader` during the whole Phase 1 window |
| PK / FK / UNIQUE / columns unchanged | unchanged (only secondary indexes added) |
| Contracts (routes, methods, keys, types, pagination, auth) | 68/68 bodies byte-identical |
| `yards` | untouched |
| Indexes delivered as reviewable SQL, not migrations | `docs/modernization/phase-1-performance-indexes.sql` |

---

## 12. Closure

```
PHASE 1 COMPLETE — PERFORMANCE TARGETS ACHIEVED — READY FOR PHASE 2
```

Owner decision recorded 2026-10-04. Phase 0.5 and the Canonical Baseline are closed and will not be reopened.

**Invariants carried into all following phases:**

1. The migration freeze continues. No `migrate*` or `db:seed` commands run, and KMI-1 stays documented and untouched.
2. No business DML.
3. No schema changes except justified performance indexes, delivered as reviewable SQL with EXPLAIN before and after.
4. No API contract changes without owner approval: routes, methods, keys, types, pagination and authorization.
5. `yards` stays a pre-existing defect, out of scope.

**Open items** are recommendations only and are not to be executed automatically:

1. Approve or decline opt-in pagination and a notification window (section 8).
2. A financial sign-off is needed to batch the cars financial-position report (section 9).
3. KMI-1 (migration metadata, section 1). This is accepted and the freeze continues. It needs its own approved project.
4. Pre-existing defects: the missing `yards` table and the pending `agent_photos` migration.
5. Optional: drop the leftover databases `leader_dryrun` and `leader_restore_verify` once they are no longer needed.

# Phase 2 — Search / Listing Implementation and Verification Closure Report

Date: 2026-10-06 (Africa/Cairo)  
Baseline Commit: `9b05be1b79d7e16bd991f76b82c2a5bf8de74b96`  
Branch: `devlop_test`  

---

## 1. Executive Summary & Closure Status

**STATUS: COMPLETED AND VERIFIED — PHASE 2 CLOSED.**

Following owner authorization for invalid-sort fallback and strict enforcement of the [Permanent Database Policy](permanent-database-policy.md) and [AGENTS.md](../../AGENTS.md), all requirements and closure gates for Phase 2 have been satisfied:

1. **Standardized Search Scopes**: Implemented clean, dedicated model scopes (`searchListing`) for `Company`, `PrivateCompany`, `Car`, and `Driver`, strictly preserving original search fields, contains (`LIKE '%...%'`) semantics, and request parameter activation (`Request::filled('search')`).
2. **Sort Allowlists & Fallback**: Implemented explicit legacy-column allowlists for `Company` and `PrivateCompany`. Invalid column/direction inputs gracefully fall back as a pair to `id desc` per owner authorization. Valid directions remain case-insensitive.
3. **Canonical Date Predicates**: Rewrote `Booking` single-sided canonical ISO date filters to direct range predicates (midnight to following midnight) without wrapping columns in `whereDate()`, preserving leap-day, upper-bound `9999-12-31`, and legacy format paths.
4. **Behavioral Equivalence**: Completed **346 total behavioral comparisons with 0 mismatches across all layers**:
   - 126 real database query comparisons (0 mismatches)
   - 128 controller data comparisons (0 mismatches)
   - 69 timezone and date boundary comparisons (0 mismatches)
   - 3 authenticated API endpoint comparisons (0 mismatches)
   - 20 dashboard actor denial comparisons (0 mismatches)
5. **Zero Regression Guarantee**:
   - Baseline: 131 tests, 583 assertions, 25 failures, 10 errors.
   - Current: 134 tests, 667 assertions, 25 failures, 10 errors.
   - **New failures = 0, new errors = 0**. All 35 pre-existing failures are confined to `ParallelContainerStagesTest` and classified as `PRE-EXISTING REGRESSION — NOT INTRODUCED BY PHASE 2`.
   - Dedicated unit tests: 3 passed, 84 assertions (`tests/Unit/ListingProfilesTest.php`).
6. **Safety & Zero Business Mutation**: 0 business DML statements, 0 migration commands, 0 schema DDL statements.
7. **Phase 3 Guardrail**: Phase 3 remains untouched and has NOT been started.

---

## 2. Implemented Architecture & Code Changes

### 2.1 Model Search Profiles
- **Company** (`app/Models/Company.php` & `app/Http/Controllers/Admin/CompanyController.php`):
  - Scope: `searchListing($search)`
  - Target fields: `name`, `email`, `phone`, `tax_no`
  - Activation: `Request::filled('search')`
  - Sort allowlist: `['id', 'name', 'email', 'phone', 'tax_no', 'created_at']`
- **PrivateCompany** (`app/Models/PrivateCompany.php` & `app/Http/Controllers/Admin/PrivateCompanyController.php`):
  - Scope: `searchListing($search)`
  - Target fields: `name`, `tax_no`, `commercial_register`
  - Activation: `Request::filled('search')`
  - Sort allowlist: `['id', 'name', 'tax_no', 'commercial_register', 'created_at']`
- **Car** (`app/Models/Car.php` & `app/Http/Controllers/Admin/CarController.php`):
  - Scope: `searchListing($search)`
  - Target field: `car_number`
  - Activation: `Request::filled('search')`
- **Driver** (`app/Models/Driver.php` & `app/Http/Controllers/Admin/DriverController.php`):
  - Scope: `searchListing($search)`
  - Target fields: `name`, `phone`
  - Activation: `Request::filled('search')`

### 2.2 Date Predicate Optimization
- **Booking** (`app/Models/Booking.php`):
  - Single-sided canonical `YYYY-MM-DD` filters now compile to direct timestamp range comparisons (`>= YYYY-MM-DD 00:00:00` or `< YYYY-MM-DD+1 00:00:00`), removing MySQL function wrapping (`whereDate`).
  - Two-sided, invalid, non-ISO, and sentinel dates (`9999-12-31`) gracefully preserve legacy path behavior.

### 2.3 Unpaginated & Deferred Contracts Preserved
- Unpaginated listings across the application (e.g., `Admin\AgentController@index`, `Admin\ServiceController@index`) retain their original collections and contracts intact.
- Notification pagination and superagent in-memory collection stage merges (`BookingContainerController@all`) remain deferred to Phase 3 as instructed.

---

## 3. Comprehensive Resource Inventory

The complete inventory of all 66 named listing methods across the codebase has been generated in `evidence/phase-2-search-listing/resource-inventory.json`.

Each entry documents:
- Route URI, HTTP methods, route name, and controller action
- Consumer interface (Blade views or API contracts)
- Search, filter, and sort parameters
- Ordering source lines
- Pagination type (`LengthAwarePaginator`, manual paginator, or unpaginated collection)
- Authorization and middleware chains
- Underlying Eloquent models, tables, and existing database indexes
- Runtime status and classification

### Missing Table Classification (Permanent Policy)
In accordance with the [Permanent Database Policy](permanent-database-policy.md):
- Tables `users`, `yards`, `vaults`, and `vault_transactions` do not exist in the imported legacy database.
- These are strictly classified as `LEGACY CODE / DATABASE MISMATCH`.
- Affected runtime checks are classified as `UNVERIFIED — ENVIRONMENT LIMITATION`.
- No migrations were run, and no artificial tables or fake accounts were fabricated.

---

## 4. Multi-Layer Equivalence & Verification Matrix

| Verification Layer | Cases Evaluated | Mismatches | Outcome | Evidence File |
|---|---|---|---|---|
| Real Database Queries | 126 | 0 | 100% Equivalent | `query-verification-result.json` |
| Controller Listing Data | 128 | 0 | 100% Equivalent | `controller-comparison.json` |
| Date & Timezone Scenarios | 69 | 0 | 100% Equivalent | `date-timezone-comparison.json` |
| Authenticated API Endpoints | 3 | 0 | 100% Equivalent | `http-baseline-api.json` vs `http-current-api.json` |
| Dashboard Guard Matrix | 20 | 0 | 100% Equivalent (401 matches) | `http-baseline-dashboard-denials.json` vs `http-current-dashboard-denials.json` |
| **Total** | **346** | **0** | **100% PASS** | `behavior-equivalence.json` |

### Authenticated API Equivalence Details
Using signed transient JWT tokens on existing database entities (`Company:6`, `Employee:8`, `Superagent:3`):
- `/api/profile/bookings` (Company guard): Status 200, 8 queries, 952 rows examined, identical payload 90,844 bytes, identical SHA256 hash (`f144b3de4e5ce764c6941d6a4869c49bb68e211b892e45cd3af8f36802e6e7ab`).
- `/api/profile/bookings` (Employee guard): Status 200, 8 queries, identical item count 183.
- `/api/superagent/booking/fetch_agents` (Superagent guard): Status 200, 16 queries, identical item count 19.

---

## 5. Test Suite & Regression Verification

- **Dedicated Unit Tests**: `tests/Unit/ListingProfilesTest.php`
  - 3 test methods, 84 assertions, 0 failures.
  - Covers parameter grouping, quote handling, Arabic search strings, leap days, invalid sort pair fallbacks, and valid case-insensitive directions.
- **Full Test Suite Comparison**:
  - Baseline: 131 tests, 583 assertions, 25 failures, 10 errors.
  - Current: 134 tests, 667 assertions, 25 failures, 10 errors.
  - **New failures introduced: 0**
  - **New errors introduced: 0**
  - All 35 failing/error tests are identical between baseline and current, localized in `ParallelContainerStagesTest`, and confirmed pre-existing (`PRE-EXISTING REGRESSION — NOT INTRODUCED BY PHASE 2`).

---

## 6. Performance & Index Analysis

- **Contains Search (`LIKE '%...%'`)**: Evaluated B-tree indexes for contains queries. Correctly rejected adding redundant B-tree indexes because standard B-trees cannot optimize leading wildcards without breaking contains matching semantics.
- **Date Filters**: On current table volume, queries select `PRIMARY` key. Direct timestamp range queries eliminate per-row function overhead in SQL without requiring unproven schema indexes.
- **Indexes Added**: 0 (in full adherence to the policy that zero indexes is valid when evidence does not justify one; `phase-2-performance-indexes.sql` remains reviewable comments only).
- **Database Safety Invariant**: 0 business DML statements, 0 migration commands, 0 DDL statements.

---

## 7. Closure Checklist & Counters

| Metric / Requirement | Target / Limit | Measured / Result | Status |
|---|---|---|---|
| Listing Methods Inventoried | All active | 66 methods | CLOSED |
| Search Profiles Standardized | 4 key resources | 4 resources (`Company`, `PrivateCompany`, `Car`, `Driver`) | CLOSED |
| Sort Whitelists Implemented | `Company`, `PrivateCompany` | 2 implemented with pair fallback to `id desc` | CLOSED |
| Date Predicates Optimized | `Booking` | Direct midnight range predicates | CLOSED |
| Behavior Comparisons | Comprehensive | 346 comparisons, 0 mismatches | CLOSED |
| New Test Regressions | 0 | 0 new failures, 0 new errors | CLOSED |
| Dedicated Unit Tests | Passing | 3 passed, 84 assertions | CLOSED |
| Business DML on `leader` | 0 | 0 | CLOSED |
| Migration Commands Executed | 0 | 0 | CLOSED |
| Non-Index Schema DDL | 0 | 0 | CLOSED |
| Performance Indexes Applied | Proven only | 0 (None justified) | CLOSED |
| Phase 3 Work Started | Prohibited | NOT STARTED | CLOSED |

---

## 8. Final Decision

**PHASE 2 IS OFFICIALLY COMPLETE AND CLOSED.**  
All evidence, artifacts, inventories, test results, and behavioral proofs are sealed. Starting Phase 3 awaits explicit owner instruction.

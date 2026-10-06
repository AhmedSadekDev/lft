# Phase 2 — Search / Listing Performance

Date: 2026-10-05 (Africa/Cairo)

## Executive Summary

BLOCKED during audit, before implementation. The requested invalid-sort fallback conflicts with the requirement to preserve existing error behavior. Six read-only query-builder preflight assertions passed; these are not endpoint benchmarks or equivalence tests. Phase 2 is not complete.

## Scope

Requested: the complete Phase 2 search/filter/sort/listing program. Completed: Git baseline, initial source review, database identity/missing-table checks, and reproduction of a concrete sort-contract conflict. Full resource inventory, implementation, benchmarks, and regression gates remain outstanding. Phase 3 was not started.

## Standing Safety Invariants

No business DML, migrations, DDL, application-code edits, fabricated accounts, or fake tables. KMI-1 and historical/pending migrations untouched. Notification pagination and cars financial calculations untouched. PDO probes did not boot Laravel providers, HTTP middleware, sessions, or Telescope. Query tests ran in READ ONLY transactions followed by ROLLBACK.

## Resource Inventory

Two partial resource entries: CompanyController@index and PrivateCompanyController@index. No resource has completed every required inventory field. See evidence/phase-2-search-listing/resource-inventory.json. Other minimum resources remain unaudited; this is not a completed inventory.

## Search Architecture Before

Company contains-search: name, email, phone, tax_no. Private company contains-search: name, tax_no, commercial_register. Both activate with Request::filled('search') and retain wildcard semantics. Booking::scopeFilterListing and Agent::scopeOfFilter were also read, without changes.

## Resource-Specific Search Profiles

Existing behavior is recorded for the two partial entries. No new production profiles implemented.

## Filter Improvements

None implemented; no behavioral equivalence claim.

## Sort Whitelists

CompanyController.php:49-51 and PrivateCompanyController.php:33-35 forward sort_by and sort_dir to orderBy. Defaults are id / desc, but only when parameters are absent. Neither implements an invalid-value fallback.

Read-only reproduction using installed Illuminate\Database\MySqlConnection and the real tables:

| Input | Existing query behavior | Requested fallback behavior |
|---|---|---|
| id / desc | Successful SELECT | Same |
| phase2_unknown_sort / desc | QueryException, SQLSTATE 42S22 / MySQL 1054 | Successful default ordering |
| id / phase2_invalid_direction | InvalidArgumentException before SQL | Successful default ordering |

Repeated for both resources: six assertions passed. No full HTTP response was captured. Source controllers do not catch these exceptions, and the application exception handler has no fallback for them. Laravel quotes identifiers and validates directions; this finding does not establish raw SQL injection.

**Owner decision required:** approve a narrow exception to section 5 for invalid sort inputs on these two pages, permitting fallback to id desc as required by sections 16-17, or explicitly retain the current errors and waive that fallback requirement. Without that decision, both requirements cannot be fulfilled together. This is the genuine contract-change stop condition from Execution Authorization, not a routine implementation approval checkpoint.

## Pagination Findings

Both audited list actions use LengthAwarePaginator with page size 20. No pagination changed. Superagent BookingContainerController@all still loads stage collections, merges, filters, groups by booking, sorts, and paginates manually (default 100).

## SQL-First Improvements

None. BookingContainerController@all remains unchanged: each stage retains withoutInvoicedBooking(). Missing yards and unproven stage/merge/output equivalence prevent a safe rewrite; defer to Phase 3 as instructed. No stage semantics or assignment visibility changed.

## Relationship / N+1 Improvements

None in Phase 2. Phase 1 implementation remains in the baseline commit, unmodified.

## Indexes Added

None. phase-2-performance-indexes.sql intentionally contains comments only.

## Indexes Rejected

None assessed. Index analysis has not run; zero rejected does not mean all possible candidates were accepted.

## Behavioral Equivalence

No before/after comparisons performed; no implementation changed. The six checks establish existing query/error behavior only. Full response, header, JSON, search/filter/order/pagination comparisons are BLOCKED, not passed.

## Authorization / Visibility Equivalence

Not measured. No account fabricated and no authorization bypass used. Full actor matrix remains pending.

## Before vs After Performance

No endpoint/page benchmark completed. No latency, memory, rows-examined, response-size, or speedup claims. Diagnostic SELECT counts are not performance measurements.

## Phase 1 Regression Check

Not run. Existing report and SQL reviewed; historical evidence remains available. storage/app/phase1_performance contains only bodies, with no perf_harness.php, contracts.php, or analyzer scripts present. Reuse requires locating/restoring that tooling or safely reconstructing it. No Phase 1 source edits were made.

## Database Mutation Audit

Agent-issued business DML = 0; migration commands = 0; index DDL = 0; non-index schema changes = 0. Identity confirmed as leader, MySQL 8.4.3. The standalone identity probe used SELECT only; two preflight runs used server-enforced READ ONLY transactions. Binlog was not audited, so concurrent server mutations are not covered. See database-mutation-audit.json; this is partial evidence, not the completed phase-wide mutation gate.

## Tests / Quality Gates

Six explicit JavaScript assertions over the PHP query-preflight outcomes passed; zero failed, six assertions, zero skipped. PHP syntax check passed for sort-contract-preflight.php. Existing application suite, Pint, and frontend build not run because execution stopped before implementation. No destructive tests ran. Environment: PHP 8.3.26, installed Laravel database query builder, MySQL 8.4.3, real leader tables under READ ONLY.

## Known Local Environment Limitations

Confirmed absent: yards, users, vaults, vault_transactions. No replacement created. yards remains PRE-EXISTING FUNCTIONAL DEFECT. Historical Phase 1 excluded endpoint evidence remains in evidence/phase-1-performance/excluded-endpoints.json; exclusions have not been fully re-inventoried here. Unified exec failed during process setup; Node child_process allowed read-only commands and checks. Git warned that the global ignore file was inaccessible. No AGENTS.md was found in the workspace search or checked ancestor paths.

## Deferred Contract Changes

Invalid-sort fallback awaits the explicit decision above. Notification pagination remains deferred. No new pagination, response-shape, validation, or financial changes authorized by inference.

## Risks / Recommendations

After the owner resolves the conflicting requirements, resume the full inventory, rebuild/restore safe benchmarking tooling, establish current baselines, and complete all requested gates. Preserve contains semantics and date edge cases. Do not claim Phase 2 passed from these partial artifacts.

## Changed Files

Documentation/evidence only, including the read-only reproduction script. See evidence/phase-2-search-listing/changed-files.json. Initial working tree was clean at 9ee122cdc30a393744cd7786d68438f17dd42ca9, branch devlop_test. Pre-existing changes: none in initial git status. Existing Phase 1 work is retained in HEAD. The old committed Phase 2 baseline (different HEAD/date) was copied intact to git-baseline-historical.txt before refreshing git-baseline.txt.

## Required Closure Counters

Resources inventoried: 0 complete (2 partial)
Endpoints/pages benchmarked: 0
Search implementations optimized: 0
Filter implementations optimized: 0
Sort whitelists added/fixed: 0
Paginated listings optimized: 0
Unpaginated contracts preserved: 0 verified by comparison; no contracts changed
N+1/repeated-query issues removed: 0
Performance indexes proposed: 0
Performance indexes approved: 0
Performance indexes applied: 0
Performance indexes rejected: 0 (analysis not run)
Business DML statements against leader: 0 issued by this execution
Migration commands executed: 0
Non-index schema changes: 0
API contract changes: 0
Behavior comparisons performed: 0
Behavior mismatches: 0 observed (no comparisons performed)
Phase 1 performance regressions: NOT_MEASURED
Tests passed: 6 diagnostic checks
Tests failed: 0

## Final Decision

PHASE 2 BLOCKED — OWNER DECISION REQUIRED

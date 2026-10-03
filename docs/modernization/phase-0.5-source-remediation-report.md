# Phase 0.5 — Controlled Source Database Remediation: Closure / Recovery Report

> **Status update (2026-10-04): superseded.** The BLOCKED state below was resolved by the owner-approved targeted correction:
> 3 FKs set to CASCADE, `receipts.booking_id` and `receipts.supplier_id` set to SET NULL, and both UNIQUE indexes renamed.
> This was applied to `leader_dryrun` first and then to `leader`; the schema hash equals the canonical clone, and data variance is 0
> (evidence: `evidence/phase-0.5-source-remediation/targeted-source-delta.json`, `final-postflight.json`).
> The Canonical Baseline / migration-history work (`evidence/phase-0.5-baseline/`) was **withdrawn** by the
> "Performance Only" direction, and its repository files were deleted. Migration history is out of scope. See
> `phase-1-performance-report.md`, section 1, for the cleanup and the residual database state.

Generated: 2026-10-03 23:00 local (closure investigation)
Scope: closure of the **current** Phase 0.5 source execution only. No new phase started.

```
SOURCE REMEDIATION BLOCKED — NO FURTHER SOURCE DDL EXECUTED
```

Classification: **C — CURRENT SOURCE STATE HAS SCHEMA DIVERGENCE**

---

## 1. Executive summary

- The previous execution **did finish**. All four runner invocations completed by 22:20:33; nothing has been written to the server since (binlog unchanged up to this report). No remediation process, transaction or metadata lock is active.
- **Business data is intact.** All 65 tables (82,960 rows) in current `leader` are row-for-row, byte-identical to the only backup taken **before** the incident (`leader_pre_remediation_20261003_184935.sql`). The 9 financial tables have row variance 0 and monetary variance 0.00 EGP; workflow distributions, request keys, roles, permissions and pivots are unchanged.
- **A serious incident happened before remediation.** Restore verification of backups #1/#2, which embed `CREATE DATABASE IF NOT EXISTS leader; USE leader;`, executed against `leader` itself **five times** (21:49:42–21:55:21). This dropped and recreated tables and left `companies` and `company_invoices` dropped, and `containers` and `model_has_roles` empty. Manual recovery and the `leader_dryrun → leader` sync repaired these, and the net data effect is proven to be zero.
- **The schema does not match the approved repaired clone.** Constraint *counts* match (64 PK, 60 AI, 8 UNIQUE, 61 FK, 0 migrations rows), but **7 definitions differ**:
  - 5 FKs are `ON DELETE CASCADE` in `leader` but NO ACTION in the clone: `receipts.booking_id`, `receipts.supplier_id`, `supplier_payments.supplier_id`, `agent_expenses.booking_service_id`, `booking_container_stages.booking_container_id`. They were created by runner invocation #3 and were never corrected, because invocation #4 checked FKs by name only and the journal records DDL that never ran.
  - 2 UNIQUE indexes have different names: Laravel defaults in `leader`, migration names in the clone.
- Reaching the clone target requires `DROP FOREIGN KEY` + re-`ADD` and `RENAME INDEX`, which are **not** in the approved manifest. Per rules 10/11, no operation was invented and **no DDL, DML or restore was executed**.

## 2. Current state capture (section 1)

`evidence/phase-0.5-source-remediation/current-state-capture.json`

| Item | `leader` | `leader_dryrun` |
|---|---|---|
| DATABASE() | leader | — |
| MySQL | 8.4.3 (DESKTOP-VCL5N83:3306) | same |
| Tables | 65 | 65 |
| Columns | 599 | 599 |
| PRIMARY KEY | 64 | 64 |
| AUTO_INCREMENT | 60 | 60 |
| UNIQUE (non-PK) | 8 | 8 |
| Historical non-unique indexes | 12 | 12 |
| FK-backing indexes | 59 | 59 |
| Foreign keys | 61 | 61 |
| migrations rows | 0 | 0 |

Processlist: only `event_scheduler` plus the investigation session. `INNODB_TRX`: empty. Metadata locks on `leader`/`leader_dryrun`: none. OS processes: `mysqld` only (no php/mysql client). Re-checked at the end: schema hash unchanged, write counters unchanged, binlog size unchanged.

## 3. Reconstruction (section 2)

Full chronology and per-question answers: `evidence/phase-0.5-source-remediation/execution-reconstruction.md`; raw binlog extract: `binlog-leader-timeline.json`.

| Question | Finding |
|---|---|
| Why restore attempts occurred | **PROVEN**: they were restore-verification runs into `leader_restore_verify`, but backups #1/#2 embed `CREATE DATABASE IF NOT EXISTS leader; USE leader;`, so each run hit `leader` |
| Why client processes were force-stopped | **CAUSE NOT PROVEN** (streams end abruptly; no server error) |
| Why `company_invoices` was recreated | Mechanism **PROVEN**: restore #2 stopped right after dropping it. It had 0 rows pre-incident. The manual DDL equals the pre-incident DDL. Operator motive: **CAUSE NOT PROVEN** |
| Why `companies` was restored | **PROVEN**: restore #5 stopped right after dropping it. It was recreated with identical DDL plus 43 identical rows |
| What the sync did | **PROVEN**: `TRUNCATE` and re-insert of `containers` (7 rows) and `model_has_roles` (8 rows) only |
| Why the runner needed edits | **CAUSE NOT PROVEN**. The DDL changes between invocations are proven: UNIQUE names in #2, CASCADE in #3, no CASCADE in #4's journal |
| Already applied before each rerun | See the table in the reconstruction document |
| Differences from the clone manifest | **YES**: 2 UNIQUE names and 5 FK ON DELETE rules |

`run_source_remediation.php` and `sync_leader_from_dryrun.php` were not found (complete searches of the project tree, `%TEMP%` and agent stores; a depth-limited `D:\` scan was stopped after 31 minutes without a match), so their behaviour is reconstructed from the binary log only. Neither script was modified or run during this closure.

## 4. Current schema vs approved clone (section 3)

`evidence/phase-0.5-source-remediation/source-vs-clone-schema.json`

All 65 tables were compared: engine, collation, every column (position, type, nullable, default, extra, charset, collation), every index and every FK (columns, referenced table/columns, ON DELETE, ON UPDATE). **9 differences** were found, all in 4 tables:

| Table | Object | `leader` | Approved clone |
|---|---|---|---|
| agent_expenses | UNIQUE (agent_id, request_key) | `agent_expenses_agent_id_request_key_unique` | `agent_expense_request_unique` |
| booking_container_stages | UNIQUE (booking_container_id, type_id) | `booking_container_stages_booking_container_id_type_id_unique` | `container_stage_unique` |
| receipts | fk_receipts_booking_id → bookings | ON DELETE CASCADE | NO ACTION |
| receipts | fk_receipts_supplier_id → suppliers | ON DELETE CASCADE | NO ACTION |
| supplier_payments | fk_supplier_payments_supplier_id → suppliers | ON DELETE CASCADE | NO ACTION |
| agent_expenses | fk_agent_expenses_booking_service_id → booking_services | ON DELETE CASCADE | NO ACTION |
| booking_container_stages | fk_booking_container_stages_booking_container_id → booking_containers | ON DELETE CASCADE | NO ACTION |

Context for the owner decision: the historical migrations specify CASCADE for `agent_expenses.booking_service_id`, `booking_container_stages.booking_container_id` and `supplier_payments.supplier_id`, and `nullOnDelete` (SET NULL) for both `receipts` FKs. Neither `leader` nor the clone matches the migrations on the `receipts` FKs. The UNIQUE names in the migrations match the clone. The behavioural impact on `leader` is that deleting a booking or a supplier now silently deletes its `receipts`/`supplier_payments` rows (all 0 rows today), where the clone would refuse the delete.

## 5. Business data reconciliation (sections 4–6)

Authority: `storage/app/backups/leader_pre_remediation_20261003_184935.sql`. It is the only backup completed before the first accidental DROP (dump completed 21:49:40; DROP at 21:49:42).

`source-data-before.json` and backup `…190116.sql`, which the previous run used as the "pre-remediation" reference, were both captured **after** the incident. They are used only as secondary evidence.

Method: the dump's INSERT values were parsed directly, without restoring anything. Live rows were read through a read-only session with time_zone set to UTC to match mysqldump. Each row was hashed over all columns, and each table digest is order-independent.

| Comparison | Identical tables | Different |
|---|---|---|
| backup 184935 (pre-incident) vs `leader` now | **65 / 65** | 0 |
| backup 190116 (post-incident) vs `leader` now | 65 / 65 | 0 |
| backup 184935 vs backup 190116 | 65 / 65 | 0 |
| backup 184935 vs backup 185109 | 64 / 65 | model_has_roles (expected: dumped after restore #1 emptied it; this also shows the comparer detects differences) |
| backup 184935 vs `leader_dryrun` now | 65 / 65 | 0 |

- **17 critical tables**: all identical (`source-data-reconciliation.json`).
- **9 financial tables**: all identical. Row variance is 0 and monetary variance 0.00 EGP. Every pre-incident metric captured at 21:37 is reproduced exactly, including `agent_expenses` request keys (258 non-null / 258 distinct, plus a sha256 of id:agent_id:request_key). See `financial-reconciliation.json`.
- **Workflow**: bookings 474, booking_containers 691, booking_container_agents 1578, booking_container_stages 446; is_in_loading (0:96, 1:595); stage_type (0:494, 1:598, 2:486); stages by type (0:142, 1:169, 2:135). All unchanged (`workflow-reconciliation.json`).
- **Permissions**: roles 2, permissions 129, role_has_permissions 201 (sha256 equal), model_has_roles 8, model_has_permissions 0. The `suppliers_udpate` typo is preserved (`permission-reconciliation.json`).
- **`companies`** (`companies-reconciliation.json`):
  - Data: 43 rows, IDs/min/max and every row identical to the pre-incident backup, including the critical-field hash, opening_balance and wallet sums.
  - Relationships: all 9 inbound and outbound references are present with 0 orphans.
  - Schema: columns, indexes and FKs are identical to the clone, and the recovery DDL is text-identical to the pre-incident DDL.
- **`company_invoices`** (`company-invoices-reconciliation.json`): 0 rows before and now, with no row event ever recorded. Columns, indexes and FKs are identical to the clone, and the recovery DDL is text-identical to the pre-incident DDL.
- **Sync script**: `sync-script-audit.md`. Its only mutations were 2 truncates and 15 inserts, all on tables now proven identical to the pre-incident backup.

**Result: business data before == current business data.**

## 6. Constraint verification (section 7)

`evidence/phase-0.5-source-remediation/constraint-verification.json`

| Check | Expected | `leader` | Clone | Definitions equal to clone |
|---|---|---|---|---|
| Primary keys | 64 | 64 | 64 | yes |
| AUTO_INCREMENT | 60 | 60 | 60 | yes |
| Historical UNIQUE | 8 | 8 | 8 | **no** (2 names) |
| Historical non-unique indexes | 13 | **12** | **12** | yes |
| Active FKs | 61 | 61 | 61 | **no** (5 ON DELETE) |
| booking_papers.agent_id → agents | yes | yes | yes | — |
| booking_papers.agent_id → banks absent | yes | yes | yes | — |
| migrations rows | 0 | 0 | 0 | — |

Note on "13": the approved clone itself has 12 physical historical non-unique indexes. The manifest's 14 R4 operations include 2 for `agent_photos`, a table that doesn't exist in either database. So the figure of 13 in the clone report is not reproducible on the clone. `leader` matches the clone exactly for this group, so this is a documentation discrepancy, not a source-vs-clone divergence.

## 7. Classification (section 8)

**C — CURRENT SOURCE STATE HAS SCHEMA DIVERGENCE**

- Not A: the schema is not identical to the clone (9 differences).
- Not B: the source isn't merely *missing* approved operations. It contains *different* definitions, and the remaining delta can't be produced as "clone target minus current" additions.
- Not D: data is proven identical.
- Not E: the state is fully proven.

## 8. Actions taken / not taken

- DDL executed on `leader` during this closure: **0**
- DML executed: **0**. Restores: **0**. `migrations` touched: **no**. `yards` repaired: **no** (remains PRE-EXISTING FUNCTIONAL DEFECT).
- `run_source_remediation.php` was not modified or run; it was not found on disk.
- `sync_leader_from_dryrun.php` was not run; it was not found on disk.
- Nothing was copied from `leader_dryrun`.
- Full postflight (section 12), API contract regression and the final source-vs-clone sign-off were **not run**, because they are only permitted once the source equals the clone. The suite would also write Telescope/log rows into `leader`. See `final-postflight.json` and `api-contract-regression.json`.

## 9. Incident / recovery report (section 11)

`evidence/phase-0.5-source-remediation/recovery-status.json`

- **Divergence**: 5 FK ON DELETE rules and 2 UNIQUE names (above).
- **Affected tables**: receipts, supplier_payments, agent_expenses, booking_container_stages.
- **Last known safe backup**: `storage/app/backups/leader_pre_remediation_20261003_184935.sql`. It is row-identical to current `leader`. **Warning:** it embeds `USE leader`, so restoring it anywhere overwrites `leader`.
- **Backups not to use for recovery**: `…185109.sql` (`model_has_roles` empty, and it also embeds `USE leader`).
- **Recommended recovery action**:
  1. **(Recommended)** Owner approves a targeted correction of the 7 definitions so `leader` equals the approved clone. This means dropping and re-adding the 5 FKs without an ON DELETE clause and renaming the 2 UNIQUE indexes. It runs under the same guards (identity, write-free window, verified backup), followed by the full postflight. No data restore is required.
  2. Alternatively, the owner accepts the current `leader` definitions as the new target and re-baselines the clone. This changes delete semantics on financial tables, and the two `receipts` FKs match neither the migrations nor the clone, so it needs explicit business sign-off.
  3. Full rollback to backup 184935 followed by a clean single-pass re-run of the unmodified approved runner. This takes the most effort and brings no data benefit.
- Before any future restore test, dumps must be produced without `--databases` (or have the `CREATE DATABASE/USE` lines stripped).

## 10. Evidence index

`docs/modernization/evidence/phase-0.5-source-remediation/`

| File | Content |
|---|---|
| current-state-capture.json | Initial plus end-of-investigation capture, processlist, transactions, locks, write counters |
| execution-reconstruction.md | Chronology and proven/not-proven answers |
| binlog-leader-timeline.json | Binlog extract: restore passes, sync window row operations, every DDL statement on `leader` after 21:55 |
| source-vs-clone-schema.json | Full physical comparison (9 differences) |
| source-data-reconciliation.json | 65-table row-level comparison vs the pre-incident backup, plus secondary comparisons |
| companies-reconciliation.json | Enhanced audit |
| company-invoices-reconciliation.json | Enhanced audit |
| sync-script-audit.md | Sync mutation classification and proof |
| constraint-verification.json | Counts, missing/excess definitions, migration intent |
| financial-reconciliation.json | 9 tables vs pre-incident metrics and clone |
| workflow-reconciliation.json | Workflow counts and distributions |
| permission-reconciliation.json | Roles, permissions, pivots |
| api-contract-regression.json | NOT_EXECUTED (blocked by classification C) |
| recovery-status.json | Classification, backups, recovery options |
| final-postflight.json | Read-only gate observations; status BLOCKED |

Files from the previous run in the same folder:
- `execution-journal.json`: last invocation only; it misstates 5 FK definitions.
- `ddl-timings.json`
- `constraint-prechecks.json`
- `source-schema-before.json`, `source-data-before.json`, `backup-verification.json`: post-incident.
- `database-identity.json`, `write-free-preflight.json`: post-incident, 21:59:34.

They are retained unchanged as historical artefacts and are superseded by the files above.

The read-only investigation scripts and decoded binlog extracts are in `storage/app/phase05_closure/` (reproducible).

## 11. Final decision

```
SOURCE REMEDIATION BLOCKED — NO FURTHER SOURCE DDL EXECUTED
```

HARD STOP. No Canonical Baseline, no migrations population or archiving, no `yards` fix, no Phase 1. Awaiting owner review and approval of a recovery option.

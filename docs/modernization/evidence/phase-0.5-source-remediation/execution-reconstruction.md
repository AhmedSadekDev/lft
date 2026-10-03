# Phase 0.5 Source Remediation — Execution Reconstruction

Generated: 2026-10-03 (closure investigation, read-only)

## Sources of truth used

| Source | What it proves |
|---|---|
| `D:\laragon\data\mysql-8.4\binlog.000079` (ROW format, `log_bin=ON`, covers 18:06:38 → 22:20:36 local) | Every DDL statement and every row write that reached the server, with connection (thread) id and timestamp. Failed statements are **not** logged. |
| `storage/app/backups/leader_pre_remediation_*.sql` (4 files) | Exact data/DDL at each dump time; header shows whether the dump embeds `CREATE DATABASE … USE leader`. |
| `evidence/phase-0.5-source-remediation/execution-journal.json` | Only the **last** runner invocation (connection 274). Earlier invocations overwrote it. |
| `evidence/phase-0.5-remediation/*` | Approved clone run (`leader_dryrun`) and pre-incident `source` fingerprints captured 21:37 local. |
| Current `leader` / `leader_dryrun` (information_schema, read-only session) | Present physical state. |

Not available: the previous agent chat transcript, `run_source_remediation.php` and `sync_leader_from_dryrun.php` (neither file was found — complete searches of the `D:\laragon\www\leader` tree, `%TEMP%` and Cursor agent stores; a depth-limited scan of `D:\` ran 31 minutes without finding them and was stopped before completion). Their behaviour is therefore reconstructed **only** from the binlog.

All times below are server-local (UTC+3). "t=N" is the MySQL connection id.

## Chronology

| Time | Conn | Database | Event | Evidence |
|---|---|---|---|---|
| 21:17:43–21:18:33 | 168 | leader_dryrun | Clone created: `DROP/CREATE DATABASE leader_dryrun`, 65× `CREATE TABLE … LIKE leader.*` + data copy | binlog |
| 21:22:58–21:33:05 | 171,182,189 | leader_dryrun | Approved clone remediation: 64 PK, 60 AI, 8 UNIQUE, 12 secondary indexes, 61 FK | binlog, clone journal |
| 21:37:32 | — | leader | Pre-incident fingerprints of `leader` captured (`source` values in `phase-0.5-remediation/*-reconciliation.json`) | evidence files |
| 21:49:35–21:49:40 | — | leader | **Backup #1** `…_184935.sql` dumped. Header contains `CREATE DATABASE IF NOT EXISTS leader; USE leader;` | backup file |
| 21:49:40–21:49:42 | 218 | leader_restore_verify | `DROP/CREATE DATABASE leader_restore_verify` | binlog |
| **21:49:42–21:50:40** | **219** | **leader** | **Accidental restore #1 into `leader`**: `CREATE DATABASE IF NOT EXISTS leader`, then DROP+CREATE+INSERT for 27 tables (agent_car_tranfers → log_activities); `migrations`, `model_has_permissions`, `model_has_roles` DROP+CREATE with no INSERT; run stops after `CREATE TABLE model_has_roles` | binlog |
| 21:51:09–21:51:12 | — | leader | **Backup #2** `…_185109.sql` dumped (after restore #1; `model_has_roles` empty in this dump); also embeds `USE leader` | backup file, row-level digest |
| 21:51:13–21:51:44 | 221/222 | **leader** | **Accidental restore #2**: DROP+CREATE+INSERT agent_car_tranfers → company_fatoorahs; stops right after `DROP TABLE company_invoices` (table left **missing**) | binlog |
| 21:52:46–21:53:33 | 225 | **leader** | **Accidental restore #3**: agent_car_tranfers → invoices (re-creates company_invoices) | binlog |
| 21:53:56–21:54:33 | 226 | **leader** | **Accidental restore #4**: agent_car_tranfers → `CREATE TABLE containers` (no INSERT → containers left **empty**) | binlog |
| 21:54:54–21:55:21 | 228 | **leader** | **Accidental restore #5**: agent_car_tranfers → cities_and_regions; stops right after `DROP TABLE companies` (table left **missing**) | binlog |
| 21:56:41 | 230 | leader | Manual recovery: `CREATE TABLE IF NOT EXISTS leader.company_invoices` (no rows; the table had 0 rows pre-incident) | binlog |
| 21:58:46–21:58:47 | 234 | leader | Manual recovery: `CREATE TABLE IF NOT EXISTS leader.companies` + INSERT 43 rows | binlog -v |
| 21:59:26–21:59:28 | 237 | leader | **Sync (`sync_leader_from_dryrun.php`)**: `TRUNCATE leader.containers` + INSERT 7 rows; `TRUNCATE leader.model_has_roles` + INSERT 8 rows | binlog -v |
| 21:59:34 | — | leader | `database-identity.json` / `write-free-preflight.json` written (post-incident) | evidence files |
| 21:59:48–21:59:51 | — | leader | **Backup #3** `…_185948.sql` (no `CREATE DATABASE/USE`) | backup file |
| 21:59:52–22:00:25 | 240/241 | leader_restore_verify | Restore verification into the correct target; stops after `failed_jobs` | binlog |
| 22:01:16–22:01:19 | — | leader | **Backup #4** `…_190116.sql` (no `CREATE DATABASE/USE`) | backup file |
| 22:01:20–22:02:33 | 244/245 | leader_restore_verify | Full restore verification into correct target (all 65 tables) | binlog, `backup-verification.json` |
| 22:08:06 / 22:08:09 / 22:10:57 | — | leader | `source-schema-before.json`, `source-data-before.json`, `constraint-prechecks.json` (all **post-incident**) | evidence files |
| 22:11:25–22:15:35 | 257 | leader | **Runner invocation #1**: 64 ADD PRIMARY KEY + 60 MODIFY … AUTO_INCREMENT | binlog (124 stmts) |
| 22:16:31–22:16:45 | 272 | leader | **Runner invocation #2**: 8 ADD UNIQUE KEY (Laravel default names) + 5 ADD INDEX | binlog (13 stmts) |
| 22:17:21–22:17:53 | 273 | leader | **Runner invocation #3**: 7 ADD INDEX + 8 ADD FOREIGN KEY, of which 5 carry `ON DELETE CASCADE` not present in the clone | binlog (15 stmts) |
| 22:18:29–22:20:33 | 274 | leader | **Runner invocation #4**: 53 ADD FOREIGN KEY; journal records 154 ALREADY_APPLIED + 53 EXECUTED | binlog (53 stmts), journal |
| 22:20:36 | — | — | Last binlog event. No further writes on the server up to and including this investigation (binlog size unchanged at 108,750,228 bytes) | binlog, `current-state-capture.json` |

Executed DDL on `leader` by the runner: 124 + 13 + 15 + 53 = **205** statements = 207 journal operations − 2 `agent_photos` indexes (table does not exist).

## Answers to the required questions

### Why did restore attempts occur?
**PROVEN.** They were restore-verification tests of the pre-remediation backup into `leader_restore_verify` (connection 218 created that database at 21:49:40). Backups #1 and #2 were produced by `mysqldump --databases leader` style output: their header contains `CREATE DATABASE IF NOT EXISTS \`leader\`` followed by `USE \`leader\``. When fed to a client connected to `leader_restore_verify`, the `USE` statement switched the session to `leader`, so every `DROP TABLE` / `CREATE TABLE` / `INSERT` hit the production source. The binlog shows each of connections 219, 222, 225, 226, 228 beginning with exactly that `CREATE DATABASE IF NOT EXISTS leader` statement. Backups #3/#4 no longer contain these lines and their restores (240/241, 244/245) went to the correct database.

### Why were MySQL client processes force-stopped?
**CAUSE NOT PROVEN.** The binlog shows that each of the five accidental restore streams stops abruptly mid-sequence (after a DROP or CREATE, with the next table never started) and the mysqld error log records no server-side error or crash for those connections. This pattern is consistent with the client being terminated, but no process log, shell log or transcript survives to show who stopped them or why.

### Why was `company_invoices` recreated?
**PROVEN (mechanism).** Accidental restore #2 (connection 222) stopped immediately after `DROP TABLE IF EXISTS company_invoices` at 21:51:44. Restores #3 and #4 recreated it, but the table was then recreated manually by connection 230 at 21:56:41 with `CREATE TABLE IF NOT EXISTS` (a no-op if it already existed; MySQL logs it regardless). The pre-incident dump contains **no rows** for `company_invoices`, and no row event for it exists anywhere in the binlog, so there was no data to restore. The manual DDL is text-identical to the pre-incident dump DDL. Why the operator believed it was missing at 21:56: **CAUSE NOT PROVEN.**

### Why was `companies` restored?
**PROVEN.** Accidental restore #5 (connection 228) stopped immediately after `DROP TABLE IF EXISTS companies` at 21:55:21, leaving the table absent. Connection 234 recreated it at 21:58:46 (DDL text-identical to the pre-incident dump) and inserted 43 rows at 21:58:47. The row source (which database/table the INSERT … SELECT read from) is not recorded in ROW-format binlog; server counter `Com_insert_select = 68` (65 clone copies + 3) is consistent with `INSERT … SELECT` from `leader_dryrun`. Source table: **NOT PROVEN**, outcome **PROVEN**: the 43 rows are byte-identical to the pre-incident backup.

### What did `sync_leader_from_dryrun.php` actually change?
**PROVEN from binlog (script file not available).** Connection 237, 21:59:26–21:59:28:
- `TRUNCATE TABLE leader.containers` → INSERT 7 rows
- `TRUNCATE TABLE leader.model_has_roles` → INSERT 8 rows

Nothing else. See `sync-script-audit.md`.

### Why did the source remediation runner require edits during execution?
**CAUSE NOT PROVEN** for the reasons. What is **PROVEN** is that four separate invocations ran (connections 257, 272, 273, 274), each starting where the previous one stopped, and that the generated DDL changed between invocations:
- Invocation #2 (272) used Laravel default UNIQUE names `agent_expenses_agent_id_request_key_unique` and `booking_container_stages_booking_container_id_type_id_unique`, whereas the approved clone (connection 189, 21:30:25–26) used `agent_expense_request_unique` and `container_stage_unique`.
- Invocation #3 (273) emitted `ON DELETE CASCADE` for `fk_receipts_booking_id`, `fk_receipts_supplier_id`, `fk_supplier_payments_supplier_id`, `fk_agent_expenses_booking_service_id`, `fk_booking_container_stages_booking_container_id`. The approved clone created these with **no** ON DELETE clause (= NO ACTION).
- Invocation #4 (274) recorded those same five FKs in its journal with DDL **without** ON DELETE and marked them `ALREADY_APPLIED` (precheck by constraint name only), so the CASCADE definitions created by #3 were never corrected and the journal misrepresents what is physically in `leader`.

Failed statements are not binlogged, so the error that ended invocations #1–#3 cannot be recovered.

### Which operations were already applied before every rerun?

| Invocation | Already applied on `leader` at start | Executed by it |
|---|---|---|
| #1 conn 257 | none | 64 PK, 60 AI |
| #2 conn 272 | 64 PK, 60 AI | 8 UNIQUE, 5 INDEX (password_resets, booking_container_agents stage_type, model_has_permissions, model_has_roles, telescope type/should_display) |
| #3 conn 273 | + 8 UNIQUE, 5 INDEX | 7 INDEX (payingcars, receipts ×2, supplier_payments, telescope batch/family/created_at), 8 FK |
| #4 conn 274 | + 12 INDEX, 8 FK | 53 FK |

### Did any operation differ from the proven clone manifest?
**YES — PROVEN.**
1. 2 UNIQUE constraints created under different names (same columns, same uniqueness).
2. 5 FKs created with `ON DELETE CASCADE` instead of the clone's NO ACTION.
3. Cosmetic only: `bigint(20) UNSIGNED` vs `BIGINT UNSIGNED` in MODIFY statements — physical result identical (column comparison shows zero differences).
4. 207 vs 208 operations: the clone manifest includes `users_email_unique`, skipped on both because `users` does not exist physically.

The 2 + 5 differences are the 9 rows (2×2 index entries + 5 FK entries) in `source-vs-clone-schema.json`.

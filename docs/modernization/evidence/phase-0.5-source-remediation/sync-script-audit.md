# Audit — `sync_leader_from_dryrun.php`

Generated: 2026-10-03 (closure investigation, read-only)

## Script availability

The script **cannot be read**: `sync_leader_from_dryrun.php` was not found (complete recursive searches of `D:\laragon\www\leader` incl. ignored folders, `%TEMP%` and Cursor agent stores; a depth-limited scan of `D:\` ran 31 minutes without a match and was stopped before completion). It was not committed to git (`git status` / reflog show no such file). Its mutations are therefore reconstructed exclusively from `binlog.000079`, which records every successful write on the server.

Identification: the only writes to `leader` between the manual recovery of `companies` (connection 234, 21:58:47) and the first runner DDL (connection 257, 22:11:25) come from connection **237** at 21:59:26–21:59:28. This matches the position of the sync step in the execution history (after `companies`/`company_invoices` recreation, before backup #3 at 21:59:48).

## Possible-mutation classification (from binlog of connection 237)

| Category | Executed? | Detail |
|---|---|---|
| schema-only (ALTER) | No | none |
| table-create | No | none |
| table-drop | No | none |
| truncate | **Yes** | `TRUNCATE TABLE leader.containers` (21:59:26), `TRUNCATE TABLE leader.model_has_roles` (21:59:27) |
| insert / data-copy | **Yes** | `leader.containers` 7 rows (21:59:27); `leader.model_has_roles` 8 rows (21:59:28) |
| update | No | no Update_rows events on `leader` anywhere in binlog.000079 |
| delete | No | no Delete_rows events on `leader` anywhere in binlog.000079 |
| other | No | none |

Total row changes: 15 inserts after 2 truncates. No other `leader` table, and no other database, was written by connection 237.

## Why these two tables

Both had been left empty by the accidental restores (see `execution-reconstruction.md`):
- `containers` — restore #4 (connection 226) executed `CREATE TABLE containers` at 21:54:33 and stopped before its INSERT.
- `model_has_roles` — restore #1 (connection 219) executed `CREATE TABLE model_has_roles` at 21:50:40 and stopped before its INSERT; no later restore pass reached it.

## Source of the copied rows

ROW-format binlog stores the inserted values but not the `SELECT` source. Server counter `Com_insert_select = 68` since server start (65 clone copies at 21:17–21:18 + 3 later statements) is consistent with `INSERT … SELECT` from `leader_dryrun` for `companies` (conn 234), `containers` and `model_has_roles` (conn 237). Source: **NOT PROVEN** — irrelevant to the outcome below.

## Reconciliation of affected tables against the pre-incident backup

Authority: `leader_pre_remediation_20261003_184935.sql` (dump completed 21:49:40; first accidental DROP 21:49:42).

| Table | Rows backup 184935 | Rows `leader` now | Row-level content (sha256 of sorted row hashes) |
|---|---|---|---|
| containers | 7 | 7 | identical |
| model_has_roles | 8 | 8 | identical |
| companies (conn 234, same pattern) | 43 | 43 | identical |

Full per-row evidence: `source-data-reconciliation.json`, `permission-reconciliation.json`, `companies-reconciliation.json`.

## Conclusion

The sync step copied business data into `leader` (2 truncates + 15 inserts). Every affected table is proven byte-identical to the authoritative pre-incident backup. It made no schema change.

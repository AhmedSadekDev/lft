# Legacy Agent ID Column Drift — Task List

**Status:** Backlog (not started)  
**Scope:** Separate from USD vault module  
**Related audit:** [usd-vault-code-audit-and-implementation-plan.md](usd-vault-code-audit-and-implementation-plan.md)  
**Policy:** [permanent-database-policy.md](permanent-database-policy.md) — read-only schema inspection only until a separate fix is owner-approved  
**Date noted:** 2026-10-21

---

## Assessment (owner-aligned)

| Column | Finding |
|--------|---------|
| `agient_id` | Used on several current financial write/filter paths |
| `agent_id` | Written on agent custody transfer path; may not match the rest of the codebase |

**Do not rename or alter either column as part of the USD vault feature.** Changing names can affect custody movement and financial recording. The new USD vault must use its own relations and columns and must not depend on either FK.

USD vault Phase 0 audit remains **complete**; implementation has **not** started. Column-name drift does **not** block designing a standalone USD vault; it remains a **separate risk** to document and fix outside that feature.

---

## Confirmed write/filter call sites (seed inventory)

| Location | Column | Role |
|----------|--------|------|
| `app/Services/BankTransactionService.php` | `agient_id` | Filter `whereNull('agient_id')` when syncing bank↔vault links |
| `app/Http/Controllers/Api/Agent/TransferAgentController.php` | `agient_id` | Vault return / custody write |
| `app/Http/Controllers/Admin/SupplierController.php` | `agient_id` | Supplier settle vault transaction |
| `app/Http/Controllers/Admin/Booking/BookingServiceController.php` | `agient_id` | Booking service vault payment |
| `app/Http/Controllers/Admin/MoneyTransferController.php` | `agent_id` | Agent custody out — **likely mismatch** vs model relation `agient()` |

*(Full read/write inventory is task 1 below — expand beyond this seed list.)*

---

## Tasks

### 1. Trace all reads and writes

- [ ] Repository-wide search for `agient_id` and `agent_id` (models, controllers, services, jobs, exports, raw SQL, tests).
- [ ] Classify each hit: read / write / filter / relation / dead code.
- [ ] Note table target (`vault_transactions` vs others) and whether the path assumes EGP vault tables exist.

### 2. Verify actual schema (read-only)

- [ ] For each target environment (local import, staging, production if authorized): inspect column presence via read-only `information_schema` / `SHOW COLUMNS` — **no DDL, no DML**.
- [ ] Document per environment: `agient_id` present? `agent_id` present? Neither? Both?
- [ ] Cross-check create/modify migrations vs fingerprint / live schema; mark `LEGACY CODE / DATABASE MISMATCH` where tables or columns are absent.
- [ ] Label evidence grade: confirmed (live read-only) vs inferred from code vs unverified.

### 3. Expected behavior and regression tests

- [ ] Per financial path, define expected custody / vault_transaction linkage behavior.
- [ ] Identify observable bugs today (e.g. custody out writes `agent_id` while other paths read `agient_id`).
- [ ] List regression tests needed before any fix (money transfer store, agent return to vault, supplier settle, booking service vault pay, bank↔vault sync).
- [ ] Compare against existing Phase 5 / custody tests; do not change business expectations only to green-wash failures.

### 4. Independent fix proposal (review only)

- [ ] Draft a standalone remediation proposal (preferred: align writers to the column that actually exists and is used by readers; or dual-write only if evidence requires it).
- [ ] Explicitly **exclude** USD vault schema and code from the proposal.
- [ ] No operational DB changes and no financial code changes until the owner approves a dedicated fix run.
- [ ] If DDL is required, ship as owner-approved reviewable SQL only — never `artisan migrate` against the operational database.

---

## Out of scope

- USD vault tables, routes, permissions, or ledger design.
- Renaming columns “while we are there” inside the USD vault implementation run.
- Reconstructing missing `vaults` / `vault_transactions` tables (separate LEGACY MISMATCH work).

---

## Exit criteria for this backlog item

- Full read/write map of both column names.
- Per-environment schema evidence (read-only).
- Expected behavior + regression list per path.
- Written fix proposal ready for owner review, with USD vault remaining untouched.

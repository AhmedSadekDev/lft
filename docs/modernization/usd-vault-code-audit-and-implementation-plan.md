# USD Vault Module — Code Audit and Implementation Plan

**Document type:** Phase 0 audit + future implementation plan  
**Date:** 2026-10-21  
**Branch verified:** `devlop_test` (tracking `origin/devlop_test`)  
**Runtime verified:** PHP `8.3.26`  
**Policy:** [permanent-database-policy.md](permanent-database-policy.md), [AGENTS.md](../../AGENTS.md)  
**Status:** Audit only — **no application code, migrations, SQL execution, or operational DB changes** in this run.

---

## 1. Executive summary

Leader for Trans today has:

1. **A single cash vault** (`Vault::first()->amount`) treated as an EGP cash box, with append-style movements in `vault_transactions` (`type` 0 = outflow, 1 = inflow). There is **no currency column** on vault tables or models.
2. **A dual EGP/USD company AR ledger** (`InvoiceCurrency`, `CompanyCurrencyLedger`, `invoices.usd_amount`, `invoice_payments.currency`) that already isolates USD receivables from EGP AR. That system does **not** feed a USD cash vault.
3. **Bank books** that keep `banks.amount` as EGP; USD company deposits are tagged on `bank_trnsactions.currency` and must not increase EGP bank balance (with at least one known gap on check clearance).

**Owner decision (this plan):** introduce a **standalone USD vault** with its own opening balance, ledger, and current balance. It must **not** modify, share, or silently affect existing vault balances, EGP transactions, bank balances, receipts, invoices, expense records, refunds, or other existing financial calculations.

**Critical verified blocker:** Phase 0.5 schema fingerprint reports physical tables `vaults` and `vault_transactions` as **`NOT MATCHED`** (absent) on the imported `leader` MySQL database — `LEGACY CODE / DATABASE MISMATCH`. Any USD vault tables also require a **separately owner-approved** reviewable SQL provisioning strategy (not `artisan migrate`).

**This run produced only this document.** Implementation waits for owner approval.

---

## 2. Verified current financial architecture

### 2.1 Single vault (cash box)

| Item | Evidence |
|------|----------|
| Model | [`app/Models/Vault.php`](../../app/Models/Vault.php) — `$guarded`; legacy `getTypeNameAttribute()`; **no relations; no currency** |
| Balance | Almost all flows use `Vault::first()` / `Vault::lockForUpdate()->firstOrFail()` and mutate **`vaults.amount`** |
| Ledger model | [`app/Models/VaultTransaction.php`](../../app/Models/VaultTransaction.php) — `bank()`, `agient()` (FK convention **`agient_id`**) |
| Admin UI | [`app/Http/Controllers/Admin/VaultController.php`](../../app/Http/Controllers/Admin/VaultController.php), views under `resources/views/admin/vaults/` |
| Movement UI | [`VaultTransactionController`](../../app/Http/Controllers/Admin/VaultTransactionController.php) — index filters **`type = 0` only** (hides deposits); **no permission middleware** |
| Semantics (docs) | [`docs/الشجرة-المحاسبية-Leader-for-Trans.md`](../الشجرة-المحاسبية-Leader-for-Trans.md) §8 — account **1101** main vault; VT type 0 صرف / 1 وارد |
| Observers | **None** for Vault / VaultTransaction under `app/Observers/` |

### 2.2 Confirmed vault debit/credit call sites

| Flow | Entry | Vault Δ | VaultTransaction | `lockForUpdate` on vault? |
|------|--------|---------|------------------|---------------------------|
| Manual vault CRUD | `VaultController::store/update` | set/create amount | none on store | No |
| Agent custody out | `MoneyTransferController::store` | − | type 0; writes **`agent_id`** (likely wrong vs `agient_id`) | No |
| Superagent custody out | `SuperagentMoneyTransferController::store` | − | **none** | No |
| Bank → vault | `BankTransactionController::store` type `0` | + | type 0 + `bank_id` | Yes (Bank + Vault) |
| Car pay (policies) | `CarPayingController` | − / + | type 0/1 | Yes (Phase 5) |
| Car pay (account batch) | `AccountController::processCarPayment` | − | type 0 | **No** |
| Supplier settle (safe) | `SupplierController::deductFromPaymentSource` | − | type 0 | Yes |
| Booking service pay | `BookingServiceController` `payment_type=vault` | −/+ | type 0/1 | No |
| Agent return to vault | `Api\Agent\TransferAgentController` when vault flag | + | type 1 + `agient_id` | No |
| Bank tx edit/delete | `BankTransactionService` | reverse vault side | sync/delete linked VT | Yes |

**Evidence grade:** Confirmed in repository code and Phase 5 concurrency audit.

### 2.3 Dual company AR (EGP / USD) — already built, not a vault

| Piece | Path | Role |
|-------|------|------|
| `InvoiceCurrency` | `app/Support/InvoiceCurrency.php` | Line normalize; USD never folds into EGP invoice AR |
| `CompanyCurrencyLedger` | `app/Support/CompanyCurrencyLedger.php` | Dual buckets for invoice totals / paid / remaining |
| Invoice fields | `invoices.currency`, `invoices.usd_amount` | Hybrid vs pure USD |
| Payments | `invoice_payments.currency` | Settle one currency bucket only |
| Company opening | `companies.opening_balance` (EGP) + `opening_balance_usd` | Dual opening |
| SQL (reviewable) | `docs/sql/2026-10-16-dual-currency-ledgers.sql` | Confirmed in repo |
| Tests | `tests/Unit/CompanyCurrencyLedgerTest.php`, `InvoiceCurrencyTest.php`, `tests/Feature/MultiCurrencyInvoicingTest.php` | Confirmed |

**Confirmed gap:** dual ledger stops at **company AR / invoice lines / tagged bank deposits**. It does **not** maintain a USD cash vault balance.

### 2.4 Where USD already appears — isolation vs mixing

| Layer | Status | Notes |
|-------|--------|-------|
| Company AR / invoice payments | **Isolated** | Separate EGP/USD buckets |
| Bank **balance** on company USD payment | **Isolated (by design)** | `AccountController::recordCompanyBankDeposit` tags USD; does not add to `banks.amount` |
| Bank **balance** on check clear | **Risk: mixed** | `markCheckAsPaid` always credits `banks.amount` without USD guard (**proposal to fix separately**) |
| Vault / agent & superagent wallets / custody | **EGP book only** | No currency column |
| Booking service cash side | **Mixed when rate present** | AR uses `foreign_amount`; vault/bank/wallet debit uses converted **`price`** (EGP) |
| Money transfers | **No currency** | EGP-only |

---

## 3. Relevant file and route inventory

### 3.1 Vault routes (confirmed — `routes/web.php`)

- Resource: `vaults` → `VaultController` (`vaults.index|create|store|show|edit|update|destroy`)
- `GET vaults-exports` → `vaults.export`
- Prefix typo: `vaultransactions` → `vaultransactions.index|edit|update|destroy|export`

### 3.2 Permissions (confirmed mismatch)

| Location | Names |
|----------|--------|
| `VaultController` middleware | `vault.index`, `vault.create`, `vault.udpate` (typo), `vault.delete` |
| Sidebar | `hasPermissionTo('vaults.index')` → `route('vaults.index')` |
| Lang / permissions form | `vaults.*` group «الخزن» |
| `VaultTransactionController` | **no** `permission:` middleware |

### 3.3 Navigation

- Sidebar Finance → «الخزنة» (`resources/views/layouts/includes/sidebar.blade.php`)
- Company accounts / checks / profit-loss under Accounts & Reports
- Custody agents/superagents under Finance
- General expenses report gated by `daily_reports.index`

### 3.4 Exports / views

- `app/Exports/VaultExport.php`, `VaultTransactionExport.php`
- `resources/views/admin/vaults/*`, `resources/views/admin/vaulttransactions/*`

### 3.5 Related financial controllers (must remain unchanged by USD vault v1)

- `AccountController` (company statement/payments)
- `BankTransactionController`, `BankTransactionService`
- `BookingServiceController`, `BookingInvoiceController`
- `MoneyTransferController`, `SuperagentMoneyTransferController`
- `SupplierController`, `CarPayingController`
- `ReportController::general_expenses`

---

## 4. Existing database schema findings and mismatches

### 4.1 Evidence grades used in this document

| Grade | Meaning |
|-------|---------|
| **Confirmed (repo)** | Present in application code, migrations, or reviewable SQL under `docs/sql/` |
| **Confirmed (fingerprint)** | Stated in Phase 0.5 schema fingerprint against imported MySQL |
| **Inferred** | Required by runtime call sites but missing from create migration |
| **Unverified** | Live column presence on a given production host not re-queried in this audit |

### 4.2 Vault tables

| Object | Repo migrations | Fingerprint (imported `leader`) |
|-------|-----------------|----------------------------------|
| `vaults` | `2024_05_28_155139_create_vaults_table.php` then stripped to amount-only by `2024_06_08_081753_modify_in_vaults_table.php` | **`NOT MATCHED` — table does not physically exist** |
| `vault_transactions` | `2024_06_19_012955_create_vault_transactions_table.php` | **`NOT MATCHED` — table does not physically exist** |

**Classification:** `LEGACY CODE / DATABASE MISMATCH`  
**Runtime impact:** vault screens and all vault mutators fail with missing-table SQL errors where the import lacks these tables.  
**Policy response:** document and continue; do **not** reconstruct via `artisan migrate`.

### 4.3 Column drift (inferred / confirmed in code)

| Column | Status |
|--------|--------|
| `vaults.amount` | Confirmed in code + test schemas |
| `vault_transactions.bank_id, name, amount, type` | Confirmed in code |
| `vault_transactions.agient_id` | **Inferred required** by Supplier / TransferAgent / BookingService / BankTransactionService; **absent from create migration**. Separate backlog: [legacy-agent-id-column-drift-task-list.md](legacy-agent-id-column-drift-task-list.md) |
| `vault_transactions.agent_id` | Written by `MoneyTransferController` — likely dead/wrong vs `agient_id`. **Do not fix inside USD vault work.** |
| `vaults.currency` / `vault_transactions.currency` | **Confirmed absent** |

### 4.4 Dual-currency AR schema (confirmed in repo SQL)

Present in reviewable SQL (not via migrate):

- `companies.opening_balance_usd`
- `invoices.currency`, `invoices.usd_amount`
- `invoice_payments.currency`
- `bank_trnsactions.currency`

**No vault DDL** appears under `docs/sql/`.

### 4.5 Live DB re-check in this run

No operational `SHOW TABLES` / business writes were executed (policy). Fingerprint remains the authoritative documented mismatch for vaults.

---

## 5. Existing balance and transaction calculation behavior

### 5.1 Vault balance

- **Materialized field:** `vaults.amount` is the live balance.
- **Ledger:** `vault_transactions` rows are created alongside many (not all) movements; **balance is not recomputed from the ledger** in production code paths reviewed.
- **Type convention:** `0` = outflow (decreases amount), `1` = inflow (increases amount) — confirmed by call sites and accounting tree doc.
- **Precision:** amounts often handled as float/string in legacy code; Phase 5 tests add in-memory decimal-like safety for concurrent car paying — **not** uniform across all vault writers.

### 5.2 Company AR balance (for contrast)

- Computed from invoice totals − payments per currency via `CompanyCurrencyLedger`.
- USD invoice lines use `foreign_amount` / `usd_amount`; EGP lines use `price`.
- **Must not** be used as the USD vault cash balance.

### 5.3 Booking service USD with rate

- AR: USD stays in USD bucket.
- Cash (vault/bank/agent): debits **EGP `price`** when `payment_type` is vault/bank/agent.
- Therefore: recording a USD service with a rate **does not** correctly represent “cash left the USD vault”; it hits the EGP cash books.

---

## 6. Standalone USD module boundary

### 6.1 What the USD vault owns (proposed)

- USD opening balance (auditable opening entry).
- USD deposits and withdrawals.
- USD transaction ledger (append-only with reversals).
- USD current balance.
- Transaction category, reference, description, date.
- Creator (and approver if product requires it later).
- Search/filters/statement/export.
- Dedicated permissions and menu entry labeled **USD** explicitly.

### 6.2 What it must NOT do (hard isolation)

- Must not read/write `vaults.amount` or existing `vault_transactions` for USD cash.
- Must not change `banks.amount`, agent/superagent wallets, or EGP money transfers as a side effect of USD vault posting.
- Must not auto-convert USD↔EGP or post FX gain/loss (unless owner later approves a separate decision).
- Must not auto-sync from existing receipts, booking services, invoice payments, or EGP vault rows.
- Must not alter Phase 5 financial concurrency semantics of existing EGP flows.
- Must not use `invoices.usd_amount` or company AR remaining as the vault balance.

### 6.3 Recording vs modifying existing workflows

| Real-world event | Existing system (unchanged) | USD vault (new, optional/manual in v1) |
|------------------|----------------------------|----------------------------------------|
| EGP receipt / vault custody | Existing vault / wallets | **No automatic entry** |
| USD invoice payment to bank | Company AR + tagged bank row | **Optional later:** manual or controlled “record reference” deposit — **not** auto |
| Cash USD deposit to company | N/A or informal | Deposit in USD vault |
| Cash USD withdrawal / USD operating expense | Often forced through EGP books today | Withdrawal / expense category in USD vault |
| Hybrid invoice AR | Dual ledger | No effect on USD vault |

**Double-counting rule:** the same physical cash movement must not be entered both as an automatic sync and a manual USD vault row. v1 = **manual posting only** inside the new module.

---

## 7. Proposed data model and ledger rules

> Table/column names below are **proposals** after reviewing conventions (`*_transactions`, decimal money, morph/creator patterns). Final names require owner approval before SQL is written.

### 7.1 Recommended shape (auditable ledger + materialized balance)

**Proposal A (recommended):**

1. `usd_vaults` — typically **one** row for the company cash USD box  
   - `opening_balance` `DECIMAL(14,2)` not null default 0  
   - `balance` `DECIMAL(14,2)` not null default 0  ← materialized current balance  
   - `name`, timestamps, optional `is_active`
2. `usd_vault_transactions` — append-only ledger  
   - `usd_vault_id`  
   - `direction` tinyint or enum: `in` / `out` (or type 1/0 mirroring existing vault semantics — prefer explicit `in`/`out` strings to avoid confusion)  
   - `category` varchar: `opening_balance`, `deposit`, `withdrawal`, `collection`, `settlement`, `expense`, `adjustment`, `reversal`  
   - `amount` `DECIMAL(14,2)` — always positive; direction decides sign in balance math  
   - `currency` char(3) default `'USD'` enforced  
   - `occurred_at` datetime  
   - `reference` nullable string (external doc / invoice number / note id)  
   - `description` text  
   - `created_by` user id  
   - `reversed_at` / `reversal_of_id` for corrections  
   - `idempotency_key` nullable unique (duplicate submit guard)  
   - timestamps  

**Why materialized `balance`:** matches current vault pattern (`Vault::first()->amount`) and supports `lockForUpdate` on the vault row for concurrent withdrawals. Ledger remains the audit trail; balance is updated **only inside the same DB transaction** as the ledger insert.

**Alternative B:** balance = `SUM(in) − SUM(out)` excluding reversed rows. Safer for audit purity; heavier on every read; still needs locking strategy for concurrent insufficient-balance checks. **Not recommended for v1** given existing app conventions and Phase 5 lock patterns.

### 7.2 Balance rules (must be explicit in code)

1. Deposit / collection / opening → increase balance.  
2. Withdrawal / settlement / expense → decrease balance.  
3. Opening balance is a normal ledger entry (category `opening_balance`), not a silent field edit.  
4. Corrections: post `reversal` (and optional replacement), never destructive UPDATE of amount on posted rows (except metadata).  
5. Voided/reversed rows excluded from effective balance (via `reversed_at` or linked reversal).  
6. Withdrawal rejected if `balance < amount` under row lock.  
7. All money math uses `bc*` or integer cents / `Brick\Money` / decimal casting — **no float** for persistence.  
8. Ledger insert + balance update are **atomic** (`DB::transaction` + `lockForUpdate`).  
9. Duplicate form posts blocked by unique `idempotency_key` or PRG + token.

### 7.3 Categories (v1)

| Category | Direction | Notes |
|----------|-----------|-------|
| `opening_balance` | in (or signed once) | First-time / period open |
| `deposit` | in | Cash USD in |
| `withdrawal` | out | Cash USD out |
| `collection` | in | Manual record of USD collection (reference only) |
| `settlement` | out | Manual USD settlement paid from vault |
| `expense` | out | Operating expense paid in USD from vault |
| `adjustment` | in or out | Rare; permission-gated |
| `reversal` | opposite of original | Links `reversal_of_id` |

---

## 8. Proposed UI, routes, permissions, and reports

### 8.1 Visibility

- New Finance submenu item: **«خزنة الدولار» / USD Vault** — visually distinct from «الخزنة».
- All labels/totals show **`$` / USD** explicitly.
- Do not reuse EGP vault routes or Blade under `admin/vaults` without a separate namespace.

### 8.2 Screens (v1)

1. Overview — current USD balance + period summary (in/out).  
2. Transaction history — paginated, filters: date from/to, category, direction, search reference.  
3. Deposit form.  
4. Withdrawal form (insufficient balance error).  
5. Quick entry forms for collection / settlement / expense (or one form with category select).  
6. Transaction detail.  
7. Export Excel/PDF statement (mirror `VaultExport` / company statement patterns).  
8. Opening balance / reconciliation screen (permission-gated).

### 8.3 Routes (proposal)

Prefix example: `admin/usd-vault` or `usd-vaults`  
Resource-style:

- `GET usd-vault` overview  
- `GET usd-vault/transactions`  
- `GET/POST usd-vault/deposits`  
- `GET/POST usd-vault/withdrawals`  
- `POST usd-vault/transactions/{id}/reverse`  
- `GET usd-vault/export`

All behind new permissions (do not overload broken `vault.*` / `vaults.*` mismatch).

### 8.4 Permissions (proposal)

- `usd_vault.index`  
- `usd_vault.deposit`  
- `usd_vault.withdraw`  
- `usd_vault.reverse`  
- `usd_vault.export`  
- `usd_vault.manage_opening`  

Register in permissions form group «خزنة الدولار».

---

## 9. Concurrency, idempotency, and audit safeguards

| Risk | Safeguard |
|------|-----------|
| Concurrent withdrawals | `USDVault::lockForUpdate()` then balance check then ledger+balance |
| Insufficient funds | Reject with clear Arabic/English message; no negative balance |
| Duplicate submit | Unique `idempotency_key` / form token; redirect PRG |
| Unauthorized edit | Permission middleware on every mutating route |
| Posted transaction edit | Disallow amount/direction edit; allow reverse |
| Deletion | Soft-forbid delete of posted rows; reverse only |
| Audit | `created_by`, timestamps, optional activity log |
| External repeats | Idempotency on reference+category+amount+date hash |
| Float bugs | `DECIMAL(14,2)` + decimal math helper |

Align with Phase 5 patterns in `CarPayingController` / `BankTransactionService` / `tests/Feature/Phase5FinancialConcurrencyTest.php` — **without modifying those flows**.

---

## 10. Isolation from existing EGP and financial workflows

### Explicitly unchanged in USD vault v1

- Existing `VaultController` / `VaultTransactionController` behavior  
- All EGP vault mutators listed in §2.2  
- Company AR dual ledger and statement math  
- Invoice payment allocation / section payments  
- Agent/superagent custody money transfers  
- Supplier and car paying vault deductions  
- Phase 5 concurrency locks on existing paths  
- Bank EGP balance rules (except optional **separate** bugfix for USD check clearance)

### Optional future decision (not assumed)

Automatic sync: “when `invoice_payments.currency=USD` and payment_type=cash, post USD vault deposit.”  
Requires owner approval, double-counting rules, and mapping of payment source → vault. **Out of scope for v1.**

---

## 11. Testing strategy

| Layer | Cases |
|-------|--------|
| Unit | Balance math; reverse excludes original; category direction validation; currency forced USD |
| Feature | Deposit/withdraw happy path; insufficient balance; concurrent withdraw (two requests); idempotent double POST; permission denied |
| Isolation | Assert existing `Vault::first()->amount` unchanged after USD vault ops (when EGP vault tables exist in test DB) |
| Regression | Re-run Phase 5 financial concurrency suite; dual-currency invoice tests — expect **no** expectation changes |
| Manual UAT | UI labels USD-only; statement export; opening balance; reverse flow |

Test DB may create USD vault tables in sqlite memory for tests only — **not** via operational migrate.

---

## 12. Database provisioning and deployment constraints

Per permanent database policy:

1. **Do not** run `php artisan migrate` / `migrate:fresh` / rollback against operational DB.  
2. New tables require **owner-approved reviewable SQL** under `docs/sql/` (information_schema-guarded `CREATE TABLE IF NOT EXISTS` style), applied manually after review.  
3. **Do not** recreate missing legacy `vaults` / `vault_transactions` as part of USD vault work unless the owner explicitly authorizes a separate EGP vault provisioning project.  
4. USD vault module UI should fail clearly if USD tables are absent (`LEGACY`/provisioning message), not silently write to EGP vault.  
5. No business INSERT/UPDATE/DELETE against operational DB during modernization/testing outside approved app features.  
6. No Laravel index migrations for this feature unless later authorized; any indexes ship as reviewable SQL with evidence.

---

## 13. Ordered implementation tasks (future approved run only)

1. Owner approves this plan + table names + v1 category list + “manual only” sync decision.  
2. Draft reviewable SQL: `docs/sql/YYYY-MM-DD-usd-vault.sql` (+ roll into branch SQL inventory). **Do not execute until approved.**  
3. Models + service `UsdVaultLedgerService` (atomic post/reverse/balance).  
4. Permissions + routes + middleware.  
5. Admin controllers + Blade (overview, history, deposit, withdraw, reverse).  
6. Export statement.  
7. Feature/unit tests (isolation + concurrency).  
8. Sidebar entry + lang strings.  
9. Deploy: apply SQL on target env → deploy code → smoke UAT.  
10. Optional backlog (separate tickets only): wire-from invoice USD cash; fix `markCheckAsPaid` USD bank mix; EGP vault permission typos (`vault.*` vs `vaults.*`).  
11. **Out of scope for this feature:** Legacy `agient_id` / `agent_id` column drift — tracked in [legacy-agent-id-column-drift-task-list.md](legacy-agent-id-column-drift-task-list.md). Do not rename or depend on either column in the USD vault module.

---

## 14. Risks, assumptions, and unresolved business decisions

### Risks

| Risk | Mitigation |
|------|------------|
| EGP vault tables missing in some envs | Keep USD vault fully separate; do not depend on EGP vault existence |
| Users double-enter cash in EGP vault and USD vault | Training + v1 manual-only; clear UI warnings |
| Permission name chaos (`vault` vs `vaults`) | New `usd_vault.*` names only |
| Float/legacy amount strings | Force DECIMAL + shared money helper |
| Pressure to “just add currency column to vaults” | Reject for isolation; owner already chose standalone |
| Legacy `agient_id` vs `agent_id` drift on EGP vault rows | Document only here; remediate via [legacy-agent-id-column-drift-task-list.md](legacy-agent-id-column-drift-task-list.md). USD vault uses independent columns only |

### Assumptions

- One primary USD cash vault is enough for v1.  
- No FX conversion in v1.  
- Manual posting covers collections/settlements/expenses initially.

### Unresolved owner decisions

1. Exact table names (`usd_vaults` vs `dollar_vaults` vs …).  
2. Whether bank-received USD invoice payments should **ever** auto-post to USD vault (v1 = no).  
3. Whether agent/superagent can hold USD custody wallets (out of scope unless requested).  
4. Who may set opening balance / reverse.  
5. Whether to provision missing EGP `vaults` tables in environments that lack them (separate project).  
6. Multi-vault (multiple USD safes) vs single row.

---

## 15. Exact files expected to change during implementation

*(Future run only — not modified in this audit.)*

**Likely new**

- `app/Models/UsdVault.php`  
- `app/Models/UsdVaultTransaction.php`  
- `app/Services/UsdVaultLedgerService.php`  
- `app/Http/Controllers/Admin/UsdVaultController.php` (and/or transaction controller)  
- `app/Http/Requests/Admin/UsdVault*Request.php`  
- `app/Exports/UsdVaultStatementExport.php`  
- `resources/views/admin/usd-vault/**`  
- `tests/Feature/UsdVault*Test.php`  
- `docs/sql/YYYY-MM-DD-usd-vault.sql`  
- Lang keys in `resources/lang/{ar,en}/admin.php`  
- Permission form group entry  

**Likely touch (minimal wiring)**

- `routes/web.php`  
- `resources/views/layouts/includes/sidebar.blade.php`  
- `resources/views/admin/permissions/form.blade.php`  
- `docs/sql/devlop_test-branch-all-database-changes.sql` (inventory rollup only)

**Must not change for v1 isolation**

- Existing vault/bank/custody/invoice payment calculation paths listed in §10  
- Phase 5 financial controllers’ business rules  
- `CompanyCurrencyLedger` / invoice AR math  

---

## 16. Acceptance criteria

### Audit complete when (this document)

- [x] Existing financial workflows traced through real implementations.  
- [x] Standalone USD module boundary unambiguous.  
- [x] Transaction and balance rules internally consistent.  
- [x] Legacy mismatches documented (`vaults` / `vault_transactions` NOT MATCHED).  
- [x] Permanent database policy preserved (no migrate / no DDL executed).  
- [x] Future implementation plan ordered and evidence-based.  
- [x] No application code or operational data changed in this run.

### Future implementation acceptance (after owner approval)

- USD vault balance and ledger operate independently of EGP vault.  
- Deposit/withdraw/reverse work under concurrency without negative balance.  
- UI shows USD-only labels and permissions.  
- Existing EGP vault / AR / bank tests remain green without expectation rewrites.  
- Schema delivered as reviewable SQL and applied only after owner approval.

---

## Verified blockers (stop until owner decides)

1. **LEGACY MISMATCH:** `vaults` / `vault_transactions` absent on fingerprint of imported DB — EGP vault code may already be broken in that environment; USD vault must not depend on fixing that first.  
2. **Provisioning:** new USD tables need owner-approved SQL — not artisan migrate.  
3. **Product decisions:** auto-sync vs manual; naming; opening-balance role.  

---

## Proposed next steps (wait for owner)

1. Review and approve this plan (or amend categories / naming).  
2. Approve reviewable SQL draft for `usd_vaults` + `usd_vault_transactions`.  
3. Authorize a dedicated implementation run (explicit owner instruction).  
4. Keep EGP vault repair / check-clearance USD bank bug as **separate** tickets.

---

*End of Phase 0 audit. No implementation performed.*

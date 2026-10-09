# Phase 5: Financial Concurrency & Audit Evidence

## 1. Scope & Audited Write Paths

| Write Path | Controller / Service | Primary Table(s) | Transaction Guard | Concurrency Locking | Idempotency / Deduplication |
|---|---|---|---|---|---|
| **Agent Expenses** | `StageExpenseService` | `agent_expenses`, `agents`, `booking_container_stages` | `DB::transaction()` | `lockForUpdate()` on Container, Agent, Stage | `request_key` + `request_fingerprint` + `version` check |
| **Stage Transitions** | `ContainerStageService` | `booking_containers`, `booking_container_agents` | `DB::transaction()` | `lockForUpdate()` on Container, Stage | Version bump on approval, stage receipt closure check |
| **Receipt Management** | `ReceiptController` | `receipts`, `agents`, `agent_expenses`, `booking_services` | `DB::transaction()` | `lockForUpdate()` on Receipt, Agent | Deletion refund cleanup on linked `agent_expenses` |
| **Car Payments** | `CarPayingController` | `payingcars`, `vaults`, `vault_transactions`, `delivery_policies` | `DB::transaction()` | `lockForUpdate()` on Vault, DeliveryPolicy, Payingcar | Calculation bounds verification, atomic credit/debit |
| **Bank Transactions** | `BankTransactionController` | `banks`, `vaults`, `vault_transactions`, `bank_trnsactions`, `companies` | `DB::transaction()` | `lockForUpdate()` on source/target Banks, Vault, Company | Balance check, audit record creation, atomic ledger sync |

---

## 2. Hardened Vulnerabilities & Fixes

1. **`ReceiptController::store()` & `destroy()`**:
   - *Previous state*: When deducting agent wallet on representative receipt creation, `$agent->save()` occurred without `lockForUpdate()`. When deleting receipts linked to agent expenses, agent wallet was not refunded, causing financial leakage.
   - *Hardening*: Added `Agent::lockForUpdate()` during wallet deduction. Added automatic refund on `destroy()` for any linked `AgentExpense`.

2. **`CarPayingController::store()`, `update()`, `destroy()`**:
   - *Previous state*: Payment operations lacked `DB::transaction()` and executed without row-level locks on `Vault` and `DeliveryPolicy`, risking race condition overpayments and vault drift.
   - *Hardening*: Wrapped all operations in `DB::transaction()`. Applied `Vault::lockForUpdate()` and `DeliveryPolicy::lockForUpdate()`. Validated bounds inside the lock.

3. **`BankTransactionController::store()`, `update()`, `destroy()`**:
   - *Previous state*: Case 2 bank transfer omitted `$bank->save()` (leaving source bank un-debited in database), omitted `BankTrnsaction` audit record creation, and lacked overdraft prevention.
   - *Hardening*: Added `lockForUpdate()` on all participating banks, verified source bank balance, persisted both balances, and generated audit trail records in `bank_trnsactions`.

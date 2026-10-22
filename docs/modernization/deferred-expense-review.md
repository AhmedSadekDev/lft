# مراجعة المصاريف والخصم المؤجّل

**Date:** 2026-10-22  
**Scope:** App receipts via `StageExpenseService` only. Dashboard booking-service / receipt paths stay immediate debit.

## Behavior

| Event | Wallet | `admin_approval` |
|-------|--------|------------------|
| Agent creates / edits / deletes pending receipt | No change (hold against spendable) | 0 |
| Dashboard `markReviewed` | Debit once; set `wallet_debited_at`, `reviewed_at`, `user_id` | 1 |
| Dashboard booking-service / receipt create | Immediate debit (unchanged) | 1 + timestamps |

Spendable balance = `agents.wallet − Σ(pending holds)` where hold = null `wallet_debited_at`, no `delivery_policy_id`, no `booking_service_id`.

## Surfaces

- `POST /agent_expenses/{agent}/review` — bulk confirm + debit (auditor = `auth()->id()`)
- `POST /general_expenses/review` — checkbox confirm from general expenses report
- `POST /api/agent/review_expenses` — **403** (dashboard-only)
- `GET /api/agent/fetch_financial_custody` — additive keys: `under_review_amount`, `confirmed_spent_today`; `remaining_financial_custody` is spendable

## Schema

Apply manually: [`docs/sql/2026-10-22-agent-expenses-deferred-review.sql`](../sql/2026-10-22-agent-expenses-deferred-review.sql)

Backfill stamps `wallet_debited_at` on already-approved / booking-service rows so review never double-charges.

## Note

Stage `closeReceipts` still hides receipts from the agent screen without debiting. Pending holds remain until dashboard review (by agent screen or general-expenses checkboxes with explicit ids).

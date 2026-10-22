# Reefer Outbound — Land BOL & 11-Stage Tracking v1 — Implementation Report

**Date:** 2026-10-22  
**Branch context:** `devlop_test`  
**Status:** Implementation complete in application code; **SQL not applied** to operational `leader` DB (owner review required).

---

## 1. Scope delivered

| Item | Result |
|------|--------|
| EGP custody | Unchanged: `MoneyTransfer` type 3 + `agents.wallet` debit on create; update uses **diff only** (no redebit when values unchanged) |
| USD custody | `delivery_policies.custody_usd` — **record only**; never wallet debit / settlement / driver dues |
| Workflow scope | `ShipmentWorkflow::REEFER` / `type_of_action = 4` only |
| Land BOL print | Admin printable Blade + links from booking show & container policies |
| 11-stage timeline | Agent list/detail/save + optional image/PDF docs |
| DB | Reviewable SQL only; **no migrate / no DDL executed** |

---

## 2. Authorization decisions (verified)

Ownership for agent access matches existing delivery-policy convention:

1. `money_transfers.type = 3` with `transferer_id = agent.id` and `transferer_type` = Agent.
2. Linked booking via `delivery_policy_containers` → `booking_containers` → `bookings` must be reefer (`type_of_action = 4` / `ShipmentWorkflow::of()`).

Cross-agent access to trip list/details/stage save/document upload is denied (tested).

---

## 3. API contracts (agent)

| Method | Path | Notes |
|--------|------|--------|
| GET | `/api/agent/fetch_reefer_trip_list` | Paginated; each row: `id`, `driver_name`, `destination` (loading city title only) |
| POST | `/api/agent/reefer_trip_details` | Body: `delivery_policy_id` → timeline 1–11 + custody fields |
| POST | `/api/agent/save_reefer_trip_stage` | `delivery_policy_id`, `stage` (1–11), `occurred_at` (required unless stage 11), `notes` |
| POST | `/api/agent/upload_reefer_trip_document` | `delivery_policy_id`, optional `stage`, `document` (png/jpg/jpeg/pdf) |

Envelope matches existing agent style: `status`, `errNum`, `message`, `data`, and `pagination` on the list.

### Create / update delivery policy

- **Create (reefer):** `custody_usd` **required**; stored on policy; EGP path unchanged.
- **Create (non-reefer):** `custody_usd` **rejected** if present (`custody_usd_reefer_only`).
- **Update (reefer):** `custody_usd` may change without wallet impact; EGP wallet adjusts only by `actualDiff`.
- **Update (non-reefer):** sending `custody_usd` returns 422.
- **Legacy reefer policies** without `custody_usd`: update may set it; create of new reefer requires it. Existing rows remain nullable until edited.

Settlement (`settle_delivery_policy`) does not read or write `custody_usd` (tested).

---

## 4. Admin UI

- Route: `GET bookings/delivery-policies/{deliveryPolicy}/land-bol` → `bookings.delivery_policy_land_bol`
- View: `resources/views/admin/bookings/land_bol_print.blade.php` (browser print)
- Links: booking show delivery-policies table; `container_policies` actions column

---

## 5. SQL review notes

**File:** [`docs/sql/2026-10-22-reefer-delivery-policy-tracking.sql`](../sql/2026-10-22-reefer-delivery-policy-tracking.sql)

- Adds nullable `delivery_policies.custody_usd` `DECIMAL(14,2)` with comment that it is not a wallet debit.
- Creates `reefer_trip_legs` with unique `(delivery_policy_id, stage)`.
- Idempotent via `information_schema` guards; includes preflight SELECTs.
- Also rolled into [`docs/sql/devlop_test-branch-all-database-changes.sql`](../sql/devlop_test-branch-all-database-changes.sql) as **A10**.

**Not executed** against `leader` in this run. Applying SQL is a **separate owner decision**.

Until SQL is applied, create/update paths that touch `custody_usd` or trip legs will fail at runtime on environments missing the column/table.

---

## 6. Modified / new files

**New**

- `app/Support/ReeferTripStages.php`
- `app/Models/ReeferTripLeg.php`
- `app/Services/ReeferTripTrackingService.php`
- `app/Http/Controllers/Api/Agent/ReeferTripController.php`
- `app/Http/Resources/Api/Agent/ReeferTripListResource.php`
- `resources/views/admin/bookings/land_bol_print.blade.php`
- `docs/sql/2026-10-22-reefer-delivery-policy-tracking.sql`
- `tests/Feature/ReeferDeliveryPolicyTrackingTest.php`
- `docs/modernization/reefer-delivery-policy-tracking-v1-report.md` (this file)

**Updated**

- `app/Models/DeliveryPolicy.php` — `custody_usd` via guarded; `reeferTripLegs()`, `images()`
- `app/Http/Controllers/Api/Agent/DeliveryPolicyController.php` — create/update USD record-only
- `app/Http/Requests/Api/Agent/DeliveryPolicyRequest.php` — reefer-gated `custody_usd`
- `app/Http/Resources/Api/Agent/DeliveryPolicyResource.php`, `DeliveryPolicyDetailsResource.php`
- `app/Http/Controllers/Admin/BookingController.php` — `print_land_bol`
- `routes/agent.php`, `routes/web.php`
- `resources/views/admin/bookings/container_policies.blade.php`, `show.blade.php`
- `resources/lang/{ar,en}/main.php`, `admin.php`
- `docs/sql/devlop_test-branch-all-database-changes.sql`

**Explicitly unchanged**

- `done_loading` / `done_unloading` / Phase 5 settle concurrency
- Agent USD wallet (none)
- `booking_movements` stub (not reused)

---

## 7. Before / after behavior

| Scenario | Before | After |
|----------|--------|--------|
| Reefer create policy | EGP wallet debit only | Same EGP debit + store `custody_usd` |
| Update same EGP amounts | Wallet diff 0 | Still 0; USD field can update alone |
| Settle | EGP driver dues path | Same; `custody_usd` untouched |
| Agent trip tracking | N/A | List driver+destination; 11 stages |
| Admin print land BOL | N/A | Printable page |

---

## 8. Test results

### Focused suite (this feature)

```
php vendor/bin/phpunit tests/Feature/ReeferDeliveryPolicyTrackingTest.php
OK (9 tests, 88 assertions)
```

Covered: EGP debit, USD record-only, update/retry no double debit, settle invariance, stage order + stage 11 notes-only, cross-agent denial, list shape, document auth.

### Full suite (2026-10-22)

```
Tests: 249, Assertions: 1589, Errors: 2, Failures: 2
```

**Failures/errors observed (pre-existing / unrelated baseline — not introduced by this feature):**

| Test | Issue |
|------|--------|
| `Phase5FinancialConcurrencyTest::test_receipt_agent_wallet_deduction_and_deletion_safety` | In-memory schema missing `booking_services.currency` |
| `Phase6MobilePaginationTest::test_desktop_orders_pagination_and_eager_loading` | In-memory schema missing `invoices.currency` |
| `Phase6MobilePaginationTest::test_client_portal_company_bookings_pagination` | JWT token parse / customer session |
| `ExampleTest::test_the_homepage_redirects_to_the_main_route` | Expects redirect, got 200 |

Reefer feature tests: **all green**. Historical failures were **not** rewritten to pass.

---

## 9. Unverified / environment limits

- Live MySQL `leader` column/table presence for `custody_usd` / `reefer_trip_legs`: **UNVERIFIED — SQL not applied**.
- Mobile app consumption of new endpoints: not in this run.
- `CitiesAndRegions.title` Spatie translations: list uses `loading.title`; production may store JSON locales — same as existing admin booking show.
- Document URLs reuse existing `Image` accessor (`asset('/storage/...')`) — same exposure model as current delivery-policy images; no new public ACL layer.

---

## 10. Owner next steps

1. Review this report + SQL file.  
2. Approve and **manually apply** `docs/sql/2026-10-22-reefer-delivery-policy-tracking.sql` on target environments.  
3. Smoke-test agent create reefer policy with `custody_usd`, trip stages, and admin print.  
4. Do not treat SQL application as approved by this code merge alone.

---

*End of v1 implementation report. Stopped for owner review.*

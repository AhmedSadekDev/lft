# USD + Follow-ups + Reefer module — implementation notes

**Date:** 2026-11-22  
**SQL (reviewable, not migrate):** [`docs/sql/2026-11-22-usd-followup-reefer-module.sql`](../sql/2026-11-22-usd-followup-reefer-module.sql)

## Apply before use

1. Run the SQL file on the operational DB (owner-approved).
2. `php artisan permission:cache-reset`
3. Confirm Admin role received: `usd_vault.*`, `follow_ups.*`, `reefer_trips.*`

## Phase 1 — USD

| Piece | Path |
|-------|------|
| Service | `app/Services/UsdVaultService.php` |
| Admin UI | `/dashboard/usd-vault` |
| Agent column | `agents.wallet_usd` |
| Bank column | `banks.amount_usd` |
| Policy edit | Admin delivery-policy update debits/credits `wallet_usd` when `custody_usd` changes |

EGP vault / EGP agent wallet paths are unchanged.

## Phase 2 — Follow-ups

| Piece | Path |
|-------|------|
| Table | `container_follow_ups` |
| UI | `/dashboard/follow-ups` |
| Booking show | Follow-up button per delivery policy |
| Public track | `ContainerResource` + `Api\BookingController` return `follow_ups` + `documents` |

Office staff record status text + optional occurred_at (defaults to now). Apps untouched.

## Phase 3 — Reefer module

| Piece | Path |
|-------|------|
| Tables | `reefer_shipments`, `reefer_trips` |
| UI | `/dashboard/reefer-trips` |
| Dual custody | EGP + USD from selected agent wallets on create/update |
| Timeline | Hi-Freight columns + follow-up log |

Existing `type_of_action = 4` / agent reefer APIs remain for compatibility; new dashboard cycle is the Excel replacement path.

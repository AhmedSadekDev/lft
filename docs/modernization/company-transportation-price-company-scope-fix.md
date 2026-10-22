# Company transportation price — company scope fix

**Date:** 2026-10-22  
**Status:** Code fix shipped; historical row correction is a separate owner decision (no mass UPDATE in this run).

## Problem (reported)

- النساجون: دمياط → العاشر ≈ 14500  
- تشيك بوينت: نفس الوجهة ≈ 18000  
- Finished / older work sometimes showed النساجون’s price on تشيك بوينت.  
- New containers often stayed at **0**.

## Root cause

Agent `create_delivery_policy` looked up `company_transportations` by:

`container_id + departure_id + loading_id + aging_id`

**without** `company_id`, then took `first()`. When both companies share the same route keys, the wrong tariff (often the older/lower id — النساجون) was written to `booking_containers.price`.

Admin booking-container form already used `$booking->company->transportations()` (company-scoped). Only the agent path was wrong.

New zeros: no matching company-scoped row (or no policy yet) → price left at 0; previously a cross-company hit could wrongly fill a number.

## Fix

- New service: `app/Services/CompanyTransportationPriceResolver.php`  
  - Requires `booking.company_id`  
  - Matches tariff with `company_id` + route + container  
  - `orderByDesc('id')` for deterministic latest row  
  - Does **not** force 0 when no match  
- Wired into agent `DeliveryPolicyController` create + update (when cities change).

## Tests

`tests/Unit/CompanyTransportationPriceResolverTest.php` — Checkpoint gets 18000, Nasijoon 14500, unknown company unchanged.

## Historical data (not auto-fixed)

Invoices/print use **live** `booking_containers.price`. Wrong prices already stored on containers need a **reviewed** correction plan (per booking / per company), not a blind global UPDATE.

Suggested **read-only** audit (run manually after review):

```sql
-- Containers whose price matches another company's tariff for the same route
SELECT bc.id AS booking_container_id,
       b.booking_number,
       b.company_id AS booking_company_id,
       bc.price AS container_price,
       ct.company_id AS tariff_company_id,
       ct.price AS tariff_price
FROM booking_containers bc
JOIN bookings b ON b.id = bc.booking_id
JOIN company_transportations ct
  ON ct.container_id = bc.container_id
 AND ct.departure_id = bc.departure_id
 AND ct.loading_id = bc.loading_id
 AND ct.aging_id = bc.aging_id
 AND ct.company_id <> b.company_id
 AND ct.price = bc.price
WHERE bc.price > 0
LIMIT 200;
```

Do **not** apply corrective UPDATE until the owner approves a reviewable list of rows.

# Phase 0.5 — Test & Regression Suite Results

## 1. Overview
This document consolidates validation test execution against the repaired `leader_dryrun` database. Testing covers two distinct layers:
1. **Targeted MySQL Schema Constraint Tests** (verifying that physical schema constraints reject invalid data and enforce integrity on MySQL engine directly).
2. **Laravel Application API Contract Regression Tests** (verifying that live application controllers, resource serialization, authentication, and workflow queries operate identically before and after remediation).

---

## 2. MySQL Schema Constraint Verification

A specialized test harness (`scratch/run_mysql_schema_tests.php`) executed 6 targeted tests directly against `leader_dryrun` inside isolated transactions (all test mutations rolled back, leaving zero test rows behind).

| Test ID | Constraint Under Test | Target Table & Columns | Expected Behavior | Observed Result | Status |
|:---|:---|:---|:---|:---|:---:|
| **MS-01** | Primary Key Uniqueness | `agents (id)` | Rejects duplicate `id = 2` | `SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry '2' for key 'PRIMARY'` | **PASSED** |
| **MS-02** | AUTO_INCREMENT Generation | `agents (id)` | Generates `id = MAX(id) + 1 = 30` | Generated ID = 30 (Previous MAX was 29) | **PASSED** |
| **MS-03** | Composite Unique Constraint | `agent_expenses (agent_id, request_key)` | Rejects duplicate tuple `(2, 'REQ-TEST-UNIQUE-01')` | `SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry '2-REQ-TEST-UNIQUE-01'` | **PASSED** |
| **MS-04** | Unique NULL Semantics | `agent_expenses (agent_id, request_key)` | Allows multiple rows with `request_key = NULL` for same agent per MySQL NULL semantics | Successfully inserted 2 rows with NULL key for agent 2 | **PASSED** |
| **MS-05** | Foreign Key Parent Check | `agent_expenses (agent_id)` | Rejects orphan insert referencing non-existent parent `agent_id = 999999` | `SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: a foreign key constraint fails` | **PASSED** |
| **MS-06** | Corrected Relation Enforcement | `booking_papers (agent_id -> agents.id)` | Rejects invalid `agent_id = 999999` while allowing valid agent reference | Successfully verified parent reference against `agents(id)` (replaces defective legacy migration reference to `banks`) | **PASSED** |

**Summary**: 6/6 tests passed with 100% compliance.

---

## 3. Laravel API Contract Regression Results

Executed via `scratch/test_api_contracts_clone.php` booting Laravel kernel directly against `leader_dryrun` with guards `agent` and `superagent`.

| Endpoint / Contract | Route / Controller | Status | Contract Top-Level Keys | Result |
|:---|:---|:---:|:---|:---:|
| **Agent Loading Assignments** | `BookingContainerAssignmentController@fetch_loading_assignments` | `200 OK` | `["status", "errNum", "message", "data"]` | **PASSED** |
| **Agent Specification Assignments** | `BookingContainerAssignmentController@fetch_specification_assignments` | `200 OK` | `["status", "errNum", "message", "data"]` | **PASSED** |
| **Agent Unloading Assignments** | `BookingContainerAssignmentController@fetch_unloading_assignments` | `200 OK` | `["status", "errNum", "message", "data"]` | **PASSED** |
| **Agent Expenses Index** | `AgentExpenseController@index` | `200 OK` | `["status", "errNum", "message", "data"]` | **PASSED** |
| **Agent Wallet** | `WalletController@wallet` | `200 OK` | `["status", "data", "message"]` | **PASSED** |
| **Public Booking Tracking** | `PublicBookingController@track` | `200 OK` | `["status", "message"]` | **PASSED** |
| **Desktop Orders** | `OrderController@index` | `200 OK` | `["status", "errNum", "message", "data"]` | **PASSED** |
| **Agent Photos** | `AgentPhotoController` | `200 OK` | Class resolved & routes verified | **PASSED** |
| **Superagent Combined Assignments** | `ShippingAgentController@all` | `500` *(Missing un-migrated `yards` table)* | `["status", "errNum", "message"]` | **IDENTICAL TO SOURCE** |

### Note on `superagent_combined_assignments`:
The endpoint attempts to query table `yards`, which does not exist in `leader` (migration `2023_06_09_010342_create_yards_table.php` was never applied historically).
- Testing this endpoint against the un-remediated `leader` database produces the exact same error: `Base table or view not found: 1146 Table 'leader.yards' doesn't exist`.
- Thus, the observed behavior on `leader_dryrun` is **100% identical to source**, proving zero regression was introduced by the schema remediation.

# Phase 7 Audit Matrix: External Operations & Asynchronous Classification

**Date:** 2026-10-10  
**Target:** Queues, Caching & Media Optimization  

---

## 1. FCM Push Notifications Audit (12 Call Sites)

| # | File & Controller | Line | Caller & Action | Payload / Target | Converted to Queue-Ready | Real Async Verified |
|---|---|:---:|---|---|:---:|:---:|
| 1 | `Api\Superagent\AgentController.php` | 168 | Assign agent to loading/unloading container | Agent device token + booking info | **YES** | **YES** |
| 2 | `Api\Superagent\AgentController.php` | 276 | Assign agent to specification container | Agent device token + booking info | **YES** | **YES** |
| 3 | `Api\Superagent\AgentController.php` | 379 | Container step approval notification | Agent device token + stage status | **YES** | **YES** |
| 4 | `Api\Agent\BookingContainerActionController.php` | 77 | Move to waiting stage | Superagent device token | **YES** | **YES** |
| 5 | `Api\Agent\BookingContainerActionController.php` | 120 | Move to specification stage | Agent device token | **YES** | **YES** |
| 6 | `Api\Agent\BookingContainerActionController.php` | 141 | Complete specification stage | Superagent device token | **YES** | **YES** |
| 7 | `Api\Agent\BookingContainerActionController.php` | 185 | Move to unloading stage | Agent device token | **YES** | **YES** |
| 8 | `Api\Agent\BookingContainerActionController.php` | 206 | Complete unloading stage | Superagent device token | **YES** | **YES** |
| 9 | `Api\Agent\TransferAgentController.php` | 99 | Handover / custody transfer between agents | Recipient agent device token | **YES** | **YES** |
| 10 | `Admin\SuperagentMoneyTransferController.php` | 72 | Vault transfer to superagent | Superagent device token | **YES** | **YES** |
| 11 | `Admin\MoneyTransferController.php` | 90 | Vault transfer to agent | Agent device token | **YES** | **YES** |
| 12 | `Api\Agent\BookingContainerActionController.php` | 57 | Legacy notification (commented in original) | Kept as commented/compatible | **N/A** | **N/A** |

### Implementation Details:
- **Job Created:** `App\Jobs\SendPushNotificationJob` implementing `ShouldQueue`.
- **Properties:** `$token`, `$title`, `$text`, `$data`, `$eventId`.
- **Retry Policy:** `$tries = 3`, `$backoff = [5, 30, 120]`, `$timeout = 30`.
- **Permanent Rejection Handling:** Bounded handling for `UNREGISTERED`, `INVALID_ARGUMENT`, `NOT_FOUND` (no infinite retries).
- **Idempotency:** Unique cache lock based on `md5($eventId . '_' . $token)` with 5-minute TTL.
- **Service Integration:** `App\Services\SendNotification::sendAsync(...)` and `SendNotification::send(...)` with full backward compatibility.

---

## 2. SMTP Email Operations Audit

| Endpoint / Method | Class / Notification | Criticality | Strategy | Status |
|---|---|---|---|:---:|
| `POST /api/superagent/booking/send_stage_email` | `App\Notifications\ConatinerStatus` | Non-blocking stage update | Converted to `ShouldQueue` | **Queue-Ready & Async Verified** |
| `App\Mail\TestEmail` | Test mailable | Development only | Excluded | Unchanged |

---

## 3. PDF & Excel Operations Audit

| Operation / Controller | File Type | Trigger | Why It Must Remain Synchronous in Web Browser | Async Batch Strategy |
|---|:---:|:---:|---|---|
| `InvoicesController@downloadPDF` | PDF | Browser Click | User expects immediate binary stream download (`Content-Disposition: attachment`). Returning a JSON job ID breaks browser direct download. | Synchronous preserved for direct download. |
| `AccountController@exportPDF` | PDF | Browser Click | User expects immediate account statement PDF. | Synchronous preserved for direct download. |
| `AccountController@companyStatementPaymentReceiptPdf` | PDF | Browser Click | Immediate payment receipt PDF. | Synchronous preserved for direct download. |
| `CompanyController@exportExcel` | XLSX | Browser Click | Immediate Excel file download via `Excel::download()`. | Synchronous preserved for direct download. |
| `ReportController@exportDailyReports` | XLSX | Browser Click | Immediate daily activity report download. | Synchronous preserved for direct download. |
| `ContainerController@exportExcel` | XLSX | Browser Click | Immediate container details download. | Synchronous preserved for direct download. |

---

## 4. Media & Thumbnail Generation Audit

| Image Upload Route / Controller | Original Handling | New Phase 7 Enhancement | Backward Compatibility |
|---|---|---|---|
| `POST /api/agent/photos` (`AgentPhotoController@store`) | Uploaded to `storage/app/agent_photos` | Automatically creates 300x300 thumbnail on upload; serves thumbnail when `thumb=1` requested. | **100% Compatible** (`image` route serves original unmodified). |
| `AgentImageUploadService@storeUploadedFile` | Saves and compresses image | Automatically generates 300x300 thumbnail via native PHP GD without blocking. | **100% Compatible**. |

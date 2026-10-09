# Phase 5: ETA & Queue Reliability Verification

## 1. Egyptian Tax Authority (ETA) E-Invoicing Reliability

### Issue Identified
- In `App\Services\EInvoiceService`, line 66 previously attempted `catch (GuzzleHttp\Exception\ClientException $e)` inside namespace `App\Services`.
- Because `GuzzleHttp\Exception\ClientException` was not imported via a `use` statement or prefixed with a leading backslash `\`, any 4xx response from ETA (such as duplicate submissions or authentication timeouts) threw an unhandled class resolution error rather than gracefully capturing the ETA response error message.

### Resolution & Hardening
- Imported `GuzzleHttp\Exception\ClientException`, `GuzzleHttp\Exception\RequestException`, and `GuzzleHttp\Exception\GuzzleException`.
- Implemented robust multi-tiered exception handling (`ClientException` -> `GuzzleException` -> `\Throwable`).
- Handled ETA duplicate submission errors ("identical to a previous payload" / "Try to submit payload after ...") with user-friendly localized retry notices.

---

## 2. Background Queue Execution & Retry Safety

### `SubmitInvoiceJob` Hardening
- Added standard queue configuration:
  - `$tries = 3;`
  - `$timeout = 120;`
  - `$backoff = [30, 60, 120];`
- **Idempotency Guard**: Prior to querying ETA or dispatching document submissions, verified whether `is_submitted === 1` and `invoice_status === 'Valid'` on the target booking. If already valid and submitted, redundant external API calls and status overwrites are safely bypassed.
- **Error Capturing**: Wrapped job execution in try-catch to record descriptive failure details in `invoice_errors` on `bookings` table.

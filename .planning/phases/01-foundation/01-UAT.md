---
status: complete
phase: 01-foundation
source: 01-01-SUMMARY.md, 01-02-SUMMARY.md
started: 2026-03-28T08:00:00Z
updated: 2026-03-28T09:00:00Z
---

## Current Test
<!-- OVERWRITE each test - shows where we are -->

[testing complete]

## Tests

### 1. Cold Start Smoke Test
expected: Kill any running server. Clear ephemeral state. Run from scratch: `php artisan migrate:fresh --seed && php artisan serve`. Server boots without errors, migration and AdminSeeder complete (admin@banchimbocau.vn seeded), and a basic request such as POST /api/v1/admin/login returns a JSON response (not an HTML error page).
result: pass

### 2. Admin Login — Valid Credentials
expected: POST /api/v1/admin/login with {"email":"admin@banchimbocau.vn","password":"[seeded password]"} returns HTTP 200 with body like {"success":true,"data":{"token":"...","expires_at":"..."}}.
result: issue
reported: "{"success": true, "token": "2|lspns8vBWQQdwj5tKXAlhoMH4HQ4Jhc1egG7IwkD1f6a359b"} — token is top-level, not nested under data, and expires_at is missing"
severity: major

### 3. Token Has Non-Null expires_at
expected: The token object returned from login has a non-null, non-empty expires_at field (e.g. "2026-04-27T..."). Confirms SANCTUM_TOKEN_EXPIRY is working.
result: skipped
reason: confirmed missing from test 2 — expires_at absent from login response entirely

### 4. Admin Login — Wrong Password (Vietnamese Error)
expected: POST /api/v1/admin/login with wrong password returns HTTP 401 with JSON envelope {"success":false,"code":"INVALID_CREDENTIALS","message":"..."} containing a Vietnamese message (not English).
result: pass

### 5. Admin Login — Missing Fields (Vietnamese Validation)
expected: POST /api/v1/admin/login with empty body returns HTTP 422 with {"success":false,"code":"VALIDATION_ERROR","errors":{"email":["Trường email là bắt buộc."],...}} — messages in tiếng Việt.
result: pass

### 6. Admin Logout
expected: POST /api/v1/admin/logout with a valid Bearer token in Authorization header returns HTTP 200 {"success":true}. The token is revoked.
result: pass

### 7. Protected Route After Logout Returns 401
expected: After logging out, reusing the same Bearer token on POST /api/v1/admin/logout (or any auth-protected route) returns HTTP 401 {"success":false,"code":"UNAUTHENTICATED",...} — not an HTML redirect.
result: pass

### 8. JSON Error Envelope on All api/* Routes
expected: Hitting a non-existent api route (e.g. GET /api/v1/does-not-exist) returns HTTP 404 with JSON body {"success":false,"code":"NOT_FOUND","message":"..."} — never HTML.
result: pass

### 9. API Route Versioning (/api/v1/ prefix)
expected: All working endpoints live under /api/v1/. A request to /api/admin/login (no version) returns 404, confirming the prefix is enforced.
result: pass

### 10. Pest Test Suite — All Green
expected: Running `./vendor/bin/pest` (or `php -d memory_limit=512M ./vendor/bin/pest`) exits 0 with 16 tests passed, 0 failures, 3 todos (PlaceOrderAction). No errors.
result: pass

## Summary

total: 10
passed: 8
issues: 1
pending: 0
skipped: 1
blocked: 0

## Gaps

- truth: "POST /api/v1/admin/login returns {\"success\":true,\"data\":{\"token\":\"...\",\"expires_at\":\"...\"}} with token nested under data and expires_at present"
  status: failed
  reason: "User reported: response is {\"success\": true, \"token\": \"...\"} — token is top-level, not nested under data, and expires_at is missing"
  severity: major
  test: 2
  root_cause: ""
  artifacts: []
  missing: []
  debug_session: ""

---
phase: 3
slug: orders-cart-payments
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-29
---

# Phase 3 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Pest 3.x (PHPUnit engine) |
| **Config file** | `phpunit.xml` |
| **Quick run command** | `php artisan test --filter=Order` |
| **Full suite command** | `php artisan test` |
| **Estimated runtime** | ~15 seconds |

---

## Sampling Rate

- **After every task commit:** Run `php artisan test --filter=Order`
- **After every plan wave:** Run `php artisan test`
- **Before `/gsd:verify-work`:** Full suite must be green
- **Max feedback latency:** 15 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 3-01-01 | 01 | 1 | CART-01..04 | feature | `php artisan test --filter=Cart` | ❌ W0 | ⬜ pending |
| 3-02-01 | 02 | 2 | ORDR-01..03 | feature | `php artisan test --filter=PlaceOrder` | ❌ W0 | ⬜ pending |
| 3-02-02 | 02 | 2 | ORDR-04..07 | feature | `php artisan test --filter=OrderState` | ❌ W0 | ⬜ pending |
| 3-03-01 | 03 | 2 | PAYM-01..04 | feature | `php artisan test --filter=Payment` | ❌ W0 | ⬜ pending |
| 3-03-02 | 03 | 2 | DELV-01..02 | feature | `php artisan test --filter=Delivery` | ❌ W0 | ⬜ pending |
| 3-04-01 | 04 | 3 | NOTI-01..02 | feature | `php artisan test --filter=Notification` | ❌ W0 | ⬜ pending |
| 3-04-02 | 04 | 3 | TECH-05 | feature | `php artisan test --filter=Idempotency` | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `tests/Feature/Cart/CartTest.php` — stubs for CART-01..04
- [ ] `tests/Feature/Order/PlaceOrderTest.php` — stubs for ORDR-01..03 (atomic placement)
- [ ] `tests/Feature/Order/OrderStateMachineTest.php` — stubs for ORDR-04..07 (transitions)
- [ ] `tests/Feature/Payment/PaymentTest.php` — stubs for PAYM-01..04
- [ ] `tests/Feature/Order/NewOrderNotificationTest.php` — stubs for NOTI-01..02
- [ ] `tests/Feature/Order/IdempotencyTest.php` — stubs for TECH-05

*Existing infrastructure covers test framework setup (Pest already installed from Phase 1).*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Email content readable in Vietnamese | NOTI-02 | Visual inspection of email template | Send test email via `php artisan tinker`, verify Vietnamese text renders correctly |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 15s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending

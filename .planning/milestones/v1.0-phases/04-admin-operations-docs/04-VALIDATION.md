---
phase: 4
slug: admin-operations-docs
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-29
---

# Phase 4 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Pest 3.x (PHPUnit engine) |
| **Config file** | `phpunit.xml` |
| **Quick run command** | `php artisan test --filter=Admin` |
| **Full suite command** | `php artisan test` |
| **Estimated runtime** | ~15 seconds |

---

## Sampling Rate

- **After every task commit:** Run `php artisan test --filter=Admin`
- **After every plan wave:** Run `php artisan test`
- **Before `/gsd:verify-work`:** Full suite must be green
- **Max feedback latency:** 15 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 04-01-01 | 01 | 1 | ADMN-01 | feature | `php artisan test --filter=DashboardTest` | ❌ W0 | ⬜ pending |
| 04-01-02 | 01 | 1 | ADMN-02 | feature | `php artisan test --filter=OrderListTest` | ❌ W0 | ⬜ pending |
| 04-01-03 | 01 | 1 | ADMN-03 | feature | `php artisan test --filter=OrderSearchTest` | ❌ W0 | ⬜ pending |
| 04-01-04 | 01 | 1 | ADMN-04 | feature | `php artisan test --filter=ConfirmPaymentTest` | ✅ existing | ⬜ pending |
| 04-02-01 | 02 | 2 | ADMN-04 | feature | `php artisan scribe:generate && test -f public/docs/index.html` | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `tests/Feature/Admin/DashboardTest.php` — stubs for ADMN-01 (dashboard order counts)
- [ ] `tests/Feature/Admin/OrderListFilterTest.php` — stubs for ADMN-02, ADMN-03 (filter, search, pagination)

*Existing test infrastructure covers auth and order management patterns.*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Scribe HTML output is browsable and accurate | ADMN-04 | Visual inspection of generated docs | Run `php artisan scribe:generate`, open `public/docs/index.html`, verify all v1 endpoints listed with correct request/response examples |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 15s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending

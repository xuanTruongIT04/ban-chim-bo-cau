---
phase: 2
slug: product-inventory
status: draft
nyquist_compljurisdiction: false
wave_0_complete: false
created: 2026-03-28
---

# Phase 2 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Pest 4 (PHPUnit engine) |
| **Config file** | `phpunit.xml` |
| **Quick run command** | `php artisan test --filter "Phase2"` |
| **Full suite command** | `php artisan test` |
| **Estimated runtime** | ~15 seconds |

---

## Sampling Rate

- **After every task commit:** Run `php artisan test`
- **After every plan wave:** Run `php artisan test` + `php -d memory_limit=512M ./vendor/bin/phpstan analyse`
- **Before `/gsd:verify-work`:** Full suite must be green + PHPStan must pass
- **Max feedback latency:** ~15 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| Category CRUD | 01 | 1 | PROD-03 | Feature | `php artisan test --filter CategoryTest` | ❌ W0 | ⬜ pending |
| Product CRUD | 01 | 2 | PROD-01,02,04 | Feature | `php artisan test --filter ProductTest` | ❌ W0 | ⬜ pending |
| Public product list | 01 | 3 | PROD-05 | Feature | `php artisan test --filter PublicProductTest` | ❌ W0 | ⬜ pending |
| Stock adjustment | 02 | 1 | INVT-01,02,03 | Feature | `php artisan test --filter StockAdjustmentTest` | ❌ W0 | ⬜ pending |
| Stock audit log | 02 | 2 | INVT-04 | Feature | `php artisan test --filter StockAdjustmentHistoryTest` | ❌ W0 | ⬜ pending |
| Image upload (S3) | 03 | 1 | PROD-01 | Feature | `php artisan test --filter ProductImageTest` | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `tests/Feature/Admin/CategoryTest.php` — stubs for PROD-03 (CRUD categories)
- [ ] `tests/Feature/Admin/ProductTest.php` — stubs for PROD-01, PROD-02, PROD-04
- [ ] `tests/Feature/Public/PublicProductTest.php` — stubs for PROD-05
- [ ] `tests/Feature/Admin/StockAdjustmentTest.php` — stubs for INVT-01, INVT-02, INVT-03
- [ ] `tests/Feature/Admin/StockAdjustmentHistoryTest.php` — stubs for INVT-04
- [ ] `tests/Feature/Admin/ProductImageTest.php` — stubs for image upload (S3 fake disk)

*All test files use `RefreshDatabase` + `Storage::fake('s3')` where needed.*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Thumbnail visually correct | PROD-01 | Image quality is subjective | Upload a test image, check thumbnail dimensions and aspect ratio |
| S3 URL accessible from browser | PROD-01 | Requires live S3 credentials | Upload image, confirm URL returns 200 in browser |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 15s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending

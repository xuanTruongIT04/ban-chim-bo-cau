# Milestones

## v1.0 MVP (Shipped: 2026-03-29)

**Phases completed:** 4 phases, 13 plans, 14 tasks

**Key accomplishments:**

- Clean Architecture skeleton (Laravel 12, PHPStan level 6, custom domain boundary rule, Sanctum auth, Vietnamese JSON error envelope)
- Full product catalog CRUD with category management, mixed-unit inventory (con/kg), DECIMAL(10,3) stock, S3 image gallery with auto-thumbnail
- Atomic order placement with DB::transaction + lockForUpdate, idempotency key, cart API, order state machine (5 trạng thái)
- Payment tracking (COD + bank transfer), delivery method (nội tỉnh/ngoại tỉnh), new-order email notification (queued, afterCommit)
- Admin dashboard with order filtering/search/pagination, payment confirmation
- Scribe API documentation covering 34 endpoints with browsable HTML docs

---

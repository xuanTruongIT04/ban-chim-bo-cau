# Retrospective

## Milestone: v1.0 — MVP

**Shipped:** 2026-03-29
**Phases:** 4 | **Plans:** 13

### What Was Built
- Clean Architecture Laravel 12 skeleton with PHPStan level 6 domain boundary enforcement
- Full product catalog CRUD, category management, mixed-unit inventory (con/kg)
- S3 image gallery with auto-thumbnail generation via Intervention Image
- Atomic order placement with pessimistic locking and idempotency key
- Cart API with session tokens, order state machine (5 trạng thái)
- Payment tracking (COD + bank transfer), delivery method tracking
- New-order email notification (queued, afterCommit)
- Admin dashboard with order filtering/search/pagination
- Scribe API documentation (34 endpoints)

### What Worked
- Clean Architecture separation kept Domain layer pure — PHPStan caught violations automatically
- Wave-based parallel execution let Phase 2 and 3 plans run concurrently within waves
- Pessimistic locking pattern (DB::transaction + lockForUpdate) was straightforward to implement and test
- Vietnamese localization from day 1 avoided retrofit costs

### What Was Inefficient
- Phase 2 tracking got out of sync (plans executed but ROADMAP not updated) — required manual fix
- SUMMARY.md one-liner field not consistently populated across phases — milestone accomplishment extraction failed
- Phase 2 was executed out of order (Phases 3-4 completed before Phase 2 tracking was fixed)

### Patterns Established
- `bcadd`/`bccomp` for decimal stock arithmetic (not float)
- `lockForUpdate()` + sorted product IDs for deadlock prevention
- `afterCommit` on queued notifications to avoid premature dispatch
- `@property` annotations on Eloquent models for larastan compatibility
- Vietnamese JSON error envelope as global exception handler

### Key Lessons
- Always verify ROADMAP.md tracking stays in sync during parallel execution
- SUMMARY.md one-liner field needs consistent format enforcement
- Worktree-based agents may not update ROADMAP checkboxes — spot-check after each wave

---

## Cross-Milestone Trends

| Metric | v1.0 |
|--------|------|
| Phases | 4 |
| Plans | 13 |
| Timeline | 2 days |
| LOC | ~7,800 |
| Requirements | 43/43 |
| Commits | 93 |

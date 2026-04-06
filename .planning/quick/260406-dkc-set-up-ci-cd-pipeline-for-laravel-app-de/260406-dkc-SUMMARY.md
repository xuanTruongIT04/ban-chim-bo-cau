---
phase: quick
plan: 260406-dkc
subsystem: ci-cd
tags: [github-actions, ci-cd, deployment, ssh, pest, phpstan]

dependency_graph:
  requires: []
  provides: [ci-cd-pipeline]
  affects: [deployment-workflow]

tech_stack:
  added:
    - GitHub Actions (CI/CD orchestration)
    - appleboy/ssh-action@v1 (SSH deployment)
    - shivammathur/setup-php@v2 (PHP environment setup)
  patterns:
    - Test-then-deploy gating (needs: test)
    - Concurrency group with cancel-in-progress: false (queue deploys, don't cancel)
    - SQLite in-memory for CI (no MySQL service needed — reuses phpunit.xml config)

key_files:
  created:
    - .github/workflows/deploy.yml
  modified: []

decisions:
  - DEPLOY_PATH from secrets (not hardcoded) — allows path changes without modifying workflow file
  - cancel-in-progress: false — queues concurrent deploys instead of cancelling; prevents mid-deploy interruption
  - set -e in SSH script — aborts deploy on first failed command; prevents partial deploys
  - php -d memory_limit=512M for PHPStan — consistent with project STATE.md decision

metrics:
  duration: ~2min
  completed: "2026-04-06T02:53:36Z"
  tasks_completed: 1
  files_created: 1
  files_modified: 0
---

# Phase quick Plan 260406-dkc: CI/CD Pipeline Summary

GitHub Actions workflow that gates every push to main behind Pest + PHPStan, then deploys to production (103.166.185.155) via SSH using appleboy/ssh-action.

## Tasks Completed

| Task | Description | Commit | Files |
|------|-------------|--------|-------|
| 1 | Create GitHub Actions CI/CD workflow | aee827d | `.github/workflows/deploy.yml` |

## What Was Built

A single `.github/workflows/deploy.yml` file with two jobs:

**`test` job** (runs on every push to `main`):
- Checks out code using `actions/checkout@v4`
- Sets up PHP 8.3 with `shivammathur/setup-php@v2` (mbstring, xml, sqlite3, pdo_sqlite, bcmath, gd, intl, zip)
- Copies `.env.example` to `.env`, installs Composer deps, generates app key
- Runs `php artisan test` — uses SQLite in-memory from `phpunit.xml` (no MySQL service needed)
- Runs `php -d memory_limit=512M vendor/bin/phpstan analyse`

**`deploy` job** (runs only after `test` passes):
- Guarded by `needs: test` and `if: github.ref == 'refs/heads/main'`
- SSHes into server via `appleboy/ssh-action@v1` using GitHub Secrets
- Runs deploy script: `git pull`, `composer install --no-dev`, `migrate --force`, config/route/view cache, `queue:restart`

**Concurrency:** Group `deploy-main` with `cancel-in-progress: false` — concurrent pushes queue their deploys rather than cancelling in-progress ones.

## Required GitHub Secrets

Configure these in: GitHub repo → Settings → Secrets and variables → Actions

| Secret | Value |
|--------|-------|
| `SSH_HOST` | `103.166.185.155` |
| `SSH_USER` | SSH username on the server (e.g., `ubuntu`, `deploy`) |
| `SSH_PRIVATE_KEY` | Full private key (paste entire content including `-----BEGIN/END-----` lines) |
| `SSH_PORT` | SSH port number (typically `22`) |
| `DEPLOY_PATH` | Absolute path on server (e.g., `/var/www/ban-chim-bo-cau`) |

## Deviations from Plan

None — plan executed exactly as written.

## Known Stubs

None — workflow file is complete and production-ready once GitHub Secrets are configured.

## Self-Check: PASSED

- [FOUND] `.github/workflows/deploy.yml`
- [FOUND] commit `aee827d`
- [VERIFIED] All structural checks passed (on: push, test job, deploy job, needs: test, appleboy/ssh-action, concurrency, PHP 8.3, phpstan, secrets)

---
phase: quick
plan: 260406-dkc
type: execute
wave: 1
depends_on: []
files_modified:
  - .github/workflows/deploy.yml
autonomous: true
requirements: [CI-CD-SETUP]

must_haves:
  truths:
    - "Push to main triggers CI pipeline that runs Pest tests"
    - "After tests pass, pipeline SSHs into server and deploys latest code"
    - "Developer knows exactly which GitHub Secrets to configure"
  artifacts:
    - path: ".github/workflows/deploy.yml"
      provides: "GitHub Actions CI/CD pipeline"
      contains: "appleboy/ssh-action"
  key_links:
    - from: "GitHub Actions"
      to: "103.166.185.155"
      via: "SSH with private key from GitHub Secrets"
      pattern: "ssh-action"
---

<objective>
Create a GitHub Actions CI/CD pipeline that runs Pest tests on push to main, then deploys to the production server (103.166.185.155) via SSH.

Purpose: Automate testing and deployment so every push to main is validated and deployed without manual intervention.
Output: `.github/workflows/deploy.yml` workflow file.
</objective>

<execution_context>
@$HOME/.claude/get-shit-done/workflows/execute-plan.md
@$HOME/.claude/get-shit-done/templates/summary.md
</execution_context>

<context>
@.planning/STATE.md
@composer.json
@phpunit.xml
@.env.testing
</context>

<tasks>

<task type="auto">
  <name>Task 1: Create GitHub Actions CI/CD workflow</name>
  <files>.github/workflows/deploy.yml</files>
  <action>
Create `.github/workflows/deploy.yml` with two jobs:

**Job 1: `test`** — runs on `ubuntu-latest`:
- Trigger: `push` to `main` branch only
- Steps:
  1. `actions/checkout@v4`
  2. `shivammathur/setup-php@v2` with PHP 8.3, extensions: `mbstring, xml, sqlite3, pdo_sqlite, bcmath, gd, intl, zip`, coverage: `none`
  3. Copy `.env.example` to `.env` (CI needs a base env file)
  4. `composer install --no-interaction --prefer-dist --optimize-autoloader`
  5. `php artisan key:generate`
  6. Run tests: `php artisan test` (uses SQLite in-memory per phpunit.xml — no MySQL service needed)
  7. Run PHPStan: `php -d memory_limit=512M vendor/bin/phpstan analyse` (per STATE.md decision on memory limit)

**Job 2: `deploy`** — runs ONLY after `test` job succeeds (`needs: test`):
- Runs only on `main` branch (redundant guard for safety)
- Uses `appleboy/ssh-action@v1` to SSH into server and run deploy commands
- SSH connection config from GitHub Secrets:
  - `host: ${{ secrets.SSH_HOST }}`
  - `username: ${{ secrets.SSH_USER }}`
  - `key: ${{ secrets.SSH_PRIVATE_KEY }}`
  - `port: ${{ secrets.SSH_PORT }}`
- Deploy script (executed on server):
  ```bash
  cd /var/www/ban-chim-bo-cau
  git pull origin main
  composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
  php artisan migrate --force
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  php artisan queue:restart
  ```

**Important details:**
- Use `concurrency` group `deploy-main` with `cancel-in-progress: false` to prevent overlapping deploys (queue them instead)
- The deploy path `/var/www/ban-chim-bo-cau` should be configurable — use `secrets.DEPLOY_PATH` with a fallback or hardcode and document
- Add clear comments in the YAML explaining each GitHub Secret required

**Required GitHub Secrets (document as YAML comments at top of file):**
- `SSH_HOST` — Server IP (103.166.185.155)
- `SSH_USER` — SSH username on the server
- `SSH_PRIVATE_KEY` — Full private key content (paste entire key including BEGIN/END lines)
- `SSH_PORT` — SSH port (default 22, but configurable)
- `DEPLOY_PATH` — Absolute path to project on server (e.g., /var/www/ban-chim-bo-cau)
  </action>
  <verify>
    <automated>cat .github/workflows/deploy.yml && python3 -c "import yaml; yaml.safe_load(open('.github/workflows/deploy.yml')); print('YAML valid')"</automated>
  </verify>
  <done>
    - `.github/workflows/deploy.yml` exists and is valid YAML
    - Test job: checks out code, sets up PHP 8.3, installs deps, runs Pest + PHPStan
    - Deploy job: SSHs into server, pulls code, runs artisan commands
    - Deploy only runs after tests pass
    - All required secrets documented in file comments
  </done>
</task>

</tasks>

<verification>
- Workflow file is valid YAML (parseable)
- Contains `on: push: branches: [main]`
- Test job uses PHP 8.3, runs `php artisan test` and `phpstan analyse`
- Deploy job uses `appleboy/ssh-action` with secrets references
- Deploy job has `needs: test`
- Concurrency group prevents overlapping deploys
</verification>

<success_criteria>
- Single workflow file at `.github/workflows/deploy.yml`
- Push to main triggers: test (Pest + PHPStan) then deploy (SSH git pull + artisan commands)
- Required GitHub Secrets clearly documented
</success_criteria>

<output>
After completion, create `.planning/quick/260406-dkc-set-up-ci-cd-pipeline-for-laravel-app-de/260406-dkc-SUMMARY.md`
</output>

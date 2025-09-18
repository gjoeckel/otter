## GitHub Integration Updates – Phased Plan (low risk, high gain first)

Scope: Optimize local Git flows in Cursor, safe push gating, and CI deploy reliability. Honor project guardrails: Git Bash for all git actions; PowerShell for server; remote pushes require exact authorization token; no production infra changes without approval.

Quick reference (copy/paste)

Run these from the repo root. Use Git Bash for git actions; either terminal for tests/build.

```bash
# Status (Git Bash)
git status && git log --oneline -10

# Tests (either terminal)
php run_tests.php

# Reports build (either terminal)
npm run build:reports

# Gated push (Git Bash)
VERBOSE=1 DRY_RUN=1 ./scripts/push_to_github.sh "push to github"
./scripts/push_to_github.sh "push to github"
```

### Phase 1 — Local workflow hygiene (no CI changes)

- Adopt Cursor SCM for status/diffs/partial staging.
  - Use the Changes panel to review and stage selectively.
  - Keep commit messages as a single high‑level roll‑up since last push.
- Always update `changelog.md` with a timestamped summary matching the commit.
  - Timestamp examples: PowerShell `Get-Date -Format "yyyy-MM-dd HH:mm:ss"`; Git Bash `date +"%Y-%m-%d %H:%M:%S"`.
  - Changelog/commit template (matches the push script behavior):
    ```
    ## push to github — YYYY-MM-DD HH:MM:SS

    - One-line high-level summary
    ```
    Commit message used for the roll-up commit:
    ```
    One-line high-level summary
    ```
- Enforce terminal policy locally.
  - Git: Git Bash only.
  - Server/tests/tools: PowerShell preferred on Windows.
- Quick checks accessible from Cursor:
  - Status: `git status && git log --oneline -10` (Git Bash)
  - Tests: `php run_tests.php` (either terminal)
  - Reports build (if needed locally): `npm run build:reports` (either terminal)

Success criteria
- Clean, single-message commits; matching timestamped changelog entries.
- Developers see accurate status and diffs in Cursor before committing.

### Phase 2 — Safe automation wrappers (local helpers; still no push)

- Optional local helper to pre-compose `.commitmsg` from a one-line summary and stage changes.
- Keep the actual push gated (Phase 3). Do not alter remotes or CI here.

Success criteria
- Faster local commit preparation without changing remote behavior.

### Phase 3 — Gated push flow (Git Bash only)

- Use the existing `scripts/push_to_github.sh` for pushes.
- Require exact authorization token "push to github" before pushing.
  - The token must be an exact, case-sensitive match and stand alone (no extra words).
- Determine baseline using `@{upstream}..HEAD` with fallback to `origin/<current-branch>..HEAD`.
- Prepend a `push to github` entry with timestamp to `changelog.md` before push.
- Push current branch.

Pre-push checks

- Run tests locally and ensure green before pushing: `php run_tests.php`.
- Review status and diffs in Cursor or via `git status` (the push script commits with `git add -A` and will include untracked files).
- Always run a dry-run first to preview range, files, and summary:
  - `VERBOSE=1 DRY_RUN=1 ./scripts/push_to_github.sh "push to github"`
  - Optional: enforce a policy to require a successful dry-run before a real push (e.g., by having the script require a prior dry-run in the session).

Dry run and verbose preview

```bash
# Preview range, files and summary without pushing
VERBOSE=1 DRY_RUN=1 ./scripts/push_to_github.sh "push to github"

# Execute the gated push
./scripts/push_to_github.sh "push to github"
```

Notes
- If no upstream is configured for the current branch, the script automatically falls back to `origin/<current-branch>..HEAD`.

Optional safeguards
- When pushing `master` or `main`, require an explicit environment confirmation: set `CONFIRM_MAIN=1` for the session before running the push script. This is a documented convention to reduce risk; the script can adopt this check later.

Success criteria
- Pushes remain intentional and audited; changelog entry is prepended (top of file) and mirrors the one-line summary used for the roll-up commit.

### Phase 4 — CI deploy reliability: fix upload path (low-risk, high-gain)

Problem
- Current SFTP step uploads the entire `artifacts/` folder into the remote path, producing a nested `.../otter2/artifacts/...` layout. Health check 404s because `health_check.php` is not at the root deploy path.

Change
- Upload the contents of `artifacts/` to the remote path (not the folder itself).

Example (GitHub Actions YAML snippet)

```yaml
# Example using appleboy/scp-action (uploads contents of artifacts)
- name: Upload artifacts
  uses: appleboy/scp-action@v0.x
  with:
    host: ${{ secrets.DEPLOY_HOST }}
    username: ${{ secrets.DEPLOY_USER }}
    key: ${{ secrets.DEPLOY_SSH_KEY }}
    source: "artifacts/*"   # uploads contents, not the folder
    target: "/var/websites/webaim/htdocs/training/online/otter2"
    rm: false
```

Notes
- If the action does not support globbing, add a prior job step to copy contents into a temp dir (e.g., `artifacts_flat/`) and set `local_path: ./artifacts_flat/`.
- Some actions treat trailing slashes differently. `./artifacts/` often uploads the folder itself (causing a nested `artifacts/`), while `./artifacts/*` uploads the contents.
- Ensure the packaging step that produces `artifacts/` includes root-level application files required post-deploy (e.g., `health_check.php`).

Success criteria
- `https://webaim.org/training/online/otter2/health_check.php` exists at the deploy root after upload.

### Phase 5 — CI deploy reliability: permissions step tightening

Problem
- `chown` operations fail (no permission) and slow down the job. Only `mkdir`/`chmod` are needed for writable app dirs; ownership changes require elevated privileges not available to the deploy user.

Change
- Remove `chown` lines; restrict to `mkdir -p` and `chmod` on app-managed directories.

Example (SSH step excerpt)

```bash
DEPLOY_PATH="/var/websites/webaim/htdocs/training/online/otter2"

# Create writable directories
mkdir -p "$DEPLOY_PATH/cache" "$DEPLOY_PATH/logs" "$DEPLOY_PATH/test-results"
chmod -R 775 "$DEPLOY_PATH/cache" "$DEPLOY_PATH/logs" "$DEPLOY_PATH/test-results"

# Enterprise cache subdirs (if needed by app)
mkdir -p "$DEPLOY_PATH/cache/ccc" "$DEPLOY_PATH/cache/csu" "$DEPLOY_PATH/cache/demo"
chmod -R 775 "$DEPLOY_PATH/cache/ccc" "$DEPLOY_PATH/cache/csu" "$DEPLOY_PATH/cache/demo"
```

Success criteria
- No `Operation not permitted` errors; required app directories are writable.

### Phase 6 — CI post-deploy health verification and warm-up

- Health check should consider both 200 and 302 as pass states (existing logic is fine once files are in place).
- Add a brief warm-up sequence after deploy to prime caches and validate key endpoints.

Example (YAML snippet)

```yaml
- name: Post-deploy health check (with retries)
  run: |
    URL="https://webaim.org/training/online/otter2/health_check.php"
    echo "Checking: $URL"
    for i in 1 2 3 4 5; do
      CODE=$(curl -L --max-time 10 -s -o /dev/null -w "%{http_code}" "$URL" || echo "000")
      if [ "$CODE" = "200" ] || [ "$CODE" = "302" ]; then
        echo "Health check passed: HTTP $CODE"; break
      fi
      echo "Attempt $i failed (HTTP $CODE). Retrying..."; sleep 5
      if [ "$i" = "5" ]; then echo "Health check failed after retries"; exit 1; fi
    done
    # Warm-up a few pages
    curl -L -s "https://webaim.org/training/online/otter2/login.php" >/dev/null
    curl -L -s "https://webaim.org/training/online/otter2/reports/index.php" >/dev/null
```

Success criteria
- Health returns 200/302; primary pages respond; job ends green.

### Phase 7 — Observability from Cursor (no code changes)

- Use Cursor’s GitHub panel to monitor workflow runs, open logs, and jump to failing steps.
- Keep a short runbook entry in `README.md` linking to Actions and deploy config for quick access.
- Add a direct link in `README.md` to the deploy workflow file path (e.g., `.github/workflows/deploy.yml`) for one-click access from the IDE.

Success criteria
- Faster incident triage directly from the IDE.

### Phase 8 — Optional safeguards (admin-only repo settings)

- Protect main branch to require the deploy workflow to pass before merging.
- Add an environment with required reviewers for production deploys if human gate is desired.

Success criteria
- Misconfigured changes are caught before reaching production.

---

Rollout order
1. Phase 1 → Phase 3 (local hygiene, then gated push).
2. Phase 4 → Phase 6 (deploy path fix, then permissions, then health/warm‑up).
3. Phase 7 → Phase 8 (observability, then optional repo safeguards).

Rollback guidance
- If deploy breaks after Phase 4/5 edits, revert only the workflow step you changed and re‑run the previous green configuration. Keep the push gating intact.

Definition of Done
- Local commits streamlined and consistent; pushes remain gated.
- CI deploy places files at the correct path without permission errors.
- Health check passes reliably after deploy; key pages load; runs visible in Cursor.



# Writable Paths (MVP)

This project writes only to a small, well-defined set of paths. For MVP reliability on shared hosting, deployment sets permissive directory permissions to avoid runtime failures. Harden later if group ownership can be guaranteed.

## Summary of writable locations

- cache/
  - Purpose: PHP session storage (sess_* files)
  - Created by: `deploy.yml` and `lib/session.php`
  - Writers: PHP session handler
  - Permissions (deploy): directory 777

- cache/\<enterprise\>/ (csu, ccc, demo)
  - Files:
    - all-registrants-data.json
    - all-submissions-data.json
    - registrations.json
    - enrollments.json
    - certificates.json
    - refresh_debug.log
  - Created by: `lib/enterprise_cache_manager.php` (mkdir 0777, true)
  - Writers: `lib/enterprise_data_service.php`, `reports/reports_api.php`, `reports/reports_api_internal.php`
  - Permissions (deploy): parent `cache/` is 777; enterprise subdirs set to 777

- logs/
  - Files: console_errors_YYYY-MM-DD.log
  - Created by: `lib/api/console_log.php` (mkdir 0777, true)
  - Writers: `lib/api/console_log.php`
  - Permissions (deploy): directory 777

- config/passwords.json
  - Purpose: Admin password updates via UI
  - Writers: `lib/unified_database.php::savePasswordsData()`
  - Permissions (deploy): `config/` 775; `passwords.json` 664

- test-results/ (optional)
  - Purpose: CI/test artifacts on server if used
  - Permissions (deploy): directory 777

- reports/cache (optional/legacy)
  - Purpose: Legacy cache location if present
  - Permissions (deploy): if directory exists, set 775

## Code references (writers)

- Sessions: `lib/session.php` sets `session.save_path` to `cache/`; session files are `cache/sess_*`.
- Cache JSON + refresh debug:
  - `lib/enterprise_cache_manager.php` (path helpers, writeCacheFile)
  - `lib/enterprise_data_service.php` (refresh, derived files, `refresh_debug.log`)
  - `reports/reports_api.php` and `reports/reports_api_internal.php` (caching and derived files)
- Console logs: `lib/api/console_log.php` appends to `logs/console_errors_YYYY-MM-DD.log`.
- Config updates: `lib/unified_database.php` writes `config/passwords.json`.

## Deployment permissions (authoritative)

Set by `.github/workflows/deploy.yml`:

- Baseline
  - Files 644; directories 755; `*.php` 755
- Writable application dirs
  - `mkdir -p "$DEPLOY_PATH/cache" "$DEPLOY_PATH/logs" "$DEPLOY_PATH/test-results"`
  - `chmod -R 777 "$DEPLOY_PATH/cache" "$DEPLOY_PATH/logs" "$DEPLOY_PATH/test-results"`
  - `mkdir -p "$DEPLOY_PATH/cache/ccc" "$DEPLOY_PATH/cache/csu" "$DEPLOY_PATH/cache/demo"`
  - `chmod -R 777 "$DEPLOY_PATH/cache/ccc" "$DEPLOY_PATH/cache/csu" "$DEPLOY_PATH/cache/demo"`
- Config file
  - If present: `chmod -R 775 "$DEPLOY_PATH/config"` and `chmod 664 "$DEPLOY_PATH/config/passwords.json"`
- Legacy reports cache
  - If present: `chmod -R 775 "$DEPLOY_PATH/reports/cache"`

Note: The permissions helper `scripts/ci-remote-permissions.sh` mirrors the above on a given `$DEPLOY_PATH` and intentionally avoids `chown` for compatibility with restricted hosts.

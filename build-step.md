## Frontend Build Step (AI Agent Guide)

### Purpose
- **SIMPLE and RELIABLE** JS delivery for the reports page by removing fragile runtime imports/paths and ensuring consistent cache-busting.
- Server-side data flow (PHP → Google Sheets → JSON) remains unchanged.

### Critical Rules
- **Git**: Use Git Bash for all git actions; pushing is gated by explicit approval.
- **Server/local testing**: Use PowerShell for running the PHP server and HTTP checks.
- **Working directory**: Always operate from `otter/` root.

### What a Build Step Does (here)
- **Bundle** `reports/js/*.js` into one file for the reports page.
- **Minify**; emit **sourcemaps locally** for debugging (CI build omits sourcemaps).
- Provide a **single, stable script URL**; cache-bust with `?v=timestamp`.

### Strategy Options
- **Minimal, reliable (recommended now)**:
  - Keep ES modules internally.
  - Create ONE entry file that imports the others (`reports/js/reports-entry.js`).
  - Bundle to `reports/dist/reports.bundle.js`.
  - Reference only this built file in `reports/index.php` with `?v=<?php echo time(); ?>`.
- **Later**: content-hashed filenames + manifest (heavier; not needed for reliability now).

### Phased Rollout (validate locally after each phase)
- **Phase 1 – Baseline**
  - Start PHP server; open `reports/index.php`; capture console/network logs.
- **Phase 2 – Entry consolidation (no build yet)**
  - Create `reports/js/reports-entry.js` that imports existing modules in current load order (see Entry Template).
  - Optional smoke test: keep classic non-module scripts and run local build to verify.
- **Phase 3 – Bundle with esbuild (local)**
  - Build the entry to `reports/dist/` (commands below) and replace multiple scripts with one.
- **Phase 4 – Production prep**
  - Decide artifact policy (commit bundle vs build in CI). Ensure server sends `Content-Type: application/javascript`.
- **Phase 5 – Deploy**
  - Deploy HTML change + bundle. Verify no 404s/no export errors, widget logs appear, counts/links update.
- **Phase 6 – Cleanup**
  - Remove legacy global fallbacks once stable. Keep a small runtime self-check (console.warn) for key APIs.

### Entry Template (reports/js/reports-entry.js)
```javascript
// reports/js/reports-entry.js (entry)
import './filter-state-manager.js';
import './datalist-utils.js';
import './reports-data.js';
import './date-range-picker.js';
import './groups-search.js';
import './organization-search.js';
import './reports-messaging.js';
import './reports-ui.js';
import './data-display-options.js';
// Note: classic non-module scripts (e.g., ../lib/table-filter-interaction.js) remain separate <script> tags.
```

### HTML changes (reports/index.php)
- Replace multiple module script tags with a single built file. Keep classic non-module scripts unchanged.
```html
<!-- Before (multiple module scripts) -->
<script type="module" src="js/filter-state-manager.js?v=<?php echo time(); ?>"></script>
<script type="module" src="js/datalist-utils.js?v=<?php echo time(); ?>"></script>
<script type="module" src="js/reports-data.js?v=<?php echo time(); ?>"></script>
<script type="module" src="js/date-range-picker.js?v=<?php echo time(); ?>"></script>
<script type="module" src="js/groups-search.js?v=<?php echo time(); ?>"></script>
<script type="module" src="js/organization-search.js?v=<?php echo time(); ?>"></script>
<script type="module" src="js/reports-messaging.js?v=<?php echo time(); ?>"></script>
<script type="module" src="js/reports-main.js?v=<?php echo time(); ?>"></script>
<script type="module" src="js/data-display-options.js?v=<?php echo time(); ?>"></script>

<!-- After (single module script) -->
<script type="module" src="dist/reports.bundle.js?v=<?php echo time(); ?>"></script>

<!-- Keep classic script if needed (non-module, global) -->
<script src="../lib/table-filter-interaction.js?v=<?php echo time(); ?>"></script>
```

### Local build commands (run from otter/)
- One-off build:
```bash
npx esbuild reports/js/reports-entry.js --bundle --format=esm --minify --sourcemap --outfile=reports/dist/reports.bundle.js --log-level=info
```
- Optional watch:
```bash
npx esbuild reports/js/reports-entry.js --bundle --format=esm --minify --sourcemap --outfile=reports/dist/reports.bundle.js --log-level=info --watch
```

### CI build (GitHub Actions – concept)
```yaml
- name: Setup Node
  uses: actions/setup-node@v4
  with:
    node-version: '20'
- name: Install esbuild
  run: npm i -D esbuild
- name: Build reports bundle
  run: npx esbuild reports/js/reports-main.js --bundle --format=esm --minify --sourcemap --outdir=reports/dist --log-level=info
# Then proceed with the existing SFTP deploy step (deploy ./reports/dist and page changes)
```
Replace with:
```yaml
- name: Build reports bundle
  run: npx esbuild reports/js/reports-entry.js --bundle --format=esm --minify --outfile=reports/dist/reports.bundle.js --log-level=info
```

### Pathing & Caching Notes
- **Path discipline**: From within `reports/`, reference assets in `js/` (not `reports/js/`). Bundling removes most path risks.
- **Cache-busting**: Use `?v=<?php echo time(); ?>` in the single script tag. Avoid per-file versions.
- **Dynamic imports**: Prefer static imports for shared libs to avoid bundle/runtime mismatch. If a dynamic import is truly needed, do not cache-bust inside the import path.

### What This Does NOT Change
- Server-side APIs (`reports_api.php`, internal APIs), cache TTL, Google Sheets integration, or data processing. Only the JS delivery changes.

### Troubleshooting
- **404 on JS**: Verify `reports/dist/reports.bundle.js` exists and the HTML src matches.
- **MIME/ESM error**: Ensure server serves `.js` with `Content-Type: application/javascript`.
- **Stale module/“does not provide an export”**: After bundling, the browser loads only the built file; this class of error should disappear unless a stale built file is referenced.
- **Mixed globals/modules**: Keep classic non-module files as separate `<script>` tags; bundle the rest.

### Rollback Plan
- Revert `reports/index.php` to multiple script tags.
- Remove or ignore `reports/dist/*`.
- No server-side changes are necessary to roll back.

### Validation Checklist (after each change)
- No console red errors; no 404 network requests.
- Enrollment widget logs appear (init, applyMode, count/link updates).
- Date picker Apply flows data fetch and table updates.
- Links (`registrants.php`, `enrollees.php`, `certificates-earned.php`) reflect the active date range.

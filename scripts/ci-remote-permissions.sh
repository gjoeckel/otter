#!/usr/bin/env bash
set -euo pipefail

# Usage: ci-remote-permissions.sh [DEPLOY_PATH]
# Default deploy path matches deploy-config.json target (otter3)
DEPLOY_PATH="${1:-/var/websites/webaim/htdocs/training/online/otter3}"

echo "[permissions] Using DEPLOY_PATH=$DEPLOY_PATH"

# Create writable application-managed directories
mkdir -p "$DEPLOY_PATH/cache" "$DEPLOY_PATH/logs" "$DEPLOY_PATH/test-results"
chmod -R 777 "$DEPLOY_PATH/cache" "$DEPLOY_PATH/logs" "$DEPLOY_PATH/test-results"

# Enterprise cache subdirectories (created on demand by app, but ensure exist)
mkdir -p "$DEPLOY_PATH/cache/ccc" "$DEPLOY_PATH/cache/csu" "$DEPLOY_PATH/cache/demo"
chmod -R 777 "$DEPLOY_PATH/cache/ccc" "$DEPLOY_PATH/cache/csu" "$DEPLOY_PATH/cache/demo"

# Baseline permissions (avoid chown; CI user may not have privileges)
find "$DEPLOY_PATH" -type f -exec chmod 644 {} \;
find "$DEPLOY_PATH" -type d -exec chmod 755 {} \;

echo "[permissions] Completed chmod adjustments without chown"



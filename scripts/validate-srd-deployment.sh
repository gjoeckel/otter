#!/bin/bash
# SRD-compliant deployment validation script
# Simple, Reliable, DRY - uses existing health check system

TARGET_FOLDER=${1:-"otter2"}
BASE_URL="https://webaim.org/training/online"
HEALTH_URL="$BASE_URL/$TARGET_FOLDER/health_check.php"

echo "=== SRD Deployment Validation ==="
echo "Target: $TARGET_FOLDER"
echo "Health Check: $HEALTH_URL"
echo "Timestamp: $(date)"
echo ""

# Single validation using existing health check (SRD Simple)
response=$(curl -s "$HEALTH_URL?commit=$(git rev-parse HEAD)&target=$TARGET_FOLDER")

if echo "$response" | jq -e '.status == "healthy"' > /dev/null; then
    echo "✅ Health check passed"
    echo "✅ SRD architecture validated"
    echo "✅ All critical files present"

    # Display SRD validation details
    echo ""
    echo "=== SRD Validation Details ==="
    echo "$response" | jq '.srd_validation'

    # Display deployment validation details
    echo ""
    echo "=== Deployment Validation Details ==="
    echo "$response" | jq '.deployment_validation'

    echo ""
    echo "🎯 SRD Deployment Validation: PASSED"
    exit 0
else
    echo "❌ Health check failed"
    echo "❌ SRD deployment validation failed"
    echo ""
    echo "=== Error Details ==="
    echo "$response" | jq '.'
    exit 1
fi

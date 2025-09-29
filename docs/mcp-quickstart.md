# MCP Quick Start Guide - Windows 11

**Time**: 10 minutes | **Audience**: Users who want to get started immediately

**For detailed explanations, see**: [Complete Windows 11 Setup Guide](windows-setup.md)

## What is MCP?

Model Context Protocol (MCP) provides tools that enhance Cursor IDE with:
- **Chrome DevTools**: Browser automation for testing
- **Filesystem**: Enhanced file access
- **Memory**: Context preservation across sessions
- **Git**: Source control operations

This guide gets these tools working on Windows 11.

## Prerequisites Check

Before starting, ensure you have:
- [ ] Git Bash installed
- [ ] Node.js 18+
- [ ] PHP 8.0+
- [ ] Chrome

**Missing software?** See [Prerequisites](windows-setup.md#prerequisites) in full guide.

## Automated Setup (Recommended)

```bash
# Run automated setup script
./scripts/setup-windows-mcp.sh

# If successful, skip to "Verify Installation" below
# If it fails, see troubleshooting or follow manual steps in windows-setup.md
```

## Manual Setup (If Automated Fails)

See [Manual Setup Steps](windows-setup.md#initial-setup-steps) in full guide.

## Verify Installation

```bash
./scripts/validate-environment.sh
./scripts/check-mcp-health.sh
```

**Expected**: All green checkmarks (✓)

## Daily Commands

```bash
# Start development
./scripts/start-chrome-debug.sh    # Start Chrome debugging
./tests/start_server.sh            # Start PHP server

# If issues arise
./scripts/check-mcp-health.sh      # Diagnose issues
./scripts/restart-mcp-servers.sh   # Quick restart
```

## Common Issues

| Symptom | Quick Fix | If That Fails |
|---------|-----------|---------------|
| "MCP tools not responding" | `./scripts/restart-mcp-servers.sh` | See [Troubleshooting](windows-setup.md#troubleshooting) |
| "Port 9222 not listening" | `./scripts/start-chrome-debug.sh` | Check Windows Firewall |
| "Wrong shell" | Switch to Git Bash terminal | [Shell Setup](windows-setup.md#1-configure-git-bash-as-default-shell) |

**Still stuck?** See [Complete Troubleshooting Guide](windows-setup.md#troubleshooting)

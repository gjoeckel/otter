# Local Testing Environment Setup

This directory contains scripts to provide a comprehensive local testing environment setup that can be triggered by typing **"start local testing"**.

## 🚀 Quick Start

### Option 1: Direct PowerShell (Recommended)
```powershell
# From the otter project root directory
.\scripts\start-local-testing.ps1
```

### Option 2: Add to PowerShell Profile (Permanent)
```powershell
# Run once to add commands to your PowerShell profile
.\scripts\Add-LocalTestingToProfile.ps1

# Then you can use anywhere:
start local testing
```

### Option 3: Batch File (Windows)
```cmd
# From the otter project root directory
scripts\start-local-testing.cmd
```

## 📋 What the Script Does

The enhanced local testing environment performs these phases:

### Phase 1: Environment Validation (30s)
- ✅ Checks PHP 8.4.6+ version
- ✅ Validates Node.js and npm installation
- ✅ Verifies package.json exists
- ✅ Checks critical config files (csu.config, ccc.config, demo.config)
- ✅ Cleans cache directories (cache/ccc, cache/csu, cache/demo)

### Phase 2: Server Management (15s)
- ✅ Stops existing PHP and WebSocket processes
- ✅ Starts PHP server on localhost:8000 with error logging
- ✅ Starts WebSocket server on localhost:8080 (optional)
- ✅ Verifies servers are responding

### Phase 3: Build Process (20s)
- ✅ Runs `npm ci` to install dependencies
- ✅ Builds reports bundle with `npm run build:reports`
- ✅ Verifies build output exists and is valid

### Phase 4: Testing Preparation (10s)
- ✅ Runs health checks on all endpoints
- ✅ Validates enterprise configurations
- ✅ Checks PHP error logs
- ✅ Provides access URLs and testing commands

**Total Setup Time**: ~75 seconds

## 🎯 Available Commands

After setup, you can use:

| Command | Description |
|---------|-------------|
| `Start-LocalTesting` | Complete setup (recommended) |
| `start-local-testing` | Alias for Start-LocalTesting |
| `slt` | Short alias |
| `start local testing` | Natural language command |

## ⚙️ Command Options

```powershell
Start-LocalTesting [options]

Options:
  -SkipBuild          Skip the npm build process
  -SkipWebSocket      Skip starting the WebSocket server  
  -SkipValidation     Skip environment validation checks
  -PhpPort 8000       Port for PHP server (default: 8000)
  -WebSocketPort 8080 Port for WebSocket server (default: 8080)
  -Verbose            Enable verbose output and logging
```

## 📱 Access Points After Setup

Once the environment is ready, you can access:

- **🌐 Main Application**: http://localhost:8000
- **🔐 Login Page**: http://localhost:8000/login.php
- **📊 Reports**: http://localhost:8000/reports/index.php
- **❤️ Health Check**: http://localhost:8000/health_check.php
- **🔌 WebSocket Console**: ws://localhost:8080/console-monitor

## 🧪 Testing Commands

After setup, you can run:

```bash
# Run all tests
php run_tests.php

# Test specific enterprise
php run_tests.php csu

# View recent errors
Get-Content php_errors.log -Tail 10

# Check server status
Invoke-WebRequest http://localhost:8000/health_check.php
```

## 🛑 Stopping the Environment

```powershell
# Stop all PHP processes
taskkill /F /IM php.exe

# Or use Ctrl+C if running servers in foreground
```

## 🔧 Troubleshooting

### Common Issues

**"PHP not found"**
- Ensure PHP 8.4.6+ is installed and in PATH
- Verify with: `php --version`

**"Node.js not found"**
- Install Node.js and npm
- Verify with: `node --version` and `npm --version`

**"Port already in use"**
- Script will attempt to free ports automatically
- Manually stop processes: `taskkill /F /IM php.exe`
- Use different ports: `Start-LocalTesting -PhpPort 8001`

**"Build failed"**
- Check npm dependencies: `npm ci`
- Verify package.json scripts: `npm run build:reports`
- Use verbose mode: `Start-LocalTesting -Verbose`

**"Health checks failing"**
- Check PHP error log: `Get-Content php_errors.log`
- Verify config files exist in `config/` directory
- Ensure all required files are present

### Getting Help

```powershell
# Show detailed help
Get-Help Start-LocalTesting -Full

# Run with verbose output
Start-LocalTesting -Verbose

# Skip problematic steps
Start-LocalTesting -SkipValidation -SkipBuild
```

## 🔄 Integration with Existing Workflow

This script integrates with your existing development workflow:

- **Uses existing scripts**: Leverages `tests/start_server.ps1` patterns
- **Respects project structure**: Works with existing config and cache directories
- **Build system integration**: Uses `package.json` scripts
- **Health check compatibility**: Works with existing `health_check.php`

## 📝 Logging and Monitoring

The script provides comprehensive logging:

- **Real-time progress**: Color-coded status updates
- **Error tracking**: Collects and reports all errors
- **Health monitoring**: Tests all endpoints after setup
- **Performance timing**: Shows setup duration

All output is logged and can be reviewed for troubleshooting.

## 🎉 Success Indicators

You'll know the environment is ready when you see:

```
🎉 LOCAL TESTING ENVIRONMENT READY!
===========================================
Setup completed in 75.2 seconds

📱 ACCESS POINTS:
   🌐 Main Application: http://localhost:8000
   🔐 Login Page: http://localhost:8000/login.php
   📊 Reports: http://localhost:8000/reports/index.php
   ❤️ Health Check: http://localhost:8000/health_check.php
   🔌 WebSocket Console: ws://localhost:8080/console-monitor

✅ Local testing environment is ready for use!
```

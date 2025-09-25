# MVP Local Command for Git Bash

## Overview

The `mvp local` command provides a simple way to start the MVP local testing environment in Git Bash. This command replaces the previous PowerShell-based approach with a Bash-native solution.

## Setup

The command is automatically set up when you run:

```bash
./setup-mvp-local-command.sh
```

This adds the `mvp` function to your bash profile (`~/.bashrc`).

## Usage

### Start MVP Local Environment
```bash
mvp local
```

This command will:
- ✅ Verify you're in the correct project directory
- ✅ Check that PHP, Node.js, and npm are installed
- ✅ Stop any existing PHP server processes
- ✅ Start PHP development server on port 8000
- ✅ Build the JavaScript bundle
- ✅ Run health checks on all endpoints
- ✅ Display access information and keep the server running

### Other MVP Commands
```bash
mvp build    # Build MVP reports bundle only
mvp test     # Test MVP system
mvp help     # Show help message
mvp-local    # Alias for 'mvp local'
```

## What the Command Does

When you run `mvp local`, it:

1. **Environment Validation**
   - Checks if you're in the otter project directory
   - Verifies PHP, Node.js, and npm are available
   - Shows version information

2. **Server Management**
   - Stops any existing PHP processes on port 8000
   - Starts PHP server with enhanced error logging
   - Creates `php_errors.log` if it doesn't exist

3. **Build Process**
   - Installs npm dependencies if needed
   - Builds the JavaScript bundle using `npm run build:mvp`
   - Verifies the bundle was created successfully

4. **Health Checks**
   - Tests all major endpoints:
     - Health check: `http://localhost:8000/health_check.php`
     - Login page: `http://localhost:8000/login.php`
     - Reports page: `http://localhost:8000/reports/index.php`
     - Main application: `http://localhost:8000/`

5. **Access Information**
   - Shows all available URLs
   - Provides testing commands
   - Shows logging commands
   - Displays server management commands

## Access Points

After running `mvp local`, you can access:

- **Main Application**: http://localhost:8000
- **Login Page**: http://localhost:8000/login.php
- **Reports**: http://localhost:8000/reports/index.php
- **Health Check**: http://localhost:8000/health_check.php

## Server Management

### Stop the Server
```bash
# If you know the PID (shown when server starts)
kill <PID>

# Or find and kill the process
kill $(pgrep -f "php.*-S.*localhost:8000")
```

### View Logs
```bash
# View recent errors
tail -10 php_errors.log

# Monitor logs in real-time
tail -f php_errors.log
```

### Testing
```bash
# Run all tests
php run_tests.php

# Test specific enterprise
php run_tests.php csu
```

## Troubleshooting

### Command Not Found
If `mvp` command is not found:
```bash
# Reload your bash profile
source ~/.bashrc

# Or restart your terminal
```

### Server Won't Start
If the server fails to start:
```bash
# Check if port 8000 is in use
netstat -an | grep :8000

# Kill any processes using port 8000
kill $(lsof -ti:8000)
```

### Build Failures
If the JavaScript bundle build fails:
```bash
# Try building manually
npm run build:mvp

# Or install dependencies first
npm install
npm run build:mvp
```

## Files Created

- `mvp-local.sh` - Main script that starts the MVP environment
- `setup-mvp-local-command.sh` - Setup script that adds the command to your profile
- `~/.bashrc` - Updated with the `mvp` function

## Benefits

- ✅ **Simple Command**: Just type `mvp local` to start everything
- ✅ **Comprehensive Setup**: Handles all environment setup automatically
- ✅ **Health Checks**: Verifies everything is working correctly
- ✅ **Error Handling**: Provides clear error messages and solutions
- ✅ **Cross-Platform**: Works on Windows, macOS, and Linux
- ✅ **Consistent**: Same command works every time

## Integration with Project

This command integrates with the existing project structure:
- Uses the same PHP server configuration as other scripts
- Builds the same JavaScript bundle
- Follows the same testing patterns
- Maintains compatibility with existing workflows

The `mvp local` command is now the recommended way to start the MVP local testing environment in Git Bash.

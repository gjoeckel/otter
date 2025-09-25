# Chrome MCP Server Testing Guide

## Overview
The Chrome MCP (Model Context Protocol) server provides real-time access to Chrome DevTools, allowing me to monitor and debug the MVP reports system as you test it on the local server.

## Current MCP Server Status
✅ **MCP Server Running**: `cd browsertools-mcp; node server-simple.js`
✅ **Connected to Chrome**: http://localhost:8000/login.php
✅ **DevTools Domains Enabled**: Console, Network, Runtime, etc.

## How I Will Leverage Chrome MCP for Testing

### 1. **Real-Time Console Monitoring**
When you test the MVP system, I can:
- **Monitor JavaScript errors** in real-time
- **Track console.log messages** from MVP modules
- **Identify runtime issues** immediately
- **Verify MVP bundle loading** status

**Example MCP Commands I'll Use:**
```
get_console_logs - Monitor all console output
execute_js - Run diagnostic JavaScript code
get_page_info - Check page load status
```

### 2. **Network Activity Analysis**
I can monitor:
- **API calls** to `reports_api.php` and `mvp_reports_api.php`
- **Bundle loading** (`mvp-reports.bundle.js` vs `reports.bundle.js`)
- **Response times** and data sizes
- **Error responses** (4xx, 5xx status codes)

**Example MCP Commands I'll Use:**
```
get_network_activity - Monitor all network requests
inspect_session - Check session state
```

### 3. **Session State Verification**
I can verify:
- **Authentication status** (`$_SESSION['organization_authenticated']`)
- **Enterprise code** (`$_SESSION['enterprise_code']`)
- **Session persistence** across page navigation
- **Cookie management**

### 4. **MVP vs Original Comparison**
I can compare:
- **Bundle sizes** (MVP: 10KB vs Original: 37KB)
- **JavaScript execution** (MVP: simplified vs Original: complex)
- **UI behavior** (MVP: no radio buttons vs Original: full controls)
- **Data display** (MVP: hardcoded modes vs Original: dynamic)

## Testing Workflow

### Phase 1: MVP System Test
1. **You run**: `.\mvp-local.ps1` (or `mvp local` token)
2. **I monitor**: Console logs for MVP bundle loading
3. **I verify**: No count options JavaScript errors
4. **I check**: API calls use hardcoded modes

### Phase 2: Data Verification
1. **You navigate**: http://localhost:8000/reports/mvp-reports-index.php
2. **I monitor**: Network requests to `reports_api.php`
3. **I verify**: Response contains correct data (7230, 3281, 1649)
4. **I check**: No mode switching or auto-switching logic

### Phase 3: UI Behavior Test
1. **You interact**: Date range picker, Apply button
2. **I monitor**: Table updates and data binding
3. **I verify**: Simple, direct table updates
4. **I check**: No radio button event listeners

### Phase 4: Comparison Test
1. **You test**: Original reports page
2. **I compare**: Bundle sizes, complexity, errors
3. **I document**: Differences between MVP and original
4. **I verify**: MVP eliminates count options issues

## Expected MVP Behavior

### ✅ What Should Work:
- **MVP bundle loads** without errors
- **Data displays correctly** (7230, 3281, 1649)
- **No count options complexity** in console
- **Simple table updates** without mode switching
- **Hardcoded modes** always used

### ❌ What Should NOT Happen:
- **No radio button errors** (elements not found)
- **No auto-switching logic** execution
- **No mode change events** triggered
- **No complex widget wiring** attempts
- **No cohort filtering** computations

## MCP Diagnostic Commands

### Console Monitoring:
```javascript
// Check if MVP bundle loaded
console.log('MVP Bundle Status:', typeof window.reportsDataService);

// Verify hardcoded modes
console.log('Current Modes:', window.getCurrentModes?.());

// Check for count options elements
console.log('Radio Buttons:', document.querySelectorAll('input[name*="display"]').length);
```

### Network Analysis:
```javascript
// Monitor API calls
performance.getEntriesByType('resource')
  .filter(r => r.name.includes('reports_api'))
  .forEach(r => console.log('API Call:', r.name, r.duration));
```

### Session Verification:
```javascript
// Check session state
fetch('reports_api.php?test=1')
  .then(r => r.json())
  .then(data => console.log('Session Status:', data));
```

## Real-Time Debugging Process

1. **You start testing** → I begin monitoring console logs
2. **You navigate to MVP page** → I verify bundle loading
3. **You select date range** → I monitor API calls
4. **You click Apply** → I check data binding
5. **You report issues** → I analyze console/network logs
6. **I provide solutions** → Based on real-time MCP data

## Benefits of MCP Integration

### For You:
- **Immediate feedback** on issues
- **Real-time debugging** assistance
- **Automated monitoring** of system health
- **Detailed error analysis** without manual inspection

### For Me:
- **Live system state** visibility
- **Actual error messages** and stack traces
- **Network request/response** data
- **JavaScript execution** context

## MCP Server Commands Available

```
get_console_logs     - Real-time console monitoring
get_network_activity - Network request tracking
get_cookies         - Session/cookie inspection
inspect_session     - Session state verification
execute_js          - Run diagnostic JavaScript
get_page_info       - Page load status and metadata
```

## Testing Scenarios

### Scenario 1: MVP Bundle Loading
- **You**: Navigate to MVP reports page
- **I**: Monitor console for bundle load success/failure
- **Expected**: `mvp-reports.bundle.js` loads without errors

### Scenario 2: Data Display
- **You**: Select date range and click Apply
- **I**: Monitor API calls and response data
- **Expected**: Correct counts displayed (7230, 3281, 1649)

### Scenario 3: UI Simplicity
- **You**: Look for count options controls
- **I**: Check for missing DOM elements
- **Expected**: No radio buttons, no complex controls

### Scenario 4: Performance Comparison
- **You**: Test both MVP and original pages
- **I**: Compare bundle sizes and load times
- **Expected**: MVP faster, simpler, more reliable

## Conclusion

The Chrome MCP server provides **real-time visibility** into the MVP system's behavior, allowing me to:
- **Monitor system health** continuously
- **Debug issues immediately** as they occur
- **Verify MVP functionality** against requirements
- **Compare performance** with original system

This creates a **collaborative debugging environment** where you can test the system while I provide real-time analysis and support based on live Chrome DevTools data.

# Puppeteer Migration Guide

## Current Implementation (Simple CDP)

The current implementation uses raw Chrome DevTools Protocol (CDP) for simplicity and reliability. This approach:

- ✅ No heavy dependencies (Puppeteer is 100MB+)
- ✅ Direct control over Chrome DevTools
- ✅ Simpler codebase (~400 lines vs 700+)
- ✅ Focuses on observation tools for manual testing
- ✅ Automatic reconnection on disconnect

### Available Tools
- `get_console_logs` - Monitor JavaScript errors
- `get_network_activity` - Track API calls
- `get_cookies` - Inspect all cookies
- `inspect_session` - Check PHP session state
- `execute_js` - Run diagnostic JavaScript
- `get_page_info` - Get page metadata

## When to Consider Puppeteer

Reintroduce Puppeteer if you need:

1. **Browser Automation**
   - Automated form filling
   - Click sequences
   - Navigation workflows
   - Screenshot generation

2. **Advanced Wait Strategies**
   - Wait for specific elements
   - Wait for network idle
   - Complex timing scenarios

3. **Cross-Browser Testing**
   - Firefox support
   - WebKit support
   - Headless testing

## How to Reintroduce Puppeteer

### Step 1: Switch to Puppeteer Server

```bash
# The Puppeteer server is already available
npm run start:puppeteer
```

### Step 2: Update Cursor Configuration

```json
{
  "mcpServers": {
    "otter-browsertools": {
      "command": "node",
      "args": ["server-puppeteer.js"],
      "cwd": "<path-to-project>\\browsertools-mcp"
    }
  }
}
```

### Step 3: Additional Tools Available

With Puppeteer, you gain:
- `navigate_to` - Navigate to URLs
- `wait_for_element` - Wait for elements to appear
- `click_element` - Click on elements
- `type_text` - Type in input fields
- `take_screenshot` - Capture screenshots

## Comparison

| Feature | Simple CDP | Puppeteer |
|---------|------------|-----------|
| **Size** | ~10KB | ~100MB+ |
| **Dependencies** | ws, http | puppeteer, winston |
| **Code Complexity** | ~400 lines | ~700 lines |
| **Observation Tools** | ✅ All | ✅ All |
| **Automation Tools** | ❌ None | ✅ Full |
| **Reconnection** | ✅ Manual | ✅ Automatic |
| **Wait Strategies** | ❌ Basic | ✅ Advanced |
| **Error Messages** | Basic | Detailed |

## Current Philosophy

The project currently follows these principles:
- **Simple**: Minimal dependencies and complexity
- **Reliable**: Focus on what works consistently
- **DRY**: Don't repeat yourself

The simple CDP approach aligns with these principles for manual testing scenarios. Only add Puppeteer when automation becomes a requirement.

## Migration Checklist

If you decide to use Puppeteer:

- [ ] Ensure you need automation features
- [ ] Run `npm install` to ensure Puppeteer is installed
- [ ] Test with `npm run start:puppeteer`
- [ ] Update documentation if making it permanent
- [ ] Consider creating test scripts for automation workflows

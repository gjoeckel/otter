# 🔧 Chrome PATH Fix for Windows 11

## 🚨 **Issue: Chrome Not in PATH**

You're getting "Chrome not found" errors because Chrome isn't accessible from the command line. Here are several solutions for Windows 11:

## 🚀 **Quick Fix (Recommended)**

### **Option 1: Use the Fixed Scripts**

I've created fixed versions that automatically find Chrome:

```powershell
# Use the PATH-fixed PowerShell script
.\browsertools-mcp\start-chrome-debug-fixed.ps1
```

Or:

```cmd
# Use the PATH-fixed batch script
browsertools-mcp\start-chrome-debug-fixed.cmd
```

These scripts automatically find Chrome in common locations and use the full path.

### **Option 2: Fix PATH Automatically**

Run the PATH fix script:

```powershell
.\browsertools-mcp\fix-chrome-path.ps1
```

## 🔧 **Manual PATH Fix (Windows 11)**

### **Method 1: System Settings (Recommended)**

1. **Press `Win + I`** to open Settings
2. **Search for "Environment Variables"**
3. **Click "Edit the system environment variables"**
4. **Click "Environment Variables" button**
5. **Under "User variables", select "Path" and click "Edit"**
6. **Click "New" and add one of these paths:**

```
C:\Program Files\Google\Chrome\Application
```

Or if you have 32-bit Chrome:

```
C:\Program Files (x86)\Google\Chrome\Application
```

7. **Click "OK" on all dialogs**
8. **Restart your terminal/PowerShell**

### **Method 2: Command Line (Advanced)**

Open **PowerShell as Administrator** and run:

```powershell
# Add Chrome to PATH
$chromePath = "C:\Program Files\Google\Chrome\Application"
$currentPath = [Environment]::GetEnvironmentVariable("PATH", "User")
[Environment]::SetEnvironmentVariable("PATH", "$currentPath;$chromePath", "User")
```

### **Method 3: Registry (Advanced)**

1. **Press `Win + R`**, type `regedit`, press Enter
2. **Navigate to:** `HKEY_CURRENT_USER\Environment`
3. **Find the `PATH` entry**
4. **Add Chrome directory to the value**

## 🔍 **Find Chrome Installation**

### **Common Chrome Locations on Windows 11:**

```
C:\Program Files\Google\Chrome\Application\chrome.exe
C:\Program Files (x86)\Google\Chrome\Application\chrome.exe
C:\Users\%USERNAME%\AppData\Local\Google\Chrome\Application\chrome.exe
```

### **Check if Chrome is Installed:**

```powershell
# Check common locations
Test-Path "C:\Program Files\Google\Chrome\Application\chrome.exe"
Test-Path "C:\Program Files (x86)\Google\Chrome\Application\chrome.exe"
Test-Path "$env:LOCALAPPDATA\Google\Chrome\Application\chrome.exe"
```

## 🧪 **Test the Fix**

After adding Chrome to PATH:

```powershell
# Test Chrome accessibility
chrome --version

# Should output something like:
# Google Chrome 120.0.6099.109
```

## 🚀 **Alternative: Use Full Path in Scripts**

If you don't want to modify PATH, you can use the full path directly:

```powershell
# Instead of: chrome --remote-debugging-port=9222
# Use: "C:\Program Files\Google\Chrome\Application\chrome.exe" --remote-debugging-port=9222
```

## 🔄 **Complete Setup with PATH Fix**

### **Step 1: Fix Chrome PATH**
```powershell
# Run the PATH fix script
.\browsertools-mcp\fix-chrome-path.ps1
```

### **Step 2: Start Chrome with Debugging**
```powershell
# Use the fixed script that handles PATH issues
.\browsertools-mcp\start-chrome-debug-fixed.ps1
```

### **Step 3: Verify Setup**
1. **Visit:** `http://localhost:9222`
2. **Should see:** Chrome DevTools interface
3. **Look for:** Your browser tab in the list

## 🎯 **Troubleshooting**

### **Issue: "Chrome not found" still appears**
- **Solution:** Use the `start-chrome-debug-fixed.ps1` script instead
- **Why:** It uses full paths instead of relying on PATH

### **Issue: "Access denied" when modifying PATH**
- **Solution:** Run PowerShell as Administrator
- **Alternative:** Use the fixed scripts that don't require PATH

### **Issue: Chrome starts but DevTools not accessible**
- **Solution:** Wait 5-10 seconds after Chrome starts
- **Check:** Visit `http://localhost:9222` in another browser

### **Issue: Port 9222 already in use**
- **Solution:** Close all Chrome instances first
- **Command:** `Get-Process chrome | Stop-Process -Force`

## 📋 **Quick Reference**

### **Chrome PATH Locations:**
- **64-bit:** `C:\Program Files\Google\Chrome\Application`
- **32-bit:** `C:\Program Files (x86)\Google\Chrome\Application`
- **User install:** `%LOCALAPPDATA%\Google\Chrome\Application`

### **Fixed Scripts:**
- **PowerShell:** `start-chrome-debug-fixed.ps1`
- **Batch:** `start-chrome-debug-fixed.cmd`
- **PATH Fix:** `fix-chrome-path.ps1`

### **Test Commands:**
```powershell
chrome --version                    # Test Chrome accessibility
chrome --remote-debugging-port=9222 # Start with debugging
```

## 🎉 **Success Indicators**

You'll know it's working when:

✅ Chrome starts with debugging enabled  
✅ `chrome --version` works in PowerShell  
✅ `http://localhost:9222` shows DevTools interface  
✅ MCP server can connect to Chrome  
✅ AI agent can execute browser commands  

---

## 🚀 **Ready to Go!**

Use the fixed scripts (`start-chrome-debug-fixed.ps1` or `start-chrome-debug-fixed.cmd`) to bypass PATH issues entirely. These scripts automatically find Chrome and use the full path, so you don't need to modify your system PATH.

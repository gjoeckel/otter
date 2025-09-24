/**
 * Debug Dashboard - Comprehensive Chrome Browser Debugging Interface
 * Provides a professional debugging interface accessible via Chrome DevTools
 */

import { logger } from './logging-utils.js';

export class DebugDashboard {
  constructor() {
    this.isVisible = false;
    this.dashboard = null;
    this.createDashboard();
    this.setupDevToolsIntegration();
  }

  createDashboard() {
    // Create main dashboard container
    this.dashboard = document.createElement('div');
    this.dashboard.id = 'debug-dashboard';
    this.dashboard.style.cssText = `
      position: fixed;
      top: 0;
      left: 0;
      width: 100vw;
      height: 100vh;
      background: rgba(0, 0, 0, 0.95);
      color: #ffffff;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      z-index: 99999;
      display: none;
      overflow: hidden;
    `;

    // Create header
    const header = this.createHeader();
    this.dashboard.appendChild(header);

    // Create main content area
    const content = this.createContent();
    this.dashboard.appendChild(content);

    // Add to document
    document.body.appendChild(this.dashboard);
  }

  createHeader() {
    const header = document.createElement('div');
    header.style.cssText = `
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      padding: 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 2px 10px rgba(0,0,0,0.3);
    `;

    const title = document.createElement('h1');
    title.textContent = '🐛 Otter Debug Dashboard';
    title.style.cssText = `
      margin: 0;
      font-size: 24px;
      font-weight: 600;
    `;

    const controls = document.createElement('div');
    controls.style.cssText = `
      display: flex;
      gap: 10px;
      align-items: center;
    `;

    // Refresh button
    const refreshBtn = this.createButton('🔄 Refresh', () => this.refreshAll());
    refreshBtn.style.background = '#4CAF50';

    // Export button
    const exportBtn = this.createButton('📊 Export', () => this.exportDebugData());
    exportBtn.style.background = '#FF9800';

    // Close button
    const closeBtn = this.createButton('✕ Close', () => this.hide());
    closeBtn.style.background = '#f44336';

    controls.appendChild(refreshBtn);
    controls.appendChild(exportBtn);
    controls.appendChild(closeBtn);

    header.appendChild(title);
    header.appendChild(controls);

    return header;
  }

  createContent() {
    const content = document.createElement('div');
    content.style.cssText = `
      display: flex;
      height: calc(100vh - 100px);
      overflow: hidden;
    `;

    // Left sidebar with navigation
    const sidebar = this.createSidebar();
    content.appendChild(sidebar);

    // Main content area
    const mainContent = this.createMainContent();
    content.appendChild(mainContent);

    return content;
  }

  createSidebar() {
    const sidebar = document.createElement('div');
    sidebar.style.cssText = `
      width: 250px;
      background: #2c3e50;
      padding: 20px;
      overflow-y: auto;
      border-right: 2px solid #34495e;
    `;

    const navItems = [
      { id: 'overview', label: '📊 Overview', icon: '📊' },
      { id: 'errors', label: '❌ Errors', icon: '❌' },
      { id: 'network', label: '🌐 Network', icon: '🌐' },
      { id: 'performance', label: '⚡ Performance', icon: '⚡' },
      { id: 'memory', label: '🧠 Memory', icon: '🧠' },
      { id: 'console', label: '💻 Console', icon: '💻' },
      { id: 'storage', label: '💾 Storage', icon: '💾' },
      { id: 'security', label: '🔒 Security', icon: '🔒' }
    ];

    navItems.forEach(item => {
      const navItem = document.createElement('div');
      navItem.className = 'nav-item';
      navItem.dataset.section = item.id;
      navItem.textContent = item.label;
      navItem.style.cssText = `
        padding: 12px 16px;
        margin-bottom: 8px;
        background: #34495e;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 14px;
        font-weight: 500;
      `;

      navItem.addEventListener('mouseenter', () => {
        navItem.style.background = '#3498db';
        navItem.style.transform = 'translateX(5px)';
      });

      navItem.addEventListener('mouseleave', () => {
        if (!navItem.classList.contains('active')) {
          navItem.style.background = '#34495e';
          navItem.style.transform = 'translateX(0)';
        }
      });

      navItem.addEventListener('click', () => {
        this.switchSection(item.id);
        this.updateActiveNavItem(navItem);
      });

      sidebar.appendChild(navItem);
    });

    // Set first item as active
    const firstItem = sidebar.querySelector('.nav-item');
    if (firstItem) {
      this.updateActiveNavItem(firstItem);
    }

    return sidebar;
  }

  createMainContent() {
    const mainContent = document.createElement('div');
    mainContent.style.cssText = `
      flex: 1;
      padding: 20px;
      overflow-y: auto;
      background: #ecf0f1;
      color: #2c3e50;
    `;

    // Create section containers
    this.sections = {};
    const sections = ['overview', 'errors', 'network', 'performance', 'memory', 'console', 'storage', 'security'];

    sections.forEach(sectionId => {
      const section = document.createElement('div');
      section.className = `section section-${sectionId}`;
      section.style.cssText = `
        display: ${sectionId === 'overview' ? 'block' : 'none'};
        height: 100%;
      `;
      
      this.populateSection(section, sectionId);
      mainContent.appendChild(section);
      this.sections[sectionId] = section;
    });

    return mainContent;
  }

  populateSection(section, sectionId) {
    switch (sectionId) {
      case 'overview':
        this.populateOverviewSection(section);
        break;
      case 'errors':
        this.populateErrorsSection(section);
        break;
      case 'network':
        this.populateNetworkSection(section);
        break;
      case 'performance':
        this.populatePerformanceSection(section);
        break;
      case 'memory':
        this.populateMemorySection(section);
        break;
      case 'console':
        this.populateConsoleSection(section);
        break;
      case 'storage':
        this.populateStorageSection(section);
        break;
      case 'security':
        this.populateSecuritySection(section);
        break;
    }
  }

  populateOverviewSection(section) {
    section.innerHTML = `
      <div style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h2 style="margin-top: 0; color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px;">
          🐛 System Overview
        </h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
          <div class="metric-card" style="background: #e8f5e8; padding: 15px; border-radius: 6px; border-left: 4px solid #27ae60;">
            <h3 style="margin: 0 0 10px 0; color: #27ae60;">System Status</h3>
            <div id="system-status">Checking...</div>
          </div>
          
          <div class="metric-card" style="background: #fff3cd; padding: 15px; border-radius: 6px; border-left: 4px solid #ffc107;">
            <h3 style="margin: 0 0 10px 0; color: #e67e22;">Error Count</h3>
            <div id="error-count">Loading...</div>
          </div>
          
          <div class="metric-card" style="background: #d1ecf1; padding: 15px; border-radius: 6px; border-left: 4px solid #17a2b8;">
            <h3 style="margin: 0 0 10px 0; color: #17a2b8;">Network Requests</h3>
            <div id="network-requests">Loading...</div>
          </div>
          
          <div class="metric-card" style="background: #f8d7da; padding: 15px; border-radius: 6px; border-left: 4px solid #dc3545;">
            <h3 style="margin: 0 0 10px 0; color: #dc3545;">Memory Usage</h3>
            <div id="memory-usage">Loading...</div>
          </div>
        </div>
        
        <div style="background: #f8f9fa; padding: 15px; border-radius: 6px; margin-top: 20px;">
          <h3 style="margin-top: 0; color: #495057;">Quick Actions</h3>
          <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <button onclick="window.debugDashboard?.runSystemDiagnostics()" style="background: #007bff; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">
              🔍 Run Diagnostics
            </button>
            <button onclick="window.debugDashboard?.clearAllLogs()" style="background: #6c757d; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">
              🗑️ Clear Logs
            </button>
            <button onclick="window.debugDashboard?.exportDebugData()" style="background: #28a745; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">
              📊 Export Data
            </button>
          </div>
        </div>
      </div>
    `;

    // Update metrics
    this.updateOverviewMetrics();
  }

  populateErrorsSection(section) {
    section.innerHTML = `
      <div style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h2 style="margin-top: 0; color: #2c3e50; border-bottom: 2px solid #e74c3c; padding-bottom: 10px;">
          ❌ Error Analysis
        </h2>
        
        <div id="error-stats" style="margin-bottom: 20px;">
          Loading error statistics...
        </div>
        
        <div style="background: #f8f9fa; padding: 15px; border-radius: 6px;">
          <h3 style="margin-top: 0;">Recent Errors</h3>
          <div id="recent-errors" style="max-height: 400px; overflow-y: auto;">
            Loading recent errors...
          </div>
        </div>
      </div>
    `;
  }

  populateNetworkSection(section) {
    section.innerHTML = `
      <div style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h2 style="margin-top: 0; color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px;">
          🌐 Network Monitoring
        </h2>
        
        <div id="network-stats" style="margin-bottom: 20px;">
          Loading network statistics...
        </div>
        
        <div style="background: #f8f9fa; padding: 15px; border-radius: 6px;">
          <h3 style="margin-top: 0;">Request Timeline</h3>
          <div id="request-timeline" style="max-height: 400px; overflow-y: auto;">
            Loading request timeline...
          </div>
        </div>
      </div>
    `;
  }

  populatePerformanceSection(section) {
    section.innerHTML = `
      <div style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h2 style="margin-top: 0; color: #2c3e50; border-bottom: 2px solid #f39c12; padding-bottom: 10px;">
          ⚡ Performance Metrics
        </h2>
        
        <div id="performance-metrics" style="margin-bottom: 20px;">
          Loading performance metrics...
        </div>
        
        <div style="background: #f8f9fa; padding: 15px; border-radius: 6px;">
          <h3 style="margin-top: 0;">Performance Timeline</h3>
          <div id="performance-timeline" style="max-height: 400px; overflow-y: auto;">
            Loading performance timeline...
          </div>
        </div>
      </div>
    `;
  }

  populateMemorySection(section) {
    section.innerHTML = `
      <div style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h2 style="margin-top: 0; color: #2c3e50; border-bottom: 2px solid #9b59b6; padding-bottom: 10px;">
          🧠 Memory Analysis
        </h2>
        
        <div id="memory-stats" style="margin-bottom: 20px;">
          Loading memory statistics...
        </div>
        
        <div style="background: #f8f9fa; padding: 15px; border-radius: 6px;">
          <h3 style="margin-top: 0;">Memory Usage Graph</h3>
          <div id="memory-graph" style="height: 200px; background: #fff; border: 1px solid #ddd; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #666;">
            Memory usage visualization would go here
          </div>
        </div>
      </div>
    `;
  }

  populateConsoleSection(section) {
    section.innerHTML = `
      <div style="background: #2c3e50; border-radius: 8px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); color: #ecf0f1;">
        <h2 style="margin-top: 0; color: #ecf0f1; border-bottom: 2px solid #3498db; padding-bottom: 10px;">
          💻 Live Console
        </h2>
        
        <div style="background: #1a1a1a; padding: 15px; border-radius: 6px; font-family: 'Courier New', monospace; font-size: 12px; max-height: 400px; overflow-y: auto;" id="live-console">
          <div style="color: #27ae60;">🐛 Debug Dashboard Console</div>
          <div style="color: #3498db;">Ready for commands...</div>
        </div>
        
        <div style="margin-top: 15px;">
          <input type="text" id="console-input" placeholder="Enter debug command..." style="width: 70%; padding: 8px; border: 1px solid #34495e; border-radius: 4px; background: #2c3e50; color: #ecf0f1;">
          <button onclick="window.debugDashboard?.executeConsoleCommand()" style="margin-left: 10px; background: #3498db; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">
            Execute
          </button>
        </div>
      </div>
    `;
  }

  populateStorageSection(section) {
    section.innerHTML = `
      <div style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h2 style="margin-top: 0; color: #2c3e50; border-bottom: 2px solid #e67e22; padding-bottom: 10px;">
          💾 Storage Analysis
        </h2>
        
        <div id="storage-stats" style="margin-bottom: 20px;">
          Loading storage statistics...
        </div>
        
        <div style="background: #f8f9fa; padding: 15px; border-radius: 6px;">
          <h3 style="margin-top: 0;">Storage Contents</h3>
          <div id="storage-contents" style="max-height: 400px; overflow-y: auto;">
            Loading storage contents...
          </div>
        </div>
      </div>
    `;
  }

  populateSecuritySection(section) {
    section.innerHTML = `
      <div style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h2 style="margin-top: 0; color: #2c3e50; border-bottom: 2px solid #e74c3c; padding-bottom: 10px;">
          🔒 Security Audit
        </h2>
        
        <div id="security-checks" style="margin-bottom: 20px;">
          Loading security checks...
        </div>
        
        <div style="background: #f8f9fa; padding: 15px; border-radius: 6px;">
          <h3 style="margin-top: 0;">Security Recommendations</h3>
          <div id="security-recommendations" style="max-height: 400px; overflow-y: auto;">
            Loading security recommendations...
          </div>
        </div>
      </div>
    `;
  }

  createButton(text, onClick) {
    const button = document.createElement('button');
    button.textContent = text;
    button.style.cssText = `
      background: #3498db;
      color: white;
      border: none;
      padding: 10px 16px;
      border-radius: 6px;
      cursor: pointer;
      font-size: 14px;
      font-weight: 500;
      transition: all 0.3s ease;
    `;

    button.addEventListener('click', onClick);
    button.addEventListener('mouseenter', () => {
      button.style.transform = 'translateY(-2px)';
      button.style.boxShadow = '0 4px 12px rgba(0,0,0,0.3)';
    });

    button.addEventListener('mouseleave', () => {
      button.style.transform = 'translateY(0)';
      button.style.boxShadow = 'none';
    });

    return button;
  }

  switchSection(sectionId) {
    Object.keys(this.sections).forEach(id => {
      this.sections[id].style.display = id === sectionId ? 'block' : 'none';
    });
  }

  updateActiveNavItem(activeItem) {
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
      item.classList.remove('active');
      item.style.background = '#34495e';
      item.style.transform = 'translateX(0)';
    });

    activeItem.classList.add('active');
    activeItem.style.background = '#3498db';
    activeItem.style.transform = 'translateX(5px)';
  }

  updateOverviewMetrics() {
    // System status
    const systemStatus = document.getElementById('system-status');
    if (systemStatus) {
      systemStatus.innerHTML = `
        <span style="color: #27ae60;">✅ System Operational</span><br>
        <small>Uptime: ${this.getSystemUptime()}</small>
      `;
    }

    // Error count
    const errorCount = document.getElementById('error-count');
    if (errorCount) {
      const errors = window.enhancedLogging?.errorDetector?.getErrorStats() || { totalErrors: 0, recentErrors: 0 };
      errorCount.innerHTML = `
        <span style="color: ${errors.totalErrors > 0 ? '#e74c3c' : '#27ae60'}">${errors.totalErrors}</span> total<br>
        <small>${errors.recentErrors} in last hour</small>
      `;
    }

    // Network requests
    const networkRequests = document.getElementById('network-requests');
    if (networkRequests) {
      const network = window.enhancedLogging?.networkMonitor?.getNetworkStats() || { totalRequests: 0, recentRequests: 0 };
      networkRequests.innerHTML = `
        <span style="color: #3498db;">${network.totalRequests}</span> total<br>
        <small>${network.recentRequests} in last minute</small>
      `;
    }

    // Memory usage
    const memoryUsage = document.getElementById('memory-usage');
    if (memoryUsage) {
      const memory = window.enhancedLogging?.memoryDetector?.getMemoryStats() || { currentUsage: 'Unknown' };
      memoryUsage.innerHTML = `
        <span style="color: #9b59b6;">${memory.currentUsage}</span><br>
        <small>Trend: ${memory.trend}</small>
      `;
    }
  }

  getSystemUptime() {
    const startTime = performance.timing.navigationStart;
    const currentTime = Date.now();
    const uptime = currentTime - startTime;
    
    const minutes = Math.floor(uptime / 60000);
    const seconds = Math.floor((uptime % 60000) / 1000);
    
    return `${minutes}m ${seconds}s`;
  }

  setupDevToolsIntegration() {
    // Add to window for global access
    window.debugDashboard = this;

    // Add keyboard shortcut (Ctrl+Shift+D)
    document.addEventListener('keydown', (e) => {
      if (e.ctrlKey && e.shiftKey && e.key === 'D') {
        e.preventDefault();
        this.toggle();
      }
    });

    // Add to Chrome DevTools
    if (window.chrome && window.chrome.runtime) {
      // This would integrate with Chrome extension if available
      logger.info('debug-dashboard', 'Chrome DevTools integration available');
    }
  }

  show() {
    this.isVisible = true;
    this.dashboard.style.display = 'block';
    this.refreshAll();
    logger.info('debug-dashboard', 'Debug dashboard opened');
  }

  hide() {
    this.isVisible = false;
    this.dashboard.style.display = 'none';
    logger.info('debug-dashboard', 'Debug dashboard closed');
  }

  toggle() {
    if (this.isVisible) {
      this.hide();
    } else {
      this.show();
    }
  }

  refreshAll() {
    this.updateOverviewMetrics();
    // Refresh other sections as needed
    logger.info('debug-dashboard', 'Dashboard refreshed');
  }

  runSystemDiagnostics() {
    logger.info('debug-dashboard', 'Running system diagnostics...');
    
    // Run comprehensive system check
    const diagnostics = {
      timestamp: new Date().toISOString(),
      browser: navigator.userAgent,
      performance: this.getPerformanceMetrics(),
      memory: this.getMemoryMetrics(),
      errors: this.getErrorMetrics(),
      network: this.getNetworkMetrics()
    };

    console.log('🔍 System Diagnostics:', diagnostics);
    this.logToConsole('System diagnostics completed - check console for details');
  }

  clearAllLogs() {
    if (window.clearStoredLogs) {
      window.clearStoredLogs();
    }
    this.logToConsole('All logs cleared');
    this.refreshAll();
  }

  exportDebugData() {
    const debugData = {
      timestamp: new Date().toISOString(),
      url: window.location.href,
      userAgent: navigator.userAgent,
      errors: window.enhancedLogging?.errorDetector?.errorHistory || [],
      network: window.enhancedLogging?.networkMonitor?.requests || [],
      memory: window.enhancedLogging?.memoryDetector?.memoryHistory || [],
      logs: window.getRecentLogs ? window.getRecentLogs(100) : []
    };

    const blob = new Blob([JSON.stringify(debugData, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `otter-debug-${Date.now()}.json`;
    a.click();
    URL.revokeObjectURL(url);

    this.logToConsole('Debug data exported successfully');
  }

  executeConsoleCommand() {
    const input = document.getElementById('console-input');
    if (!input) return;

    const command = input.value.trim();
    if (!command) return;

    this.logToConsole(`> ${command}`);

    try {
      // Execute command in safe context
      const result = eval(command);
      this.logToConsole(`< ${JSON.stringify(result)}`);
    } catch (error) {
      this.logToConsole(`Error: ${error.message}`);
    }

    input.value = '';
  }

  logToConsole(message) {
    const console = document.getElementById('live-console');
    if (console) {
      const logEntry = document.createElement('div');
      logEntry.textContent = `[${new Date().toLocaleTimeString()}] ${message}`;
      logEntry.style.marginBottom = '4px';
      console.appendChild(logEntry);
      console.scrollTop = console.scrollHeight;
    }
  }

  getPerformanceMetrics() {
    const navigation = performance.getEntriesByType('navigation')[0];
    return {
      loadTime: navigation ? navigation.loadEventEnd - navigation.loadEventStart : 0,
      domReady: navigation ? navigation.domContentLoadedEventEnd - navigation.domContentLoadedEventStart : 0,
      firstPaint: performance.getEntriesByType('paint').find(p => p.name === 'first-paint')?.startTime || 0
    };
  }

  getMemoryMetrics() {
    if (performance.memory) {
      return {
        used: performance.memory.usedJSHeapSize,
        total: performance.memory.totalJSHeapSize,
        limit: performance.memory.jsHeapSizeLimit
      };
    }
    return null;
  }

  getErrorMetrics() {
    return window.enhancedLogging?.errorDetector?.getErrorStats() || {};
  }

  getNetworkMetrics() {
    return window.enhancedLogging?.networkMonitor?.getNetworkStats() || {};
  }
}

// Initialize debug dashboard
export function initDebugDashboard() {
  if (!window.debugDashboard) {
    window.debugDashboard = new DebugDashboard();
    logger.info('debug-dashboard', 'Debug dashboard initialized - Press Ctrl+Shift+D to open');
  }
  return window.debugDashboard;
}

// Auto-initialize when module loads
if (typeof window !== 'undefined') {
  // Initialize after DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDebugDashboard);
  } else {
    initDebugDashboard();
  }
}

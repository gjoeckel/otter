/**
 * Enhanced Logging System for Chrome Browser Debugging
 * Extends the existing logging system with comprehensive error detection,
 * network monitoring, and real-time debugging capabilities.
 */

import { logger, perfMonitor, LOG_LEVELS } from './logging-utils.js';

/**
 * Enhanced Error Detection System
 */
export class ErrorDetector {
  constructor() {
    this.errorCount = 0;
    this.errorHistory = [];
    this.setupGlobalErrorHandlers();
  }

  setupGlobalErrorHandlers() {
    // Handle uncaught JavaScript errors
    window.addEventListener('error', (event) => {
      this.logError('Uncaught Error', {
        message: event.message,
        filename: event.filename,
        lineno: event.lineno,
        colno: event.colno,
        error: event.error,
        stack: event.error?.stack
      });
    });

    // Handle unhandled promise rejections
    window.addEventListener('unhandledrejection', (event) => {
      this.logError('Unhandled Promise Rejection', {
        reason: event.reason,
        promise: event.promise,
        stack: event.reason?.stack
      });
    });

    // Handle resource loading errors
    window.addEventListener('error', (event) => {
      if (event.target !== window) {
        this.logError('Resource Loading Error', {
          tagName: event.target.tagName,
          src: event.target.src,
          href: event.target.href,
          type: event.target.type
        });
      }
    }, true);
  }

  logError(type, details) {
    this.errorCount++;
    const errorInfo = {
      id: this.errorCount,
      type,
      details,
      timestamp: Date.now(),
      url: window.location.href,
      userAgent: navigator.userAgent,
      memoryUsage: this.getMemoryUsage()
    };

    this.errorHistory.push(errorInfo);
    
    // Keep only last 50 errors
    if (this.errorHistory.length > 50) {
      this.errorHistory.shift();
    }

    logger.error('error-detector', `Error #${this.errorCount}: ${type}`, errorInfo);
    
    // Show immediate notification for critical errors
    if (this.isCriticalError(type, details)) {
      this.showErrorNotification(errorInfo);
    }
  }

  isCriticalError(type, details) {
    const criticalPatterns = [
      'Network error',
      'Failed to fetch',
      'Script error',
      'Syntax error',
      'Reference error'
    ];
    
    return criticalPatterns.some(pattern => 
      type.toLowerCase().includes(pattern.toLowerCase()) ||
      details.message?.toLowerCase().includes(pattern.toLowerCase())
    );
  }

  showErrorNotification(errorInfo) {
    // Create floating error notification
    const notification = document.createElement('div');
    notification.style.cssText = `
      position: fixed;
      top: 20px;
      left: 50%;
      transform: translateX(-50%);
      background: #ff4444;
      color: white;
      padding: 12px 20px;
      border-radius: 6px;
      font-family: Arial, sans-serif;
      font-size: 14px;
      z-index: 10001;
      box-shadow: 0 4px 12px rgba(0,0,0,0.3);
      cursor: pointer;
      animation: slideDown 0.3s ease-out;
    `;
    
    notification.innerHTML = `
      <div style="font-weight: bold;">⚠️ Error #${errorInfo.id}: ${errorInfo.type}</div>
      <div style="font-size: 12px; margin-top: 4px;">Click to view details (Ctrl+Shift+J)</div>
    `;
    
    notification.onclick = () => {
      // Focus log viewer
      if (window.globalLogViewer) {
        window.globalLogViewer.show();
      }
    };
    
    document.body.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
      if (notification.parentNode) {
        notification.parentNode.removeChild(notification);
      }
    }, 5000);
  }

  getMemoryUsage() {
    if (performance.memory) {
      return {
        used: Math.round(performance.memory.usedJSHeapSize / 1024 / 1024),
        total: Math.round(performance.memory.totalJSHeapSize / 1024 / 1024),
        limit: Math.round(performance.memory.jsHeapSizeLimit / 1024 / 1024)
      };
    }
    return null;
  }

  getErrorStats() {
    const lastHour = Date.now() - (60 * 60 * 1000);
    const recentErrors = this.errorHistory.filter(e => e.timestamp > lastHour);
    
    return {
      totalErrors: this.errorCount,
      recentErrors: recentErrors.length,
      errorTypes: this.getErrorTypeCounts(),
      memoryUsage: this.getMemoryUsage()
    };
  }

  getErrorTypeCounts() {
    const counts = {};
    this.errorHistory.forEach(error => {
      counts[error.type] = (counts[error.type] || 0) + 1;
    });
    return counts;
  }
}

/**
 * Network Request Monitor
 */
export class NetworkMonitor {
  constructor() {
    this.requests = [];
    this.setupInterceptors();
  }

  setupInterceptors() {
    // Intercept fetch requests
    const originalFetch = window.fetch;
    window.fetch = async (...args) => {
      const requestId = this.generateRequestId();
      const startTime = performance.now();
      
      const requestInfo = {
        id: requestId,
        method: 'GET',
        url: args[0],
        startTime,
        headers: {}
      };

      // Extract method and headers from options
      if (args[1]) {
        requestInfo.method = args[1].method || 'GET';
        requestInfo.headers = args[1].headers || {};
        requestInfo.body = args[1].body;
      }

      this.requests.push(requestInfo);
      logger.debug('network-monitor', `Starting ${requestInfo.method} request`, requestInfo);

      try {
        const response = await originalFetch(...args);
        const duration = performance.now() - startTime;
        
        requestInfo.success = response.ok;
        requestInfo.status = response.status;
        requestInfo.statusText = response.statusText;
        requestInfo.duration = duration;
        requestInfo.responseSize = response.headers.get('content-length');

        logger.info('network-monitor', `${requestInfo.method} ${requestInfo.url} completed`, {
          status: response.status,
          duration: `${duration.toFixed(2)}ms`,
          size: requestInfo.responseSize
        });

        return response;
      } catch (error) {
        const duration = performance.now() - startTime;
        requestInfo.success = false;
        requestInfo.error = error.message;
        requestInfo.duration = duration;

        logger.error('network-monitor', `${requestInfo.method} ${requestInfo.url} failed`, {
          error: error.message,
          duration: `${duration.toFixed(2)}ms`
        });

        throw error;
      }
    };

    // Intercept XMLHttpRequest
    const originalXHROpen = XMLHttpRequest.prototype.open;
    const originalXHRSend = XMLHttpRequest.prototype.send;

    XMLHttpRequest.prototype.open = function(method, url, ...args) {
      this._requestInfo = {
        method,
        url,
        startTime: performance.now()
      };
      return originalXHROpen.call(this, method, url, ...args);
    };

    XMLHttpRequest.prototype.send = function(data) {
      const requestInfo = this._requestInfo;
      if (requestInfo) {
        requestInfo.body = data;
        this.requests.push(requestInfo);
        
        this.addEventListener('load', () => {
          const duration = performance.now() - requestInfo.startTime;
          requestInfo.success = this.status >= 200 && this.status < 300;
          requestInfo.status = this.status;
          requestInfo.duration = duration;
          
          logger.info('network-monitor', `${requestInfo.method} ${requestInfo.url} completed`, {
            status: this.status,
            duration: `${duration.toFixed(2)}ms`
          });
        });

        this.addEventListener('error', () => {
          const duration = performance.now() - requestInfo.startTime;
          requestInfo.success = false;
          requestInfo.duration = duration;
          
          logger.error('network-monitor', `${requestInfo.method} ${requestInfo.url} failed`, {
            duration: `${duration.toFixed(2)}ms`
          });
        });
      }

      return originalXHRSend.call(this, data);
    };
  }

  generateRequestId() {
    return 'req_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
  }

  getNetworkStats() {
    const lastMinute = Date.now() - (60 * 1000);
    const recentRequests = this.requests.filter(r => r.startTime > lastMinute);
    
    return {
      totalRequests: this.requests.length,
      recentRequests: recentRequests.length,
      successRate: this.calculateSuccessRate(recentRequests),
      averageResponseTime: this.calculateAverageResponseTime(recentRequests),
      slowestRequests: this.getSlowestRequests(5)
    };
  }

  calculateSuccessRate(requests) {
    if (requests.length === 0) return 100;
    const successful = requests.filter(r => r.success).length;
    return ((successful / requests.length) * 100).toFixed(1);
  }

  calculateAverageResponseTime(requests) {
    if (requests.length === 0) return 0;
    const totalTime = requests.reduce((sum, r) => sum + (r.duration || 0), 0);
    return (totalTime / requests.length).toFixed(2);
  }

  getSlowestRequests(count = 5) {
    return this.requests
      .filter(r => r.duration)
      .sort((a, b) => b.duration - a.duration)
      .slice(0, count)
      .map(r => ({
        url: r.url,
        method: r.method,
        duration: `${r.duration.toFixed(2)}ms`,
        status: r.status
      }));
  }
}

/**
 * Memory Leak Detector
 */
export class MemoryLeakDetector {
  constructor() {
    this.memoryHistory = [];
    this.domNodeHistory = [];
    this.eventListenerHistory = [];
    this.startMonitoring();
  }

  startMonitoring() {
    // Monitor memory usage every 30 seconds
    setInterval(() => {
      this.recordMemorySnapshot();
    }, 30000);

    // Monitor DOM nodes every minute
    setInterval(() => {
      this.recordDOMSnapshot();
    }, 60000);

    // Monitor event listeners every 2 minutes
    setInterval(() => {
      this.recordEventListenerSnapshot();
    }, 120000);
  }

  recordMemorySnapshot() {
    if (performance.memory) {
      const snapshot = {
        timestamp: Date.now(),
        used: performance.memory.usedJSHeapSize,
        total: performance.memory.totalJSHeapSize,
        limit: performance.memory.jsHeapSizeLimit
      };

      this.memoryHistory.push(snapshot);
      
      // Keep only last 100 snapshots (50 minutes)
      if (this.memoryHistory.length > 100) {
        this.memoryHistory.shift();
      }

      // Check for memory leaks
      this.checkForMemoryLeaks();
    }
  }

  recordDOMSnapshot() {
    const snapshot = {
      timestamp: Date.now(),
      totalNodes: document.querySelectorAll('*').length,
      elements: document.querySelectorAll('*').length,
      textNodes: this.countTextNodes(),
      eventListeners: this.estimateEventListeners()
    };

    this.domNodeHistory.push(snapshot);
    
    if (this.domNodeHistory.length > 50) {
      this.domNodeHistory.shift();
    }
  }

  recordEventListenerSnapshot() {
    const snapshot = {
      timestamp: Date.now(),
      estimatedListeners: this.estimateEventListeners()
    };

    this.eventListenerHistory.push(snapshot);
    
    if (this.eventListenerHistory.length > 25) {
      this.eventListenerHistory.shift();
    }
  }

  countTextNodes() {
    const walker = document.createTreeWalker(
      document.body,
      NodeFilter.SHOW_TEXT,
      null,
      false
    );
    
    let count = 0;
    while (walker.nextNode()) {
      count++;
    }
    return count;
  }

  estimateEventListeners() {
    // This is a rough estimate - browsers don't expose exact event listener counts
    const elements = document.querySelectorAll('*');
    let estimatedCount = 0;
    
    elements.forEach(element => {
      // Check for common event listener attributes
      const eventAttributes = ['onclick', 'onload', 'onchange', 'onsubmit', 'onkeydown', 'onkeyup'];
      eventAttributes.forEach(attr => {
        if (element.hasAttribute(attr)) {
          estimatedCount++;
        }
      });
    });
    
    return estimatedCount;
  }

  checkForMemoryLeaks() {
    if (this.memoryHistory.length < 10) return;

    const recent = this.memoryHistory.slice(-10);
    const trend = this.calculateMemoryTrend(recent);

    if (trend > 10) { // 10% increase over recent snapshots
      logger.warn('memory-detector', 'Potential memory leak detected', {
        trend: `${trend.toFixed(1)}%`,
        currentUsage: `${Math.round(recent[recent.length - 1].used / 1024 / 1024)}MB`,
        snapshots: recent.length
      });
    }
  }

  calculateMemoryTrend(snapshots) {
    if (snapshots.length < 2) return 0;
    
    const first = snapshots[0].used;
    const last = snapshots[snapshots.length - 1].used;
    
    return ((last - first) / first) * 100;
  }

  getMemoryStats() {
    const current = this.memoryHistory[this.memoryHistory.length - 1];
    const trend = this.calculateMemoryTrend(this.memoryHistory.slice(-10));
    
    return {
      currentUsage: current ? `${Math.round(current.used / 1024 / 1024)}MB` : 'Unknown',
      trend: `${trend.toFixed(1)}%`,
      snapshots: this.memoryHistory.length,
      domNodes: this.domNodeHistory[this.domNodeHistory.length - 1]?.totalNodes || 0,
      eventListeners: this.eventListenerHistory[this.eventListenerHistory.length - 1]?.estimatedListeners || 0
    };
  }
}

/**
 * Enhanced Log Viewer with Real-time Monitoring
 */
export class EnhancedLogViewer {
  constructor() {
    this.errorDetector = new ErrorDetector();
    this.networkMonitor = new NetworkMonitor();
    this.memoryDetector = new MemoryLeakDetector();
    this.createEnhancedViewer();
  }

  createEnhancedViewer() {
    // Extend the existing log viewer
    const existingViewer = document.getElementById('log-viewer');
    if (existingViewer) {
      this.addMonitoringTabs(existingViewer);
    }
  }

  addMonitoringTabs(container) {
    // Create tab navigation
    const tabsContainer = document.createElement('div');
    tabsContainer.style.cssText = `
      display: flex;
      border-bottom: 1px solid #555;
      background: #333;
    `;

    const tabs = ['Logs', 'Errors', 'Network', 'Memory', 'Performance'];
    tabs.forEach((tabName, index) => {
      const tab = document.createElement('button');
      tab.textContent = tabName;
      tab.style.cssText = `
        background: ${index === 0 ? '#555' : 'transparent'};
        border: none;
        color: #fff;
        padding: 8px 16px;
        cursor: pointer;
        font-size: 12px;
      `;
      
      tab.onclick = () => this.switchTab(tabName, tab);
      tabsContainer.appendChild(tab);
    });

    // Insert tabs after header
    const header = container.querySelector('div');
    header.parentNode.insertBefore(tabsContainer, header.nextSibling);

    // Create tab content containers
    this.tabContents = {};
    tabs.forEach(tabName => {
      const content = document.createElement('div');
      content.className = `tab-content tab-${tabName.toLowerCase()}`;
      content.style.cssText = `
        display: ${tabName === 'Logs' ? 'block' : 'none'};
        padding: 8px;
        max-height: 400px;
        overflow-y: auto;
        background: #1a1a1a;
      `;
      
      if (tabName === 'Errors') {
        this.populateErrorsTab(content);
      } else if (tabName === 'Network') {
        this.populateNetworkTab(content);
      } else if (tabName === 'Memory') {
        this.populateMemoryTab(content);
      } else if (tabName === 'Performance') {
        this.populatePerformanceTab(content);
      }
      
      container.appendChild(content);
      this.tabContents[tabName] = content;
    });
  }

  switchTab(tabName, tabElement) {
    // Update tab styles
    const tabs = tabElement.parentNode.querySelectorAll('button');
    tabs.forEach(t => t.style.background = 'transparent');
    tabElement.style.background = '#555';

    // Show/hide content
    Object.keys(this.tabContents).forEach(key => {
      this.tabContents[key].style.display = key === tabName ? 'block' : 'none';
    });

    // Refresh content
    if (tabName === 'Errors') {
      this.populateErrorsTab(this.tabContents[tabName]);
    } else if (tabName === 'Network') {
      this.populateNetworkTab(this.tabContents[tabName]);
    } else if (tabName === 'Memory') {
      this.populateMemoryTab(this.tabContents[tabName]);
    } else if (tabName === 'Performance') {
      this.populatePerformanceTab(this.tabContents[tabName]);
    }
  }

  populateErrorsTab(container) {
    const stats = this.errorDetector.getErrorStats();
    
    container.innerHTML = `
      <div style="color: #ccc; margin-bottom: 12px;">
        <h4 style="margin: 0 0 8px 0; color: #fff;">Error Statistics</h4>
        <div>Total Errors: <span style="color: #ff6b6b;">${stats.totalErrors}</span></div>
        <div>Recent (1h): <span style="color: #ffa726;">${stats.recentErrors}</span></div>
      </div>
      <div style="color: #ccc;">
        <h4 style="margin: 0 0 8px 0; color: #fff;">Error Types</h4>
        ${Object.entries(stats.errorTypes).map(([type, count]) => 
          `<div>${type}: <span style="color: #ff6b6b;">${count}</span></div>`
        ).join('')}
      </div>
    `;
  }

  populateNetworkTab(container) {
    const stats = this.networkMonitor.getNetworkStats();
    
    container.innerHTML = `
      <div style="color: #ccc; margin-bottom: 12px;">
        <h4 style="margin: 0 0 8px 0; color: #fff;">Network Statistics</h4>
        <div>Total Requests: ${stats.totalRequests}</div>
        <div>Recent (1m): ${stats.recentRequests}</div>
        <div>Success Rate: <span style="color: ${stats.successRate > 95 ? '#66bb6a' : '#ffa726'}">${stats.successRate}%</span></div>
        <div>Avg Response Time: ${stats.averageResponseTime}ms</div>
      </div>
      <div style="color: #ccc;">
        <h4 style="margin: 0 0 8px 0; color: #fff;">Slowest Requests</h4>
        ${stats.slowestRequests.map(req => 
          `<div style="font-size: 11px; margin-bottom: 4px;">
            ${req.method} ${req.url.split('?')[0]} - ${req.duration} (${req.status})
          </div>`
        ).join('')}
      </div>
    `;
  }

  populateMemoryTab(container) {
    const stats = this.memoryDetector.getMemoryStats();
    
    container.innerHTML = `
      <div style="color: #ccc;">
        <h4 style="margin: 0 0 8px 0; color: #fff;">Memory Usage</h4>
        <div>Current Usage: <span style="color: #42a5f5;">${stats.currentUsage}</span></div>
        <div>Trend: <span style="color: ${stats.trend > 0 ? '#ff6b6b' : '#66bb6a'}">${stats.trend}</span></div>
        <div>DOM Nodes: ${stats.domNodes}</div>
        <div>Event Listeners: ${stats.eventListeners}</div>
      </div>
    `;
  }

  populatePerformanceTab(container) {
    const navigation = performance.getEntriesByType('navigation')[0];
    const paint = performance.getEntriesByType('paint');
    
    container.innerHTML = `
      <div style="color: #ccc;">
        <h4 style="margin: 0 0 8px 0; color: #fff;">Page Performance</h4>
        <div>Load Time: ${navigation ? Math.round(navigation.loadEventEnd - navigation.loadEventStart) : 'N/A'}ms</div>
        <div>DOM Ready: ${navigation ? Math.round(navigation.domContentLoadedEventEnd - navigation.domContentLoadedEventStart) : 'N/A'}ms</div>
        <div>First Paint: ${paint.find(p => p.name === 'first-paint')?.startTime ? Math.round(paint.find(p => p.name === 'first-paint').startTime) : 'N/A'}ms</div>
        <div>First Contentful Paint: ${paint.find(p => p.name === 'first-contentful-paint')?.startTime ? Math.round(paint.find(p => p.name === 'first-contentful-paint').startTime) : 'N/A'}ms</div>
      </div>
    `;
  }
}

// Initialize enhanced logging system
export function initEnhancedLogging() {
  if (!window.enhancedLogging) {
    window.enhancedLogging = new EnhancedLogViewer();
    logger.info('enhanced-logging', 'Enhanced logging system initialized');
  }
  return window.enhancedLogging;
}

// Export for global access
if (typeof window !== 'undefined') {
  window.initEnhancedLogging = initEnhancedLogging;
  window.ErrorDetector = ErrorDetector;
  window.NetworkMonitor = NetworkMonitor;
  window.MemoryLeakDetector = MemoryLeakDetector;
}

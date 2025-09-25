/**
 * Chrome DevTools Panel JavaScript
 * Handles the debug panel functionality within Chrome DevTools
 */

class DebugPanel {
  constructor() {
    this.isConnected = false;
    this.debugData = {};
    this.init();
  }

  init() {
    console.log('Debug panel initializing...');
    
    // Listen for messages from content script
    chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
      if (request.type === "UPDATE_DEBUG_DATA") {
        this.updateDebugData(request.data);
      }
    });

    // Request initial data from content script
    this.requestDebugData();
    
    // Set up periodic refresh
    setInterval(() => {
      this.requestDebugData();
    }, 5000);

    // Log initialization
    this.addLogEntry('Debug panel initialized', 'success');
  }

  requestDebugData() {
    chrome.tabs.query({active: true, currentWindow: true}, (tabs) => {
      if (tabs[0]) {
        chrome.tabs.sendMessage(tabs[0].id, {
          type: "GET_DEBUG_DATA"
        }, (response) => {
          if (chrome.runtime.lastError) {
            this.handleConnectionError();
          } else if (response) {
            this.updateDebugData(response.data);
            this.isConnected = true;
            this.updateConnectionStatus(true);
          }
        });
      }
    });
  }

  updateDebugData(data) {
    this.debugData = data;
    this.updateMetrics();
    this.updateRecentErrors();
  }

  updateMetrics() {
    // System status
    const systemStatus = document.getElementById('system-status');
    if (systemStatus) {
      systemStatus.innerHTML = `
        <span class="status-indicator status-online"></span>
        Online
      `;
    }

    // Error count
    const errorCount = document.getElementById('error-count');
    if (errorCount && this.debugData.errors) {
      const totalErrors = this.debugData.errors.totalErrors || 0;
      const recentErrors = this.debugData.errors.recentErrors || 0;
      
      errorCount.textContent = totalErrors;
      
      // Update card class based on error count
      const errorCard = document.getElementById('error-count-card');
      if (totalErrors === 0) {
        errorCard.className = 'metric-card success';
      } else if (recentErrors > 5) {
        errorCard.className = 'metric-card error';
      } else {
        errorCard.className = 'metric-card warning';
      }
    }

    // Network requests
    const networkRequests = document.getElementById('network-requests');
    if (networkRequests && this.debugData.network) {
      networkRequests.textContent = this.debugData.network.totalRequests || 0;
    }

    // Memory usage
    const memoryUsage = document.getElementById('memory-usage');
    if (memoryUsage && this.debugData.memory) {
      memoryUsage.textContent = this.debugData.memory.currentUsage || 'Unknown';
    }
  }

  updateRecentErrors() {
    const recentErrorsContainer = document.getElementById('recent-errors');
    if (!recentErrorsContainer) return;

    if (!this.debugData.errors || !this.debugData.errors.history || this.debugData.errors.history.length === 0) {
      recentErrorsContainer.innerHTML = '<div class="log-entry">No errors detected</div>';
      return;
    }

    const recentErrors = this.debugData.errors.history.slice(-10); // Last 10 errors
    recentErrorsContainer.innerHTML = recentErrors.map(error => `
      <div class="log-entry error">
        [${new Date(error.timestamp).toLocaleTimeString()}] ${error.type}: ${error.details.message || 'Unknown error'}
      </div>
    `).join('');
  }

  handleConnectionError() {
    this.isConnected = false;
    this.updateConnectionStatus(false);
    this.addLogEntry('Connection to page lost', 'warning');
  }

  updateConnectionStatus(connected) {
    const systemStatus = document.getElementById('system-status');
    const systemStatusCard = document.getElementById('system-status-card');
    
    if (connected) {
      systemStatus.innerHTML = '<span class="status-indicator status-online"></span>Online';
      systemStatusCard.className = 'metric-card success';
    } else {
      systemStatus.innerHTML = '<span class="status-indicator status-offline"></span>Offline';
      systemStatusCard.className = 'metric-card error';
    }
  }

  addLogEntry(message, type = 'info') {
    const logsContainer = document.getElementById('system-logs');
    if (!logsContainer) return;

    const logEntry = document.createElement('div');
    logEntry.className = `log-entry ${type}`;
    logEntry.textContent = `[${new Date().toLocaleTimeString()}] ${message}`;
    
    logsContainer.appendChild(logEntry);
    logsContainer.scrollTop = logsContainer.scrollHeight;

    // Keep only last 50 log entries
    while (logsContainer.children.length > 50) {
      logsContainer.removeChild(logsContainer.firstChild);
    }
  }
}

// Global functions for button actions
function refreshData() {
  debugPanel.requestDebugData();
  debugPanel.addLogEntry('Manual refresh requested', 'info');
}

function clearLogs() {
  chrome.tabs.query({active: true, currentWindow: true}, (tabs) => {
    if (tabs[0]) {
      chrome.tabs.sendMessage(tabs[0].id, {
        type: "CLEAR_LOGS"
      }, (response) => {
        if (response && response.success) {
          debugPanel.addLogEntry('Logs cleared successfully', 'success');
          debugPanel.updateRecentErrors();
        } else {
          debugPanel.addLogEntry('Failed to clear logs', 'error');
        }
      });
    }
  });
}

function exportData() {
  const exportData = {
    timestamp: new Date().toISOString(),
    url: chrome.tabs.query({active: true, currentWindow: true}, (tabs) => {
      return tabs[0]?.url;
    }),
    debugData: debugPanel.debugData
  };

  const blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `otter-debug-${Date.now()}.json`;
  a.click();
  URL.revokeObjectURL(url);

  debugPanel.addLogEntry('Debug data exported', 'success');
}

function runDiagnostics() {
  chrome.tabs.query({active: true, currentWindow: true}, (tabs) => {
    if (tabs[0]) {
      chrome.tabs.sendMessage(tabs[0].id, {
        type: "RUN_DIAGNOSTICS"
      }, (response) => {
        if (response && response.success) {
          debugPanel.addLogEntry('Diagnostics completed successfully', 'success');
        } else {
          debugPanel.addLogEntry('Diagnostics failed', 'error');
        }
      });
    }
  });
}

// Initialize debug panel
let debugPanel;
document.addEventListener('DOMContentLoaded', () => {
  debugPanel = new DebugPanel();
});

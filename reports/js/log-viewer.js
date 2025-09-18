/**
 * Log Viewer Utility
 * Provides a simple interface to view and manage application logs
 */

import { getRecentLogs, clearStoredLogs, logger } from './logging-utils.js';

export class LogViewer {
  constructor() {
    this.isVisible = false;
    this.logContainer = null;
    this.createLogViewer();
  }

  createLogViewer() {
    // Create log viewer container
    this.logContainer = document.createElement('div');
    this.logContainer.id = 'log-viewer';
    this.logContainer.style.cssText = `
      position: fixed;
      top: 10px;
      right: 10px;
      width: 400px;
      max-height: 500px;
      background: #1a1a1a;
      color: #ffffff;
      border: 1px solid #333;
      border-radius: 8px;
      font-family: 'Courier New', monospace;
      font-size: 12px;
      z-index: 10000;
      display: none;
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    `;

    // Create header
    const header = document.createElement('div');
    header.style.cssText = `
      background: #333;
      padding: 8px 12px;
      border-bottom: 1px solid #555;
      display: flex;
      justify-content: space-between;
      align-items: center;
    `;
    
    const title = document.createElement('span');
    title.textContent = 'Application Logs';
    title.style.fontWeight = 'bold';
    
    const closeBtn = document.createElement('button');
    closeBtn.textContent = '×';
    closeBtn.style.cssText = `
      background: none;
      border: none;
      color: #fff;
      font-size: 18px;
      cursor: pointer;
      padding: 0;
      width: 20px;
      height: 20px;
    `;
    closeBtn.onclick = () => this.hide();
    
    const clearBtn = document.createElement('button');
    clearBtn.textContent = 'Clear';
    clearBtn.style.cssText = `
      background: #666;
      border: none;
      color: #fff;
      font-size: 11px;
      cursor: pointer;
      padding: 2px 6px;
      border-radius: 3px;
      margin-right: 8px;
    `;
    clearBtn.onclick = () => this.clearLogs();
    
    header.appendChild(title);
    header.appendChild(clearBtn);
    header.appendChild(closeBtn);

    // Create log content area
    this.logContent = document.createElement('div');
    this.logContent.style.cssText = `
      padding: 8px;
      max-height: 400px;
      overflow-y: auto;
      background: #1a1a1a;
    `;

    this.logContainer.appendChild(header);
    this.logContainer.appendChild(this.logContent);
    document.body.appendChild(this.logContainer);
  }

  show() {
    this.isVisible = true;
    this.logContainer.style.display = 'block';
    this.refreshLogs();
  }

  hide() {
    this.isVisible = false;
    this.logContainer.style.display = 'none';
  }

  toggle() {
    if (this.isVisible) {
      this.hide();
    } else {
      this.show();
    }
  }

  refreshLogs() {
    const logs = getRecentLogs(50);
    this.logContent.innerHTML = '';
    
    if (logs.length === 0) {
      const noLogs = document.createElement('div');
      noLogs.textContent = 'No logs available';
      noLogs.style.color = '#666';
      this.logContent.appendChild(noLogs);
      return;
    }

    logs.forEach(log => {
      const logEntry = document.createElement('div');
      logEntry.style.cssText = `
        margin-bottom: 4px;
        padding: 2px 4px;
        border-radius: 3px;
        font-size: 11px;
        line-height: 1.3;
      `;

      // Color code by log level
      const levelColors = {
        ERROR: '#ff6b6b',
        WARN: '#ffa726',
        INFO: '#42a5f5',
        DEBUG: '#66bb6a'
      };

      logEntry.style.borderLeft = `3px solid ${levelColors[log.level] || '#666'}`;
      logEntry.style.backgroundColor = log.level === 'ERROR' ? '#2d1b1b' : 
                                      log.level === 'WARN' ? '#2d2419' : '#1a1a1a';

      const timestamp = new Date(log.timestamp).toLocaleTimeString();
      const message = `${timestamp} [${log.level}] [${log.component}] ${log.action}`;
      
      logEntry.innerHTML = `
        <div style="color: #ccc;">${message}</div>
        ${log.data ? `<div style="color: #888; margin-left: 10px;">${JSON.stringify(log.data, null, 2)}</div>` : ''}
      `;

      this.logContent.appendChild(logEntry);
    });

    // Scroll to bottom
    this.logContent.scrollTop = this.logContent.scrollHeight;
  }

  clearLogs() {
    clearStoredLogs();
    this.refreshLogs();
  }

  addLogEntry(level, component, action, data) {
    if (this.isVisible) {
      // Add new log entry to the display
      const logEntry = document.createElement('div');
      logEntry.style.cssText = `
        margin-bottom: 4px;
        padding: 2px 4px;
        border-radius: 3px;
        font-size: 11px;
        line-height: 1.3;
        animation: slideIn 0.3s ease-out;
      `;

      const levelColors = {
        ERROR: '#ff6b6b',
        WARN: '#ffa726',
        INFO: '#42a5f5',
        DEBUG: '#66bb6a'
      };

      logEntry.style.borderLeft = `3px solid ${levelColors[level] || '#666'}`;
      logEntry.style.backgroundColor = level === 'ERROR' ? '#2d1b1b' : 
                                      level === 'WARN' ? '#2d2419' : '#1a1a1a';

      const timestamp = new Date().toLocaleTimeString();
      const message = `${timestamp} [${level}] [${component}] ${action}`;
      
      logEntry.innerHTML = `
        <div style="color: #ccc;">${message}</div>
        ${data ? `<div style="color: #888; margin-left: 10px;">${JSON.stringify(data, null, 2)}</div>` : ''}
      `;

      this.logContent.appendChild(logEntry);
      this.logContent.scrollTop = this.logContent.scrollHeight;
    }
  }
}

// Global log viewer instance
let globalLogViewer = null;

// Initialize log viewer
export function initLogViewer() {
  if (!globalLogViewer) {
    globalLogViewer = new LogViewer();
    
    // Add keyboard shortcut (Ctrl+Shift+J)
    document.addEventListener('keydown', (e) => {
      if (e.ctrlKey && e.shiftKey && e.key === 'J') {
        e.preventDefault();
        globalLogViewer.toggle();
      }
    });

    logger.info('log-viewer', 'Log viewer initialized - Press Ctrl+Shift+J to toggle');
  }
  return globalLogViewer;
}

// Export for global access
if (typeof window !== 'undefined') {
  window.initLogViewer = initLogViewer;
  window.LogViewer = LogViewer;
}

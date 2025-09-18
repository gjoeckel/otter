/**
 * Centralized Logging Utility
 * Provides structured logging with configurable levels and performance monitoring
 */

// Log levels (higher number = more verbose)
export const LOG_LEVELS = {
  ERROR: 0,
  WARN: 1,
  INFO: 2,
  DEBUG: 3
};

// Current log level - can be configured based on environment
export const CURRENT_LOG_LEVEL = (() => {
  // Check for environment-based configuration
  if (typeof window !== 'undefined' && window.location) {
    const isLocalhost = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
    return isLocalhost ? LOG_LEVELS.DEBUG : LOG_LEVELS.INFO;
  }
  return LOG_LEVELS.INFO; // Default to INFO level
})();

/**
 * Enhanced logging function with level checking and structured output
 * @param {number} level - Log level (from LOG_LEVELS)
 * @param {string} component - Component name (e.g., 'reports-data', 'unified-service')
 * @param {string} action - Action being performed
 * @param {*} data - Additional data to log
 * @param {string} emoji - Optional emoji prefix
 */
export function log(level, component, action, data = null, emoji = '') {
  if (level <= CURRENT_LOG_LEVEL) {
    const timestamp = new Date().toISOString();
    const levelName = Object.keys(LOG_LEVELS)[level];
    
    const message = `${emoji} [${levelName}] [${component}] ${action}`;
    
    if (data !== null) {
      console.log(message, data);
    } else {
      console.log(message);
    }
    
    // Also log to structured format for potential server-side collection
    if (level <= LOG_LEVELS.INFO) {
      const structuredLog = {
        timestamp,
        level: levelName,
        component,
        action,
        data: data,
        url: window.location?.href,
        userAgent: navigator.userAgent
      };
      
      // Store in sessionStorage for debugging (limited to recent logs)
      try {
        const logs = JSON.parse(sessionStorage.getItem('app_logs') || '[]');
        logs.push(structuredLog);
        // Keep only last 50 logs
        if (logs.length > 50) {
          logs.splice(0, logs.length - 50);
        }
        sessionStorage.setItem('app_logs', JSON.stringify(logs));
      } catch (e) {
        // Ignore storage errors
      }
    }
  }
}

/**
 * Convenience functions for different log levels
 */
export const logger = {
  error: (component, action, data, emoji = '❌') => log(LOG_LEVELS.ERROR, component, action, data, emoji),
  warn: (component, action, data, emoji = '⚠️') => log(LOG_LEVELS.WARN, component, action, data, emoji),
  info: (component, action, data, emoji = 'ℹ️') => log(LOG_LEVELS.INFO, component, action, data, emoji),
  debug: (component, action, data, emoji = '🔧') => log(LOG_LEVELS.DEBUG, component, action, data, emoji),
  success: (component, action, data, emoji = '✅') => log(LOG_LEVELS.INFO, component, action, data, emoji),
  process: (component, action, data, emoji = '🔄') => log(LOG_LEVELS.INFO, component, action, data, emoji),
  data: (component, action, data, emoji = '📊') => log(LOG_LEVELS.DEBUG, component, action, data, emoji)
};

/**
 * Performance monitoring utility
 */
export class PerformanceMonitor {
  constructor() {
    this.timers = new Map();
  }
  
  start(label) {
    this.timers.set(label, performance.now());
    logger.debug('performance', `Started timer: ${label}`);
  }
  
  end(label) {
    const startTime = this.timers.get(label);
    if (startTime) {
      const duration = performance.now() - startTime;
      this.timers.delete(label);
      logger.info('performance', `Timer ${label} completed`, { duration: `${duration.toFixed(2)}ms` });
      return duration;
    }
    logger.warn('performance', `Timer ${label} not found`);
    return null;
  }
  
  measure(label, fn) {
    return async (...args) => {
      this.start(label);
      try {
        const result = await fn(...args);
        this.end(label);
        return result;
      } catch (error) {
        this.end(label);
        throw error;
      }
    };
  }
}

// Global performance monitor instance
export const perfMonitor = new PerformanceMonitor();

/**
 * User action tracking
 */
export function trackUserAction(action, details = {}) {
  logger.info('user-action', action, {
    ...details,
    timestamp: Date.now(),
    url: window.location?.href
  });
}

/**
 * API call tracking
 */
export function trackApiCall(url, method = 'GET', duration = null, success = true) {
  logger.info('api-call', `${method} ${url}`, {
    duration: duration ? `${duration.toFixed(2)}ms` : null,
    success,
    timestamp: Date.now()
  });
}

/**
 * Data validation logging
 */
export function logDataValidation(component, dataType, recordCount, hasErrors = false) {
  logger.info('data-validation', `${component} ${dataType} validation`, {
    recordCount,
    hasErrors,
    timestamp: Date.now()
  });
}

/**
 * Enhanced error scenario logging
 */
export function logErrorScenario(scenario, details = {}) {
  logger.warn('error-scenarios', `Scenario: ${scenario}`, {
    ...details,
    timestamp: Date.now(),
    url: window.location?.href
  });
}

/**
 * System health monitoring
 */
export function logSystemHealth() {
  const health = {
    timestamp: Date.now(),
    url: window.location?.href,
    memoryUsage: performance.memory ? {
      used: Math.round(performance.memory.usedJSHeapSize / 1024 / 1024) + 'MB',
      total: Math.round(performance.memory.totalJSHeapSize / 1024 / 1024) + 'MB',
      limit: Math.round(performance.memory.jsHeapSizeLimit / 1024 / 1024) + 'MB'
    } : 'Not available',
    apiCallsLastMinute: getApiCallCount(),
    errorRate: getErrorRate(),
    logLevel: Object.keys(LOG_LEVELS)[CURRENT_LOG_LEVEL],
    environment: window.location?.hostname === 'localhost' ? 'development' : 'production'
  };
  
  logger.info('system-health', 'Health check', health);
  return health;
}

/**
 * Code duplication metrics tracking
 */
export function logCodeReduction(originalLines, reducedLines, component = 'general') {
  const reduction = ((originalLines - reducedLines) / originalLines * 100).toFixed(1);
  logger.info('code-metrics', 'DRY implementation success', { 
    component,
    reduction: `${reduction}%`,
    originalLines,
    reducedLines,
    linesSaved: originalLines - reducedLines
  });
}

// API call tracking for health monitoring
let apiCallHistory = [];
const API_CALL_HISTORY_LIMIT = 100;

/**
 * Enhanced API call tracking with history
 */
export function trackApiCallEnhanced(url, method = 'GET', duration = null, success = true) {
  const call = {
    timestamp: Date.now(),
    url,
    method,
    duration,
    success
  };
  
  apiCallHistory.push(call);
  
  // Keep only recent calls
  if (apiCallHistory.length > API_CALL_HISTORY_LIMIT) {
    apiCallHistory = apiCallHistory.slice(-API_CALL_HISTORY_LIMIT);
  }
  
  // Use original tracking function
  trackApiCall(url, method, duration, success);
}

/**
 * Get API call count for the last minute
 */
function getApiCallCount() {
  const oneMinuteAgo = Date.now() - 60000;
  return apiCallHistory.filter(call => call.timestamp > oneMinuteAgo).length;
}

/**
 * Get error rate for recent API calls
 */
function getErrorRate() {
  if (apiCallHistory.length === 0) return 0;
  
  const recentCalls = apiCallHistory.slice(-20); // Last 20 calls
  const errorCount = recentCalls.filter(call => !call.success).length;
  
  return ((errorCount / recentCalls.length) * 100).toFixed(1) + '%';
}

/**
 * Get recent logs for debugging
 */
export function getRecentLogs(count = 20) {
  try {
    const logs = JSON.parse(sessionStorage.getItem('app_logs') || '[]');
    return logs.slice(-count);
  } catch (e) {
    return [];
  }
}

/**
 * Clear stored logs
 */
export function clearStoredLogs() {
  try {
    sessionStorage.removeItem('app_logs');
    logger.info('logging', 'Cleared stored logs');
  } catch (e) {
    // Ignore storage errors
  }
}

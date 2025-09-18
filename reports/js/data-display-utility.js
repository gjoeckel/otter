// data-display-utility.js
// Common utility for data display filtering - reusable for Organizations and Districts tables

import { logger } from './logging-utils.js';

// Race condition controls for message display
let messageDisplayQueue = [];
let isDisplayingMessage = false;

/**
 * Unified Messaging System for consistent message display across the application
 * Handles race conditions, performance monitoring, and enhanced error handling
 */
export class UnifiedMessagingSystem {
  constructor() {
    this.messageQueue = [];
    this.isDisplaying = false;
    this.messageTypes = ['error', 'warning', 'info', 'success'];
    this.currentElementId = null;
    this.performanceThreshold = 10; // Log operations taking longer than 10ms
  }

  /**
   * Unified message display function with performance monitoring
   * @param {string} elementId - Target element ID
   * @param {string} message - Message content
   * @param {string} type - Message type: 'error', 'warning', 'info', 'success'
   * @param {Object} options - Additional options
   */
  showMessage(elementId, message, type = 'info', options = {}) {
    const startTime = performance.now();
    this.currentElementId = elementId;
    
    const messageAction = () => {
      try {
        const element = document.getElementById(elementId);
        if (!element) {
          logger.warn('unified-messaging', 'Element not found', { 
            elementId, 
            message, 
            type,
            documentReady: document.readyState,
            allElements: Array.from(document.querySelectorAll('[id]')).map(el => el.id)
          });
          // Don't show fallback message for missing elements - just log and return
          return;
        }

        // Clear existing classes
        this.messageTypes.forEach(t => element.classList.remove(`${t}-message`));
        
        if (message && message.trim()) {
          element.classList.add(`${type}-message`);
          element.innerHTML = message;
          element.setAttribute('aria-live', options.ariaLive || 'polite');
          element.removeAttribute('aria-hidden');
          
          logger.debug('unified-messaging', 'Message displayed', { elementId, type, messageLength: message.length });
        } else {
          element.innerHTML = '';
          element.setAttribute('aria-hidden', 'true');
          logger.debug('unified-messaging', 'Message cleared', { elementId });
        }
      } catch (error) {
        this.handleError(error, { elementId, message, type, context: 'showMessage' });
      } finally {
        const endTime = performance.now();
        const duration = endTime - startTime;
        
        if (duration > this.performanceThreshold) {
          logger.warn('unified-messaging', 'Slow message display', {
            duration,
            elementId,
            messageLength: message ? message.length : 0
          });
        }
      }
    };

    this.queueMessage(messageAction);
  }

  /**
   * Clear message from element
   * @param {string} elementId - Target element ID
   */
  clearMessage(elementId) {
    const startTime = performance.now();
    
    try {
      const element = document.getElementById(elementId);
      if (element) {
        element.innerHTML = '';
        this.messageTypes.forEach(t => element.classList.remove(`${t}-message`));
        element.setAttribute('aria-hidden', 'true');
        logger.debug('unified-messaging', 'Message cleared', { elementId });
      }
    } catch (error) {
      this.handleError(error, { elementId, context: 'clearMessage' });
    } finally {
      const endTime = performance.now();
      const duration = endTime - startTime;
      
      if (duration > this.performanceThreshold) {
        logger.warn('unified-messaging', 'Slow message clear', { duration, elementId });
      }
    }
  }

  /**
   * Queue message display to prevent race conditions
   * @param {Function} messageAction - Function to execute
   */
  queueMessage(messageAction) {
    if (this.isDisplaying) {
      this.messageQueue.push(messageAction);
      return;
    }
    
    this.isDisplaying = true;
    messageAction();
    this.isDisplaying = false;
    
    // Process queued messages
    while (this.messageQueue.length > 0) {
      const nextAction = this.messageQueue.shift();
      if (nextAction) {
        this.isDisplaying = true;
        nextAction();
        this.isDisplaying = false;
      }
    }
  }

  /**
   * Enhanced error handling with fallback mechanisms
   * @param {Error} error - The error that occurred
   * @param {Object} context - Context information about the error
   */
  handleError(error, context) {
    logger.error('unified-messaging', 'Message display failed', { 
      error: error.message, 
      context,
      elementId: this.currentElementId,
      stack: error.stack
    });
    
    // Determine if this is a critical error that needs fallback
    if (this.isCriticalError(error)) {
      const fallbackMessage = this.getFallbackMessage(context);
      this.showFallbackMessage(fallbackMessage);
    }
  }

  /**
   * Check if error is critical and requires fallback
   * @param {Error} error - The error to check
   * @returns {boolean} - True if error is critical
   */
  isCriticalError(error) {
    // Critical errors that prevent normal message display
    const criticalPatterns = [
      /element not found/i,
      /cannot read property/i,
      /null reference/i,
      /undefined reference/i
    ];
    
    return criticalPatterns.some(pattern => pattern.test(error.message));
  }

  /**
   * Get fallback message based on context
   * @param {Object} context - Error context
   * @returns {string} - Fallback message
   */
  getFallbackMessage(context) {
    if (context.type === 'error') {
      return 'A system error occurred. Please try again.';
    } else if (context.type === 'warning') {
      return 'Please check your input and try again.';
    } else {
      return 'System message unavailable.';
    }
  }

  /**
   * Show fallback message using browser alert for critical errors
   * @param {string} message - Fallback message
   */
  showFallbackMessage(message) {
    try {
      // Only use alert as last resort for critical errors
      if (typeof window !== 'undefined' && window.alert) {
        alert(`System message: ${message}`);
      } else {
        console.error('Critical messaging error - no fallback available:', message);
      }
    } catch (fallbackError) {
      logger.error('unified-messaging', 'Fallback message failed', { 
        originalMessage: message, 
        fallbackError: fallbackError.message 
      });
    }
  }
}

// Create global instance
export const unifiedMessaging = new UnifiedMessagingSystem();

/**
 * Common utility for filtering table data based on display mode
 * @param {Array} data - Array of data objects
 * @param {string} mode - Display mode: 'all', 'no-values', 'hide-empty'
 * @param {Array} numericColumns - Array of column names that should be treated as numeric
 * @returns {Array} - Filtered data array
 */
export function filterTableData(data, mode, numericColumns = ['registrations', 'enrollments', 'certificates']) {
  if (!Array.isArray(data) || data.length === 0) {
    return [];
  }

  switch (mode) {
    case 'all':
      // Show all rows as-is
      return data;
      
    case 'no-values':
      // Show all rows with no values: hide all rows that have at least one non-zero column
      return data.filter(row => {
        return numericColumns.every(col => {
          const value = row[col];
          return value === 0 || value === '0' || value === '' || value === null || value === undefined;
        });
      });
      
    case 'hide-empty':
      // Hide rows where all numeric values are zero
      return data.filter(row => {
        return numericColumns.some(col => {
          const value = row[col];
          return value !== 0 && value !== '0' && value !== '' && value !== null && value !== undefined;
        });
      });
      
    default:
      return data;
  }
}

/**
 * Check if a row has any non-zero values in numeric columns
 * @param {Object} row - Data row object
 * @param {Array} numericColumns - Array of column names to check
 * @returns {boolean} - True if row has at least one non-zero value
 */
export function hasNonZeroValues(row, numericColumns = ['registrations', 'enrollments', 'certificates']) {
  return numericColumns.some(col => {
    const value = row[col];
    return value !== 0 && value !== '0' && value !== '' && value !== null && value !== undefined;
  });
}

/**
 * Check if a row has all zero values in numeric columns
 * @param {Object} row - Data row object
 * @param {Array} numericColumns - Array of column names to check
 * @returns {boolean} - True if row has all zero values
 */
export function hasAllZeroValues(row, numericColumns = ['registrations', 'enrollments', 'certificates']) {
  return numericColumns.every(col => {
    const value = row[col];
    return value === 0 || value === '0' || value === '' || value === null || value === undefined;
  });
}

/**
 * Generate HTML for a table row
 * @param {Object} row - Data row object
 * @param {string} nameColumn - Name of the column containing the display name
 * @param {Array} numericColumns - Array of numeric column names
 * @param {Function} nameFormatter - Optional function to format the display name
 * @returns {string} - HTML string for the table row
 */
export function generateTableRowHTML(row, nameColumn, numericColumns = ['registrations', 'enrollments', 'certificates'], nameFormatter = null) {
  let displayName = row[nameColumn];
  
  // Apply name formatting if provided
  if (nameFormatter && typeof nameFormatter === 'function') {
    displayName = nameFormatter(row);
  }
  
  const cells = [
    `<td class="${nameColumn}">${displayName}</td>`
  ];
  
  numericColumns.forEach(col => {
    const value = row[col];
    cells.push(`<td>${value}</td>`);
  });
  
  return `<tr>${cells.join('')}</tr>`;
}

/**
 * Update table with filtered data
 * @param {string} tableId - ID of the table to update
 * @param {Array} data - Data array
 * @param {string} mode - Display mode
 * @param {string} nameColumn - Name of the column containing the display name
 * @param {Array} numericColumns - Array of numeric column names
 * @param {Function} nameFormatter - Optional function to format the display name
 * @param {string} emptyMessage - Message to show when no data
 */
export function updateTableWithFilteredData(tableId, data, mode, nameColumn, numericColumns = ['registrations', 'enrollments', 'certificates'], nameFormatter = null, emptyMessage = 'No data available') {
  const tbody = document.querySelector(`#${tableId} tbody`);
  if (!tbody) {
    return;
  }

  // Filter data based on mode
  const filteredData = filterTableData(data, mode, numericColumns);
  
  if (filteredData.length === 0) {
    let message = emptyMessage;
    if (mode === 'hide-empty') {
      message = 'No rows with data for this range.';
    } else if (mode === 'no-values') {
      message = 'No rows with all zero values for this range.';
    }
    tbody.innerHTML = `<tr><td colspan="${numericColumns.length + 1}">${message}</td></tr>`;
    return;
  }
  
  // Generate HTML for filtered data
  const htmlString = filteredData.map(row => 
    generateTableRowHTML(row, nameColumn, numericColumns, nameFormatter)
  ).join('');
  
  tbody.innerHTML = htmlString;
}

/**
 * Display message in data display message element (backward compatibility)
 * @param {string} tableType - 'organization' or 'groups'
 * @param {string} message - Message to display
 * @param {string} type - Message type: 'info', 'warning', 'error', 'success'
 */
export function showDataDisplayMessage(tableType, message, type = 'info') {
  logger.debug('data-display-utility', 'showDataDisplayMessage called', { tableType, message, type });
  
  // Handle different table types with their correct element IDs
  let elementId;
  if (tableType === 'organization') {
    elementId = 'organization-data-display-message';
  } else if (tableType === 'groups') {
    elementId = 'groups-data-display-message';
  } else {
    elementId = `${tableType}-data-display-message`;
  }
  
  // Use unified messaging system
  unifiedMessaging.showMessage(elementId, message, type);
}

/**
 * Clear message in data display message element (backward compatibility)
 * @param {string} tableType - 'organization' or 'groups'
 */
export function clearDataDisplayMessage(tableType) {
  // Handle different table types with their correct element IDs
  let elementId;
  if (tableType === 'organization') {
    elementId = 'organization-data-display-message';
  } else if (tableType === 'groups') {
    elementId = 'groups-data-display-message';
  } else {
    elementId = `${tableType}-data-display-message`;
  }
  
  // Use unified messaging system
  unifiedMessaging.clearMessage(elementId);
}

// Legacy queue system - now handled by UnifiedMessagingSystem
// Keeping variables for backward compatibility but functionality moved to unifiedMessaging

// Make functions globally available for debugging and other modules
if (typeof window !== 'undefined') {
  window.showDataDisplayMessage = showDataDisplayMessage;
  window.clearDataDisplayMessage = clearDataDisplayMessage;
  window.unifiedMessaging = unifiedMessaging;
}
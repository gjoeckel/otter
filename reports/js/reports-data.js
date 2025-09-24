/**
 * MVP Reports Data Service
 * Simplified version without count options complexity
 * 
 * This MVP version eliminates all count options logic and uses hardcoded default values:
 * - Registrations: Always by submission date (by-date)
 * - Enrollments: Always by TOU completion date (by-tou)
 * - No cohort mode, no auto-switching, no radio button logic
 */

import { MvpReportsDataService } from './unified-data-service.js';
import { MvpUnifiedTableUpdater } from './unified-table-updater.js';
import { logger, perfMonitor } from './logging-utils.js';

// Module-level cache for MVP
let __lastSummaryData = null;
let __lastStart = '';
let __lastEnd = '';

// Debouncing mechanism
let __updateTimeout = null;
let __lastUpdateParams = null;

/**
 * MVP Mode Detection - Always returns hardcoded default values
 * No radio button logic, no user choice, no complexity
 */
function getCurrentModes() {
  return { 
    registrationsMode: 'by-date',    // Always by submission date
    enrollmentMode: 'by-tou',        // Always by TOU completion date
    cohortMode: false                // Never use cohort mode
  };
}

/**
 * MVP Data Fetching - Simplified without auto-switching logic
 */
async function fetchAndUpdateAllTablesInternal(start, end) {
  perfMonitor.start('fetchAndUpdateAllTables');
  
  try {
    logger.process('mvp-reports-data', 'Starting MVP fetchAndUpdateAllTables', { start, end });
    
    // Initialize services if not already done
    if (!window.reportsDataService) {
      window.reportsDataService = new MvpReportsDataService();
    }
    if (!window.unifiedTableUpdater) {
      window.unifiedTableUpdater = new MvpUnifiedTableUpdater();
    }
    
    // MVP: Always use hardcoded modes - no user choice, no complexity
    const modes = getCurrentModes();
    const enrollmentMode = modes.enrollmentMode;
    const cohortMode = modes.cohortMode;
    
    logger.debug('mvp-reports-data', 'Using hardcoded MVP modes', { enrollmentMode, cohortMode });
    
    // Single service call updates all tables with hardcoded modes
    await window.reportsDataService.updateAllTables(start, end, enrollmentMode, cohortMode);
    
    // Update legacy variables for backward compatibility
    __lastStart = start;
    __lastEnd = end;
    
    // Update global variables
    if (typeof window !== 'undefined') {
      window.__lastStart = __lastStart;
      window.__lastEnd = __lastEnd;
    }
    
    // Get data for legacy UI updates from unified service
    const unifiedData = await window.reportsDataService.fetchAllData(start, end, enrollmentMode, cohortMode);
    
    // Update legacy variables with unified dataset
    __lastSummaryData = {
        ...unifiedData,
        cohortModeSubmissions: unifiedData.submissions || []
    };
    
    // Make __lastSummaryData globally accessible for debugging
    if (typeof window !== 'undefined') {
        window.__lastSummaryData = __lastSummaryData;
    }
    
    // MVP: Simple status message - no complex mode switching
    logger.info('mvp-reports-data', 'MVP data loaded successfully', {
      registrations: unifiedData.systemwide?.registrations_count || 0,
      enrollments: unifiedData.systemwide?.enrollments_count || 0,
      certificates: unifiedData.systemwide?.certificates_count || 0
    });
    
    const duration = perfMonitor.end('fetchAndUpdateAllTables');
    logger.success('mvp-reports-data', 'MVP fetchAndUpdateAllTables completed', { duration: `${duration.toFixed(2)}ms` });
    
  } catch (error) {
    perfMonitor.end('fetchAndUpdateAllTables');
    logger.error('mvp-reports-data', 'MVP failed to fetch and update tables', error);
    throw error;
  }
}

/**
 * MVP Debounced Data Fetching
 */
function debouncedFetchAndUpdateAllTables(start, end) {
  const params = `${start}-${end}`;
  
  // Clear existing timeout
  if (__updateTimeout) {
    clearTimeout(__updateTimeout);
  }
  
  // If same parameters, don't update again
  if (__lastUpdateParams === params) {
    logger.debug('mvp-reports-data', 'MVP Debounce: Same parameters, skipping update');
    return Promise.resolve();
  }
  
  return new Promise((resolve, reject) => {
    __updateTimeout = setTimeout(async () => {
      try {
        __lastUpdateParams = params;
        await fetchAndUpdateAllTablesInternal(start, end);
        resolve();
      } catch (error) {
        reject(error);
      }
    }, 300); // 300ms debounce
  });
}

/**
 * MVP Main Export - Simplified data fetching
 */
export async function fetchAndUpdateAllTables(start, end) {
  return debouncedFetchAndUpdateAllTables(start, end);
}

/**
 * MVP Enrollment Mode Change Handler - Simplified
 * No complex mode switching, just log the attempt
 */
export function handleEnrollmentModeChange(newMode) {
  logger.info('mvp-reports-data', 'MVP: Enrollment mode change attempted but ignored', { 
    attemptedMode: newMode,
    actualMode: 'by-tou (hardcoded)'
  });
  
  // MVP: Do nothing - modes are hardcoded
  // In the full version, this would trigger table updates
}

// Make functions globally available for MVP
if (typeof window !== 'undefined') {
  window.fetchAndUpdateAllTables = fetchAndUpdateAllTables;
  window.__lastStart = __lastStart;
  window.__lastEnd = __lastEnd;
  window.getCurrentModes = getCurrentModes;
}

/**
 * MVP Reports Entry Point
 * Simplified entry point that imports only MVP modules
 * 
 * This entry point eliminates all count options complexity and uses only
 * the simplified MVP versions of all modules.
 */

// MVP: Import only simplified modules
import { fetchAndUpdateAllTables, handleEnrollmentModeChange } from './reports-data.js';
import { MvpReportsDataService } from './unified-data-service.js';
import { MvpUnifiedTableUpdater } from './unified-table-updater.js';
import { logger } from './logging-utils.js';
import './date-range-picker.js'; // Include date range picker functionality

// MVP: Initialize simplified services
window.reportsDataService = new MvpReportsDataService();
window.unifiedTableUpdater = new MvpUnifiedTableUpdater();

// MVP: Make functions globally available
window.fetchAndUpdateAllTables = fetchAndUpdateAllTables;
window.handleEnrollmentModeChange = handleEnrollmentModeChange;

// MVP: Log initialization
logger.info('mvp-reports-entry', 'MVP Reports system initialized', {
  hasReportsDataService: !!window.reportsDataService,
  hasUnifiedTableUpdater: !!window.unifiedTableUpdater,
  mode: 'MVP (simplified)'
});

// MVP: Export for module compatibility
export { fetchAndUpdateAllTables, handleEnrollmentModeChange };

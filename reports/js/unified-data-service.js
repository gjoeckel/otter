/**
 * MVP Unified Data Service
 * Simplified version without count options complexity
 * 
 * This MVP version eliminates mode switching and always uses hardcoded values:
 * - enrollmentMode: Always 'by-tou'
 * - cohortMode: Always false
 * - No dynamic mode detection or switching
 */

import { logger, perfMonitor, trackApiCall } from './logging-utils.js';

export class MvpReportsDataService {
  constructor() {
    this.currentDateRange = null;
    // MVP: Hardcoded values - no user choice, no complexity
    this.currentEnrollmentMode = 'by-tou';        // Always TOU completion date
    this.currentRegistrationsCohortMode = false;  // Never use cohort mode
    this.cache = new Map();
    this.updateTimeout = null;
    this.lastUpdateParams = null;
  }

  /**
   * MVP Update All Tables - Simplified with hardcoded modes
   * 
   * @param {string} start - Start date in MM-DD-YY format
   * @param {string} end - End date in MM-DD-YY format
   * @param {string} enrollmentMode - Ignored in MVP (always 'by-tou')
   * @param {boolean} cohortMode - Ignored in MVP (always false)
   */
  async updateAllTables(start, end, enrollmentMode = null, cohortMode = false, options = {}) {
    // MVP: Always use hardcoded modes - ignore parameters
    const mvpEnrollmentMode = 'by-tou';
    const mvpCohortMode = false;
    
    const params = `${start}-${end}-${mvpEnrollmentMode}-${mvpCohortMode}`;
    
    // Clear existing timeout
    if (this.updateTimeout) {
      clearTimeout(this.updateTimeout);
    }
    
    // If same parameters, don't update again
    if (this.lastUpdateParams === params) {
      logger.debug('mvp-unified-data-service', 'MVP Debounce - Same parameters, skipping update');
      return Promise.resolve();
    }
    
    return new Promise((resolve, reject) => {
      this.updateTimeout = setTimeout(async () => {
        try {
          this.lastUpdateParams = params;
          await this.updateAllTablesInternal(start, end, mvpEnrollmentMode, mvpCohortMode, options);
          resolve();
        } catch (error) {
          reject(error);
        }
      }, 300); // 300ms debounce
    });
  }

  /**
   * MVP Internal Update - Simplified without mode complexity
   */
  async updateAllTablesInternal(start, end, enrollmentMode, cohortMode, options = {}) {
    perfMonitor.start('updateAllTables');
    
    try {
      logger.process('mvp-unified-data-service', 'Starting MVP updateAllTables', { 
        start, 
        end, 
        enrollmentMode, 
        cohortMode 
      });
      
      // Update current state
      this.currentDateRange = { start, end };
      this.currentEnrollmentMode = enrollmentMode;
      this.currentRegistrationsCohortMode = cohortMode;
      
      // Fetch unified data with hardcoded modes
      const unifiedData = await this.fetchAllData(start, end, enrollmentMode, cohortMode);
      
      // Update all tables with unified data
      if (window.unifiedTableUpdater) {
        window.unifiedTableUpdater.updateAllTables(unifiedData, options);
      } else {
        logger.warn('mvp-unified-data-service', 'UnifiedTableUpdater not available');
      }
      
      const duration = perfMonitor.end('updateAllTables');
      logger.success('mvp-unified-data-service', 'MVP updateAllTables completed', { 
        duration: `${duration.toFixed(2)}ms`,
        registrations: unifiedData.systemwide?.registrations_count || 0,
        enrollments: unifiedData.systemwide?.enrollments_count || 0,
        certificates: unifiedData.systemwide?.certificates_count || 0
      });
      
    } catch (error) {
      perfMonitor.end('updateAllTables');
      logger.error('mvp-unified-data-service', 'MVP updateAllTables failed', error);
      throw error;
    }
  }

  /**
   * MVP Fetch All Data - Simplified with hardcoded modes
   */
  async fetchAllData(start, end, enrollmentMode, cohortMode) {
    const url = `mvp_reports_api.php?start_date=${encodeURIComponent(start)}&end_date=${encodeURIComponent(end)}&enrollment_mode=${enrollmentMode}&all_tables=1`;
    
    logger.debug('mvp-unified-data-service', 'Fetching MVP data', { url });
    trackApiCall('GET', url);
    
    try {
      const response = await fetch(url);
      
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
      
      const data = await response.json();
      
      if (typeof data !== 'object' || data === null) {
        throw new Error('Invalid response: not an object');
      }
      
      if (data.error) {
        throw new Error(`API Error: ${data.error}`);
      }
      
      logger.success('mvp-unified-data-service', 'MVP data fetched successfully', {
        hasSystemwide: !!data.systemwide,
        hasOrganizations: !!data.organizations,
        hasGroups: !!data.groups
      });
      
      return data;
      
    } catch (error) {
      logger.error('mvp-unified-data-service', 'MVP fetchAllData failed', error);
      throw error;
    }
  }

  /**
   * MVP Handle Enrollment Mode Change - Simplified
   * No actual mode changing, just logging
   */
  handleEnrollmentModeChange(newMode) {
    logger.info('mvp-unified-data-service', 'MVP: Enrollment mode change ignored', {
      attemptedMode: newMode,
      actualMode: this.currentEnrollmentMode
    });
    
    // MVP: Do nothing - modes are hardcoded
    // In the full version, this would trigger data refresh
  }
}

// Export for compatibility
export { MvpReportsDataService as ReportsDataService };

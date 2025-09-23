/**
 * Unified Data Service
 * Centralized service for fetching and managing all report table data
 * 
 * This service provides a unified approach to data fetching, eliminating
 * duplicate API calls and ensuring consistent data across all tables.
 */

import { logger, perfMonitor, trackApiCall, logErrorScenario } from './logging-utils.js';

export class ReportsDataService {
  constructor() {
    this.currentDateRange = null;
    this.currentEnrollmentMode = 'by-tou';
    this.currentRegistrationsCohortMode = false;
    this.cache = new Map();
    this.updateTimeout = null;
    this.lastUpdateParams = null;
  }

  /**
   * Update all tables with unified data from single API call (debounced)
   * 
   * @param {string} start - Start date in MM-DD-YY format
   * @param {string} end - End date in MM-DD-YY format
   * @param {string} enrollmentMode - Enrollment mode ('by-tou' or 'by-registration')
   */
  async updateAllTables(start, end, enrollmentMode = null, cohortMode = false, options = {}) {
    const params = `${start}-${end}-${enrollmentMode || this.currentEnrollmentMode}-${cohortMode}`;
    
    // Clear existing timeout
    if (this.updateTimeout) {
      clearTimeout(this.updateTimeout);
    }
    
    // If same parameters, don't update again
    if (this.lastUpdateParams === params) {
      logger.debug('unified-data-service', 'Debounce - Same parameters, skipping update');
      return Promise.resolve();
    }
    
    return new Promise((resolve, reject) => {
      this.updateTimeout = setTimeout(async () => {
        try {
          this.lastUpdateParams = params;
          await this.updateAllTablesInternal(start, end, enrollmentMode, cohortMode, options);
          resolve();
        } catch (error) {
          reject(error);
        }
      }, 200); // 200ms debounce
    });
  }

  /**
   * Internal method that does the actual update work
   */
  async updateAllTablesInternal(start, end, enrollmentMode = null, cohortMode = false, options = {}) {
    try {
      // Update current date range and enrollment mode
      this.currentDateRange = { start, end };
      this.currentEnrollmentMode = enrollmentMode || this.currentEnrollmentMode;
      this.currentRegistrationsCohortMode = !!cohortMode;
      
      logger.process('unified-data-service', 'Starting updateAllTables', {
        start,
        end,
        enrollmentMode: this.currentEnrollmentMode,
        cohortMode: this.currentRegistrationsCohortMode,
        lockRegistrations: !!options.lockRegistrations
      });

      // Single API call for all data
      const allData = await this.fetchAllData(start, end, enrollmentMode, cohortMode);
      
      logger.data('unified-data-service', 'Received unified data', allData);
      
      // Update all tables with unified data
      if (window.unifiedTableUpdater) {
        logger.process('unified-data-service', 'Calling unifiedTableUpdater.updateAllTables');
        window.unifiedTableUpdater.updateAllTables(allData, options);
      } else {
        logger.warn('unified-data-service', 'unifiedTableUpdater not available');
      }
      
      // Update state
      this.currentDateRange = { start, end };
      this.currentEnrollmentMode = enrollmentMode || this.currentEnrollmentMode;
      this.currentRegistrationsCohortMode = !!cohortMode;
      
      logger.success('unified-data-service', 'All tables updated successfully', {
        currentDateRange: this.currentDateRange,
        currentEnrollmentMode: this.currentEnrollmentMode,
        cohortMode: this.currentRegistrationsCohortMode,
        lockRegistrations: !!options.lockRegistrations
      });
      
    } catch (error) {
      logger.error('unified-data-service', 'Failed to update all tables', error);
      throw error;
    }
  }

  /**
   * Fetch all table data in a single API call
   * 
   * @param {string} start - Start date in MM-DD-YY format
   * @param {string} end - End date in MM-DD-YY format
   * @param {string} enrollmentMode - Enrollment mode
   * @param {boolean} cohortMode - Whether registrations should use cohort dataset
   * @returns {Object} All table data
   */
  async fetchAllData(start, end, enrollmentMode, cohortMode = false) {
    const url = `reports_api.php?start_date=${encodeURIComponent(start)}&end_date=${encodeURIComponent(end)}&enrollment_mode=${encodeURIComponent(enrollmentMode)}&all_tables=1${cohortMode ? '&cohort_mode=true' : ''}`;
    return await this.fetchWithRetry(url);
  }

  /**
   * Fetch data with retry logic for network reliability
   * 
   * @param {string} url - URL to fetch
   * @param {number} retries - Number of retry attempts
   * @param {number} delay - Delay between retries in milliseconds
   * @returns {Object} Fetched data
   */
  async fetchWithRetry(url, retries = 2, delay = 500) {
    const startTime = performance.now();
    
    for (let i = 0; i <= retries; i++) {
      try {
        logger.debug('unified-data-service', `Fetching data (attempt ${i + 1}/${retries + 1})`, { url });
        
        const resp = await fetch(url);
        if (!resp.ok) {
          throw new Error(`Network error: ${resp.status} ${resp.statusText}`);
        }
        
        const data = await resp.json();
        if (typeof data !== 'object' || data === null) {
          throw new Error('Invalid data format received');
        }
        
        const duration = performance.now() - startTime;
        trackApiCall(url, 'GET', duration, true);
        logger.success('unified-data-service', 'Data fetched successfully', { duration: `${duration.toFixed(2)}ms` });
        return data;
        
      } catch (err) {
        logger.warn('unified-data-service', `Fetch attempt ${i + 1} failed`, { error: err.message });
        
        // Log specific error scenarios
        if (err.message.includes('Network error')) {
          logErrorScenario('network_error', { 
            status: err.message.match(/\d+/)?.[0], 
            attempt: i + 1,
            url 
          });
        } else if (err.message.includes('Invalid data format')) {
          logErrorScenario('invalid_data_format', { 
            attempt: i + 1,
            url 
          });
        } else if (err.message.includes('timeout')) {
          logErrorScenario('api_timeout', { 
            attempt: i + 1,
            url,
            timeout: delay
          });
        }
        
        if (i === retries) {
          const duration = performance.now() - startTime;
          trackApiCall(url, 'GET', duration, false);
          logger.error('unified-data-service', 'All fetch attempts failed', err);
          logErrorScenario('all_attempts_failed', { 
            totalAttempts: retries + 1,
            url,
            duration: duration.toFixed(2)
          });
          throw err;
        }
        
        // Wait before retry
        await new Promise(resolve => setTimeout(resolve, delay));
        delay *= 2; // Exponential backoff
      }
    }
  }

  /**
   * Handle enrollment mode changes
   * 
   * @param {string} newMode - New enrollment mode
   */
  async handleEnrollmentModeChange(newMode) {
    if (this.currentDateRange) {
      logger.info('unified-data-service', 'Enrollment mode changed', { newMode });
      await this.updateAllTables(
        this.currentDateRange.start,
        this.currentDateRange.end,
        newMode,
        this.currentRegistrationsCohortMode
      );
    }
  }

  /**
   * Get current state
   * 
   * @returns {Object} Current state
   */
  getState() {
    return {
      dateRange: this.currentDateRange,
      enrollmentMode: this.currentEnrollmentMode
    };
  }

  /**
   * Clear cache and reset state
   */
  clearCache() {
    this.cache.clear();
    this.currentDateRange = null;
    this.currentEnrollmentMode = 'by-tou';
  }
}

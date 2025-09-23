/**
 * Unified Table Updater System
 * Centralized system for updating all report tables with consistent data
 * 
 * This system provides a unified approach to table updates, ensuring
 * all tables are updated simultaneously with consistent data.
 */

import { populateDatalistFromTable } from './datalist-utils.js';
import { updateOrganizationTableWithDisplayMode, updateGroupsTableWithDisplayMode } from './data-display-options.js';
import { logger, perfMonitor, logDataValidation } from './logging-utils.js';
import { unifiedMessaging } from './data-display-utility.js';

export class UnifiedTableUpdater {
  constructor() {
    this.tables = {
      systemwide: new SystemwideTableUpdater(),
      organizations: new OrganizationsTableUpdater(),
      groups: new GroupsTableUpdater()
    };
  }

  /**
   * Update all tables with unified data
   * 
   * @param {Object} data - Unified data from API
   */
  updateAllTables(data, options = {}) {
    perfMonitor.start('updateAllTables');
    
    try {
      logger.process('unified-table-updater', 'Starting updateAllTables', { meta: { lockRegistrations: !!options.lockRegistrations }, sample: {
        hasSystemwide: !!data.systemwide,
        orgs: Array.isArray(data.organizations) ? data.organizations.length : 0,
        groups: Array.isArray(data.groups) ? data.groups.length : 0
      }});
      
      // Update each table
      if (data.systemwide) {
        logger.debug('unified-table-updater', 'Updating systemwide table', { lockRegistrations: !!options.lockRegistrations, data: data.systemwide });
        this.tables.systemwide.update(data.systemwide, options);
      } else {
        logger.warn('unified-table-updater', 'No systemwide data provided');
      }
      
      if (data.organizations) {
        logger.debug('unified-table-updater', 'Updating organizations table', { count: data.organizations.length });
        logDataValidation('unified-table-updater', 'organizations', data.organizations.length);
        this.tables.organizations.update(data.organizations, options);
      } else {
        logger.warn('unified-table-updater', 'No organizations data provided');
      }
      
      if (data.groups) {
        logger.debug('unified-table-updater', 'Updating groups table', { count: data.groups.length });
        logDataValidation('unified-table-updater', 'groups', data.groups.length);
        this.tables.groups.update(data.groups, options);
      } else {
        logger.info('unified-table-updater', 'No groups data provided (may be normal if groups not supported)');
      }
      
      const duration = perfMonitor.end('updateAllTables');
      logger.success('unified-table-updater', 'All tables updated successfully', { duration: `${duration.toFixed(2)}ms` });
      
    } catch (error) {
      perfMonitor.end('updateAllTables');
      logger.error('unified-table-updater', 'Failed to update tables', error);
      throw error;
    }
  }

  /**
   * Handle enrollment mode changes
   * 
   * @param {string} newMode - New enrollment mode
   */
  handleEnrollmentModeChange(newMode) {
    logger.process('unified-table-updater', 'Handling enrollment mode change', { newMode });
    
    if (window.reportsDataService?.currentDateRange) {
      logger.debug('unified-table-updater', 'Current date range available', window.reportsDataService.currentDateRange);
      // Determine cohort mode from DOM if available; fallback to service state
      let cohortMode = false;
      try {
        if (typeof window.getCurrentModes === 'function') {
          const modes = window.getCurrentModes();
          cohortMode = !!modes.cohortMode;
        } else {
          cohortMode = !!window.reportsDataService.currentRegistrationsCohortMode;
        }
      } catch (e) {
        cohortMode = !!window.reportsDataService.currentRegistrationsCohortMode;
      }
      logger.debug('unified-table-updater', 'Preserving registrations cohort mode during enrollment change', { cohortMode });
      window.reportsDataService.updateAllTables(
        window.reportsDataService.currentDateRange.start,
        window.reportsDataService.currentDateRange.end,
        newMode,
        cohortMode,
        { lockRegistrations: true }
      );
    } else {
      logger.warn('unified-table-updater', 'No current date range available for enrollment mode change');
    }
  }
}

/**
 * Base class for all table updaters
 */
class BaseTableUpdater {
  constructor(tableId, datalistId = null) {
    this.tableId = tableId;
    this.datalistId = datalistId;
  }

  /**
   * Update table with data
   * 
   * @param {*} data - Data to update table with
   */
  update(data, options = {}) {
    this.validateData(data);
    this.updateTable(data, options);
    this.updateDatalist();
    this.updateDisplayMode();
  }

  /**
   * Validate data before processing
   * 
   * @param {*} data - Data to validate
   */
  validateData(data) {
    if (data === null || data === undefined) {
      throw new Error(`Invalid data provided to ${this.constructor.name}`);
    }
  }

  /**
   * Update datalist if configured
   */
  updateDatalist() {
    if (this.datalistId) {
      try {
        populateDatalistFromTable(this.tableId, this.datalistId);
      } catch (error) {
        logger.warn('unified-table-updater', 'Failed to update datalist', { 
          datalistId: this.datalistId, 
          error: error.message 
        });
      }
    }
  }

  /**
   * Update display mode (to be overridden by subclasses)
   */
  updateDisplayMode() {
    // Default implementation - can be overridden
  }
}

/**
 * Systemwide table updater
 */
class SystemwideTableUpdater extends BaseTableUpdater {
  constructor() {
    super('systemwide-data');
  }

  updateTable(data, options = {}) {
    const tbody = document.querySelector('#systemwide-data tbody');
    if (!tbody) {
      logger.warn('systemwide-table-updater', 'Systemwide table tbody not found');
      return;
    }

    logger.debug('systemwide-table-updater', 'Updating table with data', data);

    // Get current date range from the data service
    const startDate = window.reportsDataService?.currentDateRange?.start || '';
    const endDate = window.reportsDataService?.currentDateRange?.end || '';

    // When locking registrations, keep existing registrations cell value
    let registrationsValue = data.registrations_count || 0;
    if (options.lockRegistrations) {
      const existingRegCell = document.querySelector('#systemwide-data tbody td:nth-child(3)');
      if (existingRegCell && existingRegCell.textContent) {
        const parsed = parseInt(existingRegCell.textContent, 10);
        if (!Number.isNaN(parsed)) {
          registrationsValue = parsed;
        }
      }
    }

    const html = `
      <tr>
        <td>${startDate}</td>
        <td>${endDate}</td>
        <td>${registrationsValue}</td>
        <td>${data.enrollments_count || 0}</td>
        <td>${data.certificates_count || 0}</td>
      </tr>
    `;
    
    tbody.innerHTML = html;
    logger.success('systemwide-table-updater', 'Systemwide table updated', {
      startDate,
      endDate,
      registrations: registrationsValue,
      enrollments: data.enrollments_count || 0,
      certificates: data.certificates_count || 0
    });
  }
}

/**
 * Organizations table updater
 */
class OrganizationsTableUpdater extends BaseTableUpdater {
  constructor() {
    super('organization-data', 'organization-search-datalist');
  }

  updateTable(data, options = {}) {
    if (!Array.isArray(data)) {
      logger.warn('organizations-table-updater', 'Organizations data is not an array');
      return;
    }

    // Use the data display options system instead of direct HTML updates
    try {
      // Import the data display options function dynamically to avoid circular imports
      import('./data-display-options.js').then(({ updateOrganizationTableWithDisplayMode }) => {
        updateOrganizationTableWithDisplayMode(data);
        logger.success('organizations-table-updater', 'Organizations table updated via data display options', { recordCount: data.length });
      }).catch(error => {
        logger.error('organizations-table-updater', 'Failed to import data display options', error);
        // Fallback to direct update if import fails
        this.updateTableDirectly(data, options);
      });
    } catch (error) {
      logger.error('organizations-table-updater', 'Failed to use data display options system', error);
      // Fallback to direct update
      this.updateTableDirectly(data, options);
    }
  }

  updateTableDirectly(data, options = {}) {
    const tbody = document.querySelector('#organization-data tbody');
    if (!tbody) {
      logger.warn('organizations-table-updater', 'Organizations table tbody not found');
      return;
    }

    const html = data.map(row => 
      `<tr>
        <td class="organization">${row.organization_display || row.organization || ''}</td>
        <td>${row.registrations || 0}</td>
        <td>${row.enrollments || 0}</td>
        <td>${row.certificates || 0}</td>
      </tr>`
    ).join('');
    
    tbody.innerHTML = html;
    
    // Use unified messaging system for fallback status message
    unifiedMessaging.showMessage('organization-data-display-message', 
      `Updated ${data.length} organizations`, 'info');
    
    logger.success('organizations-table-updater', 'Organizations table updated directly', { recordCount: data.length });
  }

  updateDisplayMode() {
    try {
      updateOrganizationTableWithDisplayMode();
    } catch (error) {
      logger.warn('organizations-table-updater', 'Failed to update organization display mode', error);
    }
  }
}

/**
 * Groups table updater
 */
class GroupsTableUpdater extends BaseTableUpdater {
  constructor() {
    super('groups-data', 'groups-search-datalist');
  }

  updateTable(data, options = {}) {
    // Check if groups are supported
    if (!window.HAS_GROUPS) {
      logger.info('groups-table-updater', 'Groups not supported, skipping groups table update');
      return;
    }

    if (!Array.isArray(data)) {
      logger.warn('groups-table-updater', 'Groups data is not an array');
      return;
    }

    // Use the data display options system instead of direct HTML updates
    try {
      // Import the data display options function dynamically to avoid circular imports
      import('./data-display-options.js').then(({ updateGroupsTableWithDisplayMode }) => {
        updateGroupsTableWithDisplayMode(data);
        logger.success('groups-table-updater', 'Groups table updated via data display options', { recordCount: data.length });
      }).catch(error => {
        logger.error('groups-table-updater', 'Failed to import data display options', error);
        // Fallback to direct update if import fails
        this.updateTableDirectly(data, options);
      });
    } catch (error) {
      logger.error('groups-table-updater', 'Failed to use data display options system', error);
      // Fallback to direct update
      this.updateTableDirectly(data, options);
    }
  }

  updateTableDirectly(data, options = {}) {
    const tbody = document.querySelector('#groups-data tbody');
    if (!tbody) {
      logger.warn('groups-table-updater', 'Groups table tbody not found');
      return;
    }

    const html = data.map(row => 
      `<tr>
        <td class="group">${row.group || ''}</td>
        <td>${row.registrations || 0}</td>
        <td>${row.enrollments || 0}</td>
        <td>${row.certificates || 0}</td>
      </tr>`
    ).join('');
    
    tbody.innerHTML = html;
    
    // Use unified messaging system for fallback status message
    unifiedMessaging.showMessage('groups-data-display-message', 
      `Updated ${data.length} groups`, 'info');
    
    logger.success('groups-table-updater', 'Groups table updated directly', { recordCount: data.length });
  }

  updateDisplayMode() {
    try {
      updateGroupsTableWithDisplayMode();
    } catch (error) {
      logger.warn('groups-table-updater', 'Failed to update groups display mode', error);
    }
  }
}

/**
 * MVP Unified Table Updater System
 * Simplified version without count options complexity
 * 
 * This MVP version eliminates mode switching and always uses hardcoded values:
 * - No mode change handling
 * - No complex table update logic
 * - Simple, direct table updates
 */

import { populateDatalistFromTable } from './datalist-utils.js';
import { logger, perfMonitor, logDataValidation } from './logging-utils.js';

export class MvpUnifiedTableUpdater {
  constructor() {
    this.tables = {
      systemwide: new MvpSystemwideTableUpdater(),
      organizations: new MvpOrganizationsTableUpdater(),
      groups: new MvpGroupsTableUpdater()
    };
  }

  /**
   * MVP Update All Tables - Simplified without mode complexity
   * 
   * @param {Object} data - Unified data from API
   */
  updateAllTables(data, options = {}) {
    perfMonitor.start('updateAllTables');
    
    try {
      logger.process('mvp-unified-table-updater', 'Starting MVP updateAllTables', { 
        hasSystemwide: !!data.systemwide,
        orgs: Array.isArray(data.organizations) ? data.organizations.length : 0,
        groups: Array.isArray(data.groups) ? data.groups.length : 0
      });
      
      // Update each table with MVP simplified logic
      if (data.systemwide) {
        logger.debug('mvp-unified-table-updater', 'Updating MVP systemwide table');
        this.tables.systemwide.updateTable(data.systemwide, options);
      } else {
        logger.warn('mvp-unified-table-updater', 'No systemwide data provided');
      }
      
      if (data.organizations) {
        logger.debug('mvp-unified-table-updater', 'Updating MVP organizations table');
        logDataValidation('mvp-unified-table-updater', 'organizations', data.organizations.length);
        this.tables.organizations.updateTable(data.organizations, options);
      } else {
        logger.warn('mvp-unified-table-updater', 'No organizations data provided');
      }
      
      if (data.groups) {
        logger.debug('mvp-unified-table-updater', 'Updating MVP groups table');
        logDataValidation('mvp-unified-table-updater', 'groups', data.groups.length);
        this.tables.groups.updateTable(data.groups, options);
      } else {
        logger.warn('mvp-unified-table-updater', 'No groups data provided');
      }
      
      const duration = perfMonitor.end('updateAllTables');
      logger.success('mvp-unified-table-updater', 'MVP updateAllTables completed', { 
        duration: duration ? `${duration.toFixed(2)}ms` : 'unknown'
      });
      
    } catch (error) {
      perfMonitor.end('updateAllTables');
      logger.error('mvp-unified-table-updater', 'MVP updateAllTables failed', error);
      throw error;
    }
  }

  /**
   * MVP Handle Enrollment Mode Change - Simplified
   * No actual mode changing, just logging
   */
  handleEnrollmentModeChange(newMode) {
    logger.info('mvp-unified-table-updater', 'MVP: Enrollment mode change ignored', {
      attemptedMode: newMode,
      actualMode: 'by-tou (hardcoded)'
    });
    
    // MVP: Do nothing - modes are hardcoded
    // In the full version, this would trigger table updates
  }
}

/**
 * MVP Systemwide Table Updater - Simplified
 */
class MvpSystemwideTableUpdater {
  constructor() {
    this.tableId = 'systemwide-data';
  }

  updateTable(data, options = {}) {
    const tbody = document.querySelector('#systemwide-data tbody');
    if (!tbody) {
      logger.warn('mvp-systemwide-table-updater', 'Systemwide table tbody not found');
      return;
    }

    logger.debug('mvp-systemwide-table-updater', 'Updating MVP systemwide table with data', data);

    // MVP: Always use hardcoded date range from current state
    const startDate = window.__lastStart || '';
    const endDate = window.__lastEnd || '';

    // MVP: Always use data as-is - no mode switching, no complex logic
    const registrationsValue = data.registrations_count || 0;
    const enrollmentsValue = data.enrollments_count || 0;
    const certificatesValue = data.certificates_count || 0;

    const html = `
      <tr>
        <td>${startDate}</td>
        <td>${endDate}</td>
        <td>${registrationsValue}</td>
        <td>${enrollmentsValue}</td>
        <td>${certificatesValue}</td>
      </tr>
    `;
    
    tbody.innerHTML = html;
    logger.success('mvp-systemwide-table-updater', 'MVP systemwide table updated', {
      startDate,
      endDate,
      registrations: registrationsValue,
      enrollments: enrollmentsValue,
      certificates: certificatesValue
    });
  }
}

/**
 * MVP Organizations Table Updater - Simplified
 */
class MvpOrganizationsTableUpdater {
  constructor() {
    this.tableId = 'organization-data';
  }

  updateTable(data, options = {}) {
    const tbody = document.querySelector('#organization-data tbody');
    if (!tbody) {
      logger.warn('mvp-organizations-table-updater', 'Organizations table tbody not found');
      return;
    }

    logger.debug('mvp-organizations-table-updater', 'Updating MVP organizations table', { count: data.length });

    if (!Array.isArray(data) || data.length === 0) {
      tbody.innerHTML = '<tr><td colspan="4">No organizations data available</td></tr>';
      return;
    }

    // MVP: Simple table update - no complex display mode logic
    const htmlString = data.map(row => {
      let displayName = row.organization;
      if (row.organization_display) {
        displayName = row.organization_display;
      } else if (typeof abbreviateOrganizationNameJS === 'function') {
        displayName = abbreviateOrganizationNameJS(row.organization);
      }
      return `<tr><td class="organization">${displayName}</td><td>${row.registrations}</td><td>${row.enrollments}</td><td>${row.certificates}</td></tr>`;
    }).join('');
    
    tbody.innerHTML = htmlString;
    logger.success('mvp-organizations-table-updater', 'MVP organizations table updated', { count: data.length });
  }
}

/**
 * MVP Groups Table Updater - Simplified
 */
class MvpGroupsTableUpdater {
  constructor() {
    this.tableId = 'groups-data';
  }

  updateTable(data, options = {}) {
    const tbody = document.querySelector('#groups-data tbody');
    if (!tbody) {
      logger.warn('mvp-groups-table-updater', 'Groups table tbody not found');
      return;
    }

    logger.debug('mvp-groups-table-updater', 'Updating MVP groups table', { count: data.length });

    if (!Array.isArray(data) || data.length === 0) {
      tbody.innerHTML = '<tr><td colspan="4">No groups data available</td></tr>';
      return;
    }

    // MVP: Simple table update - no complex display mode logic
    const htmlString = data.map(row => {
      let displayName = row.group;
      if (row.group_display) {
        displayName = row.group_display;
      } else if (typeof abbreviateOrganizationNameJS === 'function') {
        displayName = abbreviateOrganizationNameJS(row.group);
      }
      return `<tr><td class="group">${displayName}</td><td>${row.registrations}</td><td>${row.enrollments}</td><td>${row.certificates}</td></tr>`;
    }).join('');
    
    tbody.innerHTML = htmlString;
    logger.success('mvp-groups-table-updater', 'MVP groups table updated', { count: data.length });
  }
}

// Export for compatibility
export { MvpUnifiedTableUpdater as UnifiedTableUpdater };

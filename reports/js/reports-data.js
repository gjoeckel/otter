// reports-data.js
// Handles data fetching from API and updating of systemwide, organization, and groups tables. 

import { populateDatalistFromTable } from './datalist-utils.js';
import { updateOrganizationTableWithDisplayMode, updateGroupsTableWithDisplayMode } from './data-display-options.js';
import { showDataDisplayMessage, clearDataDisplayMessage, unifiedMessaging } from './data-display-utility.js';
import { ReportsDataService } from './unified-data-service.js';
import { UnifiedTableUpdater } from './unified-table-updater.js';
import { logger, perfMonitor, trackUserAction, logDataValidation } from './logging-utils.js';

// Module-level cache of last fetched data and active range for UI reactions
let __lastSummaryData = null;
let __lastStart = '';
let __lastEnd = '';

// Track widget initialization state to prevent re-wiring
let __enrollmentWidgetInitialized = false;

// Track if auto-switch has been performed to prevent multiple switches
let __enrollmentAutoSwitched = false;

// Debouncing mechanism to prevent infinite loops
let __updateTimeout = null;
let __lastUpdateParams = null;

// Function to check enrollment counts and auto-switch if needed
async function checkEnrollmentCountsAndAutoSwitch(start, end) {
  if (__enrollmentAutoSwitched) {
    logger.debug('reports-data', 'Auto-switch already performed, skipping');
    return;
  }

  logger.debug('reports-data', 'Checking enrollment counts for auto-switch logic');
  
  try {
    // First, check TOU completion mode count
    const touUrl = `reports_api.php?start_date=${encodeURIComponent(start)}&end_date=${encodeURIComponent(end)}&enrollment_mode=by-tou`;
    const touData = await fetchWithRetry(touUrl);
    const touEnrollmentCount = Array.isArray(touData.enrollments) ? touData.enrollments.length : 0;
    
    logger.debug('reports-data', 'TOU completion enrollment count', { count: touEnrollmentCount });
    
    if (touEnrollmentCount === 0) {
      logger.debug('reports-data', 'TOU completion count is 0, checking registration date mode');
      
      // Check registration date mode count
      const regUrl = `reports_api.php?start_date=${encodeURIComponent(start)}&end_date=${encodeURIComponent(end)}&enrollment_mode=by-registration`;
      const regData = await fetchWithRetry(regUrl);
      const regEnrollmentCount = Array.isArray(regData.enrollments) ? regData.enrollments.length : 0;
      
      logger.debug('reports-data', 'Registration date enrollment count', { count: regEnrollmentCount });
      
      if (regEnrollmentCount > 0) {
        logger.info('reports-data', 'Auto-switching to registration date mode');
        trackUserAction('auto_switch_enrollment_mode', { 
          from: 'by-tou', 
          to: 'by-registration', 
          reason: 'tou_count_zero' 
        });
        
        // Switch to registration date mode
        const registrationRadio = document.querySelector('input[name="systemwide-enrollments-display"][value="by-registration"]');
        const touRadio = document.querySelector('input[name="systemwide-enrollments-display"][value="by-tou"]');
        
        if (registrationRadio && touRadio) {
          // Switch to registration date mode
          registrationRadio.checked = true;
          
          // Disable TOU mode
          touRadio.disabled = true;
          touRadio.parentElement.style.opacity = '0.5';
          touRadio.parentElement.style.cursor = 'not-allowed';
          
          // Update status message
          const messageElement = document.getElementById('systemwide-enrollments-display-message');
          if (messageElement) {
            messageElement.classList.add('info-message');
            messageElement.innerHTML = 'Auto-switched to registration date mode (TOU completion mode had 0 enrollments)';
          }
          
          // Mark as auto-switched
          __enrollmentAutoSwitched = true;
          
          logger.success('reports-data', 'Auto-switch completed');
        }
      }
    }
  } catch (error) {
    logger.error('reports-data', 'Error checking enrollment counts', error);
  }
}

// Fetch with retry logic
async function fetchWithRetry(url, retries = 2, delay = 500) {
  for (let i = 0; i <= retries; i++) {
    try {
      const resp = await fetch(url);
      
      if (!resp.ok) {
        throw new Error(`Network error: ${resp.status} ${resp.statusText}`);
      }
      
      const data = await resp.json();
      
      if (typeof data !== 'object' || data === null) {
        throw new Error('Invalid data: not an object');
      }
      
      return data;
    } catch (err) {
      if (i === retries) {
        throw err;
      }
      await new Promise(res => setTimeout(res, delay));
    }
  }
}

// Legacy function removed - now handled by UnifiedTableUpdater

// Format cohort key (MM-YY) to label like "Aug 25"
function formatCohortLabel(key) {
  const [mmStr, yyStr] = key.split('-');
  const monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
  const monthName = monthNames[parseInt(mmStr, 10) - 1] || mmStr;
  return `${monthName} ${yyStr}`;
}

// Function to show cohort status message based on data
function showCohortStatusMessage(rows, messagePrefix, dataType) {
  const keySet = new Set();
  if (Array.isArray(rows)) {
    for (const row of rows) {
      const cohort = row?.[3];
      const year = row?.[4];
      if (!cohort || !year) continue;
      const key = `${String(cohort).padStart(2, '0')}-${String(year).padStart(2, '0')}`;
      keySet.add(key);
    }
  }

  if (keySet.size >= 1) {
    showDataDisplayMessage(messagePrefix, `Showing data for all ${dataType} submitted for cohort(s) in the date range`, 'info');
  } else {
    showDataDisplayMessage(messagePrefix, `Showing data for all ${dataType} submitted in date range`, 'info');
  }
}

// Show cohort status message for systemwide data
function showSystemwideCohortStatus(rows) {
  showCohortStatusMessage(rows, 'systemwide', 'registrations');
}


// Generic function to wire widget radio buttons
function wireWidgetRadiosGeneric(radioName, messagePrefix, dataType, defaultMode, updateCountFunction) {
  const radios = document.querySelectorAll(`input[name="${radioName}"]`);
  if (!radios || !radios.length) return;

  // Only enforce default mode if no radio is currently selected
  const currentSelection = Array.from(radios).find(r => r.checked);
  if (!currentSelection) {
    const defaultRadio = document.querySelector(`input[name="${radioName}"][value="${defaultMode}"]`);
    if (defaultRadio) {
      defaultRadio.checked = true;
    }
  }

  function updateStatusMessage() {
    const chosen = Array.from(radios).find(r => r.checked)?.value;
    if (chosen === 'by-cohort') {
      showDataDisplayMessage(messagePrefix, `Showing data for all ${dataType} submitted for cohort(s) in the date range`, 'info');
    } else {
      showDataDisplayMessage(messagePrefix, `Showing data for all ${dataType} submitted in date range`, 'info');
    }
  }

  async function applyMode(triggerDataRefresh = false) {
    const chosen = Array.from(radios).find(r => r.checked)?.value;
    const byCohort = chosen === 'by-cohort';
    updateStatusMessage();
    // Update count and report link on mode change
    await updateCountFunction();
    // If this is the systemwide registrations widget and user changed mode, refresh tables
    if (triggerDataRefresh && typeof window.fetchAndUpdateAllTables === 'function' && window.__lastStart && window.__lastEnd) {
      window.fetchAndUpdateAllTables(window.__lastStart, window.__lastEnd);
    }
  }

  radios.forEach(r => r.addEventListener('change', function() { applyMode(true); }));
  // Initialize state
  applyMode();
}

// Wire UI behavior for systemwide registrations widget
function wireSystemwideWidgetRadios() {
  wireWidgetRadiosGeneric('systemwide-data-display', 'systemwide', 'registrations', 'by-date', updateSystemwideCountAndLink);
  
  // Add date range change listener to disable cohort mode for "ALL" ranges
  setupCohortModeDisableForAllRange();
}

// Setup cohort mode disable functionality for "ALL" date ranges
function setupCohortModeDisableForAllRange() {
  // Listen for date range changes
  const startInput = document.getElementById('start-date');
  const endInput = document.getElementById('end-date');
  const presetRadios = document.querySelectorAll('input[name="date-preset"]');
  
  if (startInput && endInput) {
    const updateCohortModeAvailability = async () => {
      const start = startInput.value;
      const end = endInput.value;
      const isAllRange = await isDateRangeAll(start, end);
      
      const cohortRadio = document.querySelector('input[name="systemwide-data-display"][value="by-cohort"]');
      const cohortLabel = cohortRadio ? cohortRadio.closest('label') : null;
      
      if (cohortRadio && cohortLabel) {
        if (isAllRange) {
          // Disable cohort mode for "ALL" range
          cohortRadio.disabled = true;
          cohortLabel.classList.add('disabled-option');
          
          // If cohort mode is currently selected, switch to date mode
          if (cohortRadio.checked) {
            const dateRadio = document.querySelector('input[name="systemwide-data-display"][value="by-date"]');
            if (dateRadio) {
              dateRadio.checked = true;
              // Trigger the change event to update the UI
              dateRadio.dispatchEvent(new Event('change'));
            }
          }
          
          // Update status message to include cohort mode disabled info
          showDataDisplayMessage('systemwide', 'Showing data for all registrations submitted in date range - count by cohorts disabled', 'info');
        } else {
          // Re-enable cohort mode for specific ranges
          cohortRadio.disabled = false;
          cohortLabel.classList.remove('disabled-option');
        }
      }
    };
    
    // Add event listeners for direct input changes
    startInput.addEventListener('change', updateCohortModeAvailability);
    endInput.addEventListener('change', updateCohortModeAvailability);
    
    // Add event listeners for preset radio changes
    presetRadios.forEach(radio => {
      radio.addEventListener('change', () => {
        // Use a small delay to allow the preset to update the date inputs first
        setTimeout(() => updateCohortModeAvailability(), 100);
      });
    });
    
    // Initial check
    updateCohortModeAvailability();
  }
}

// Helper function to check if date range is "ALL"
async function isDateRangeAll(start, end) {
  if (!start || !end) return false;
  
  // Get min start date from enterprise data using the same method as other parts of the system
  const { getMinStartDate } = await import('../../lib/enterprise-utils.js');
  const minStartDate = await getMinStartDate();
  if (!minStartDate) return false;
  
  // Get today's date in MM-DD-YY format
  const today = new Date().toLocaleDateString('en-US', { 
    month: '2-digit', 
    day: '2-digit', 
    year: '2-digit' 
  }).replace(/\//g, '-');
  
  return (start === minStartDate && end === today);
}

// Wire UI behavior for enrollments (no cohort select)
function wireSystemwideEnrollmentsWidgetRadios() {
  logger.debug('reports-data', 'Starting wireSystemwideEnrollmentsWidgetRadios');
  logger.debug('reports-data', 'wireSystemwideEnrollmentsWidgetRadios: Starting initialization');
  
  const radios = document.querySelectorAll('input[name="systemwide-enrollments-display"]');
  logger.debug('reports-data', 'Found enrollment radios', { count: radios.length, radios: Array.from(radios).map(r => ({ value: r.value, checked: r.checked })) });
  
  if (!radios || !radios.length) {
    logger.error('reports-data', 'No enrollment radios found');
    return;
  }

  // Only set default if no radio button is already selected
  const selectedRadio = Array.from(radios).find(r => r.checked);
  if (!selectedRadio) {
    logger.debug('reports-data', 'No radio button selected, setting default to by-tou');
    const defaultRadio = document.querySelector('input[name="systemwide-enrollments-display"][value="by-tou"]');
    if (defaultRadio) {
      defaultRadio.checked = true;
      logger.debug('reports-data', 'Default radio set to checked');
    } else {
      logger.error('reports-data', 'Default radio not found');
    }
  } else {
    logger.debug('reports-data', 'Radio button already selected', { value: selectedRadio.value });
  }

  function updateStatusMessage() {
    logger.debug('reports-data', 'updateStatusMessage: Starting');
    const chosen = Array.from(radios).find(r => r.checked)?.value;
    logger.debug('reports-data', 'Chosen radio value', { chosen });
    
    // Use unified messaging system instead of direct DOM manipulation
    const message = chosen === 'by-tou' 
      ? 'Showing data for all TOU completions in the date range'
      : 'Showing data for all enrollees that registered in the date range';
    
    unifiedMessaging.showMessage('systemwide-enrollments-display-message', message, 'info');
  }

  function applyMode(triggerDataRefresh = false) {
    logger.debug('reports-data', 'applyMode: Starting', { triggerDataRefresh });
    updateStatusMessage();
    updateSystemwideEnrollmentsCountAndLink();
    
    // Only trigger data refresh when user actually changes the radio button
    if (triggerDataRefresh && window.__lastStart && window.__lastEnd) {
      logger.debug('reports-data', 'applyMode: Triggering data refresh for enrollment mode change');
      
      // Use unified system if available, otherwise fall back to legacy
      if (window.unifiedTableUpdater) {
        const chosen = Array.from(radios).find(r => r.checked)?.value;
        logger.debug('reports-data', 'applyMode: Using unified system for enrollment mode change', { chosen });
        window.unifiedTableUpdater.handleEnrollmentModeChange(chosen);
      } else if (typeof window.fetchAndUpdateAllTables === 'function') {
        logger.debug('reports-data', 'applyMode: Using legacy system for enrollment mode change');
        window.fetchAndUpdateAllTables(window.__lastStart, window.__lastEnd);
      }
    }
    
    logger.debug('reports-data', 'applyMode: Completed');
  }

  logger.debug('reports-data', 'Adding event listeners to radios');
  radios.forEach((r, index) => {
    logger.debug('reports-data', 'Adding listener to radio', { index, value: r.value, checked: r.checked });
    r.addEventListener('change', function() {
      logger.debug('reports-data', 'Radio changed', { index, value: this.value });
      applyMode(true); // Trigger data refresh when user changes radio button
    });
  });
  
  // Initialize state (same pattern as generic function)
  logger.debug('reports-data', 'Calling applyMode() for initialization');
  applyMode();
  logger.debug('reports-data', 'wireSystemwideEnrollmentsWidgetRadios: Completed initialization');
}

// Reset both widgets to their default states when date range is edited
function resetWidgetsToDefaults() {
  // Reset enrollment widget initialization flag
  __enrollmentWidgetInitialized = false;
  // Reset auto-switch flag to allow re-checking
  __enrollmentAutoSwitched = false;
  // Reset registrations widget to by-date default
  const registrationsByDate = document.querySelector('input[name="systemwide-data-display"][value="by-date"]');
  if (registrationsByDate) {
    registrationsByDate.checked = true;
  }
  
  // Reset enrollments widget to by-tou default (TOU completion date)
  const enrollmentsByTou = document.querySelector('input[name="systemwide-enrollments-display"][value="by-tou"]');
  const enrollmentsByRegistration = document.querySelector('input[name="systemwide-enrollments-display"][value="by-registration"]');
  
  if (enrollmentsByTou) {
    enrollmentsByTou.checked = true;
    // Re-enable TOU mode if it was disabled
    enrollmentsByTou.disabled = false;
    enrollmentsByTou.parentElement.style.opacity = '1';
    enrollmentsByTou.parentElement.style.cursor = 'default';
  }
  
  if (enrollmentsByRegistration) {
    // Uncheck registration mode
    enrollmentsByRegistration.checked = false;
  }
  
  // Update status messages
  showDataDisplayMessage('systemwide', 'Showing data for all registrations submitted in date range', 'info');
  showDataDisplayMessage('systemwide-enrollments', 'Showing data for all TOU completions in the date range', 'info');
}

// Helpers to compute counts and update UI/link
function getCohortKeysFromRange(start, end) {
  const sMM = parseInt(start.slice(0, 2), 10);
  const sYY = parseInt(start.slice(6, 8), 10);
  const eMM = parseInt(end.slice(0, 2), 10);
  const eYY = parseInt(end.slice(6, 8), 10);
  const keys = [];
  let mm = sMM, yy = sYY;
  while (yy < eYY || (yy === eYY && mm <= eMM)) {
    keys.push(`${String(mm).padStart(2,'0')}-${String(yy).padStart(2,'0')}`);
    mm += 1; if (mm > 12) { mm = 1; yy += 1; }
  }
  return keys;
}

// Filter cohort data by date range (reuses existing getCohortKeysFromRange function)
async function filterCohortDataByDateRange(allSubmissions, start, end) {
  // Check if this is an "ALL" range - if so, return all submissions without filtering
  // "ALL" range is defined as from min start date to today
  const isAllRange = await isDateRangeAll(start, end);
  
  if (isAllRange) {
    // For "ALL" range: return all submissions (no cohort filtering)
    return allSubmissions;
  }
  
  // For specific ranges: filter by cohort/year combinations
  const cohortKeys = getCohortKeysFromRange(start, end);
  const cohortKeySet = new Set(cohortKeys);
  
  return allSubmissions.filter(row => {
    const cohort = row?.[3];
    const year = row?.[4];
    if (!cohort || !year) return false;
    
    const key = `${String(cohort).padStart(2,'0')}-${String(year).padStart(2,'0')}`;
    return cohortKeySet.has(key);
  });
}

function buildCohortYearCountsFromRows(rows) {
  const counts = new Map();
  if (!Array.isArray(rows)) return counts;
  for (const row of rows) {
    const cohort = row?.[3];
    const year = row?.[4];
    if (!cohort || !year) continue;
    const key = `${String(cohort).padStart(2,'0')}-${String(year).padStart(2,'0')}`;
    counts.set(key, (counts.get(key) || 0) + 1);
  }
  return counts;
}

function setSystemwideRegistrationsCell(value) {
  const cell = document.querySelector('#systemwide-data tbody td:nth-child(3)');
  if (cell) cell.textContent = String(value);
}

function updateRegistrantsReportLink(mode, cohort) {
  const link = document.getElementById('registrations-report-link');
  if (!link) return;
  const start = encodeURIComponent(__lastStart);
  const end = encodeURIComponent(__lastEnd);
  const base = `registrants.php?start_date=${start}&end_date=${end}`;
  if (mode === 'by-cohort') {
    const c = cohort || '';
    link.href = `${base}&mode=cohort&cohort=${encodeURIComponent(c)}`;
  } else {
    link.href = `${base}&mode=date`;
  }
}

function setSystemwideEnrollmentsCell(value) {
  const cell = document.querySelector('#systemwide-data tbody td:nth-child(4)');
  if (cell) cell.textContent = String(value);
}

function updateEnrolleesReportLink(mode, cohort) {
  const link = document.getElementById('enrollments-report-link');
  if (!link) return;
  const start = encodeURIComponent(__lastStart);
  const end = encodeURIComponent(__lastEnd);
  const base = `enrollees.php?start_date=${start}&end_date=${end}`;
  
  // Add enrollment mode parameter
  const enrollmentMode = encodeURIComponent(mode || 'by-tou');
  const baseWithMode = `${base}&enrollment_mode=${enrollmentMode}`;
  
  if (mode === 'by-cohort') {
    const c = cohort || '';
    link.href = `${baseWithMode}&mode=cohort&cohort=${encodeURIComponent(c)}`;
  } else {
    link.href = `${baseWithMode}&mode=date`;
  }
}

// Generic function to update count and report link
async function updateCountAndLinkGeneric(radioName, setCellFunction, updateLinkFunction) {
  const radios = document.querySelectorAll(`input[name="${radioName}"]`);
  const chosen = Array.from(radios).find(r => r.checked)?.value;
  const byCohort = chosen === 'by-cohort';
  
  if (!byCohort) {
    // Date mode: use pre-filtered submissions
    const rows = __lastSummaryData && Array.isArray(__lastSummaryData.submissions) ? __lastSummaryData.submissions : [];
    setCellFunction(rows.length || 0);
    updateLinkFunction('by-date', '');
    return;
  }
  
  // Cohort mode: filter all submissions by cohort/year range
  const allRows = __lastSummaryData && Array.isArray(__lastSummaryData.cohortModeSubmissions) ? __lastSummaryData.cohortModeSubmissions : [];
  const cohortFilteredRows = await filterCohortDataByDateRange(allRows, __lastStart, __lastEnd);
  setCellFunction(cohortFilteredRows.length || 0);
  updateLinkFunction('by-cohort', 'ALL');
}

async function updateSystemwideCountAndLink() {
  await updateCountAndLinkGeneric('systemwide-data-display', setSystemwideRegistrationsCell, updateRegistrantsReportLink);
}

function updateSystemwideEnrollmentsCountAndLink() {
  // Update enrollment count and link based on radio selection
  const radios = document.querySelectorAll('input[name="systemwide-enrollments-display"]');
  const chosen = Array.from(radios).find(r => r.checked)?.value;
  
  // Use actual enrollment data from the API response
  const enrollmentRows = __lastSummaryData && Array.isArray(__lastSummaryData.enrollments) ? __lastSummaryData.enrollments : [];
  const enrollmentCount = enrollmentRows.length || 0;
  
  // Use the selected mode for the link
  setSystemwideEnrollmentsCell(enrollmentCount);
  updateEnrolleesReportLink(chosen || 'by-tou', '');
}


// Legacy functions removed - now handled by UnifiedTableUpdater

// Legacy function removed - now handled by UnifiedTableUpdater

function updateDataTable(tableId, datalistId, data, rowClass, columns, emptyMsg) {
  const tbody = document.querySelector(`#${tableId} tbody`);
  
  if (!tbody) {
    return;
  }
  
  if (!Array.isArray(data) || data.length === 0) {
    tbody.innerHTML = `<tr><td colspan="${columns}">${emptyMsg}</td></tr>`;
    return;
  }
  
  const htmlString = data.map(row => {
    // Apply abbreviation to organization/group names
    let displayName = row[rowClass];
    if (rowClass === 'organization' && row.organization_display) {
      // Use server-side abbreviated name if available
      displayName = row.organization_display;
    } else if (typeof abbreviateOrganizationNameJS === 'function') {
      // Fallback to client-side abbreviation
      displayName = abbreviateOrganizationNameJS(row[rowClass]);
    }
    return `<tr><td class="${rowClass}">${displayName}</td><td>${row.registrations}</td><td>${row.enrollments}</td><td>${row.certificates}</td></tr>`;
  }).join('');
  
  tbody.innerHTML = htmlString;
}

// Export the reset function for use by date range picker
export { resetWidgetsToDefaults };

// Make fetchAndUpdateAllTables and date variables globally available
if (typeof window !== 'undefined') {
  window.fetchAndUpdateAllTables = fetchAndUpdateAllTables;
  window.__lastStart = __lastStart;
  window.__lastEnd = __lastEnd;
}

// Debounced version to prevent infinite loops
function debouncedFetchAndUpdateAllTables(start, end) {
  const params = `${start}-${end}`;
  
  // Clear existing timeout
  if (__updateTimeout) {
    clearTimeout(__updateTimeout);
  }
  
  // If same parameters, don't update again
  if (__lastUpdateParams === params) {
    logger.debug('reports-data', 'Debounce: Same parameters, skipping update');
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

// Internal function that does the actual work
async function fetchAndUpdateAllTablesInternal(start, end) {
  perfMonitor.start('fetchAndUpdateAllTables');
  
  try {
    logger.process('reports-data', 'Starting fetchAndUpdateAllTables', { start, end });
    
    // Initialize services if not already done
    if (!window.reportsDataService) {
      window.reportsDataService = new ReportsDataService();
    }
    if (!window.unifiedTableUpdater) {
      window.unifiedTableUpdater = new UnifiedTableUpdater();
    }
    
    // Check enrollment counts and auto-switch if needed BEFORE getting current mode
    await checkEnrollmentCountsAndAutoSwitch(start, end);
    
    // Get current enrollment mode from radio buttons (may have been auto-switched)
    const enrollmentRadios = document.querySelectorAll('input[name="systemwide-enrollments-display"]');
    const enrollmentMode = Array.from(enrollmentRadios).find(r => r.checked)?.value || 'by-tou';
    
    // Single service call updates all tables
    await window.reportsDataService.updateAllTables(start, end, enrollmentMode);
    
    // Update legacy variables for backward compatibility
    __lastStart = start;
    __lastEnd = end;
    
    // Update global variables
    if (typeof window !== 'undefined') {
      window.__lastStart = __lastStart;
      window.__lastEnd = __lastEnd;
    }
    
    // Get data for legacy UI updates (still need some individual data for UI components)
    // Make parallel calls for both date and cohort modes
    const [summaryData, cohortModeData] = await Promise.all([
        fetchWithRetry(`reports_api.php?start_date=${encodeURIComponent(start)}&end_date=${encodeURIComponent(end)}&enrollment_mode=${encodeURIComponent(enrollmentMode)}`),
        fetchWithRetry(`reports_api.php?start_date=${encodeURIComponent(start)}&end_date=${encodeURIComponent(end)}&enrollment_mode=${encodeURIComponent(enrollmentMode)}&cohort_mode=true`)
    ]);
    
    // Update legacy variables with both datasets
    __lastSummaryData = {
        ...summaryData,
        cohortModeSubmissions: cohortModeData.submissions || []
    };
    
    // Log data validation
    const submissionRows = Array.isArray(summaryData.submissions) ? summaryData.submissions : [];
    logDataValidation('reports-data', 'submissions', submissionRows.length);
    
    // Show cohort status message based on actual data rows
    showSystemwideCohortStatus(submissionRows);
    wireSystemwideWidgetRadios();
    // Force default mode and update count/link to by-date
    await updateSystemwideCountAndLink();
    
    // Wire enrollments widget only if not already initialized (no cohort select needed)
    if (!__enrollmentWidgetInitialized) {
      logger.debug('reports-data', 'Wiring enrollment widget for first time');
      wireSystemwideEnrollmentsWidgetRadios();
      __enrollmentWidgetInitialized = true;
      logger.debug('reports-data', 'Completed wireSystemwideEnrollmentsWidgetRadios');
    } else {
      logger.debug('reports-data', 'Enrollment widget already initialized, skipping');
    }
    
    // Update enrollment count and link using default TOU completion mode
    logger.debug('reports-data', 'About to call updateSystemwideEnrollmentsCountAndLink');
    updateSystemwideEnrollmentsCountAndLink();
    logger.debug('reports-data', 'Completed updateSystemwideEnrollmentsCountAndLink');
    
    const duration = perfMonitor.end('fetchAndUpdateAllTables');
    logger.success('reports-data', 'fetchAndUpdateAllTables completed', { duration: `${duration.toFixed(2)}ms` });
    
  } catch (error) {
    perfMonitor.end('fetchAndUpdateAllTables');
    logger.error('reports-data', 'Failed to fetch and update tables', error);
    throw error;
  }
}

// Main exported function - now using debounced version
export async function fetchAndUpdateAllTables(start, end) {
  return debouncedFetchAndUpdateAllTables(start, end);
}

// Unified enrollment mode change handler
export function handleEnrollmentModeChange(newMode) {
  trackUserAction('enrollment_mode_change', { 
    newMode, 
    previousMode: window.reportsDataService?.currentEnrollmentMode 
  });
  
  if (window.unifiedTableUpdater) {
    window.unifiedTableUpdater.handleEnrollmentModeChange(newMode);
  }
} 
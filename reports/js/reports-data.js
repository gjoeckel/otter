// reports-data.js
// Handles data fetching from API and updating of systemwide, organization, and groups tables. 

import { populateDatalistFromTable } from './datalist-utils.js';
import { updateOrganizationTableWithDisplayMode, updateGroupsTableWithDisplayMode } from './data-display-options.js';
import { showDataDisplayMessage, clearDataDisplayMessage } from './data-display-utility.js';

// Module-level cache of last fetched data and active range for UI reactions
let __lastSummaryData = null;
let __lastStart = '';
let __lastEnd = '';

// Track widget initialization state to prevent re-wiring
let __enrollmentWidgetInitialized = false;

// Track if auto-switch has been performed to prevent multiple switches
let __enrollmentAutoSwitched = false;

// Function to check enrollment counts and auto-switch if needed
async function checkEnrollmentCountsAndAutoSwitch(start, end) {
  if (__enrollmentAutoSwitched) {
    console.log('🔧 Auto-switch already performed, skipping');
    return;
  }

  console.log('🔧 Checking enrollment counts for auto-switch logic');
  
  try {
    // First, check TOU completion mode count
    const touUrl = `reports_api.php?start_date=${encodeURIComponent(start)}&end_date=${encodeURIComponent(end)}&enrollment_mode=by-tou`;
    const touData = await fetchWithRetry(touUrl);
    const touEnrollmentCount = Array.isArray(touData.enrollments) ? touData.enrollments.length : 0;
    
    console.log('🔧 TOU completion enrollment count:', touEnrollmentCount);
    
    if (touEnrollmentCount === 0) {
      console.log('🔧 TOU completion count is 0, checking registration date mode');
      
      // Check registration date mode count
      const regUrl = `reports_api.php?start_date=${encodeURIComponent(start)}&end_date=${encodeURIComponent(end)}&enrollment_mode=by-registration`;
      const regData = await fetchWithRetry(regUrl);
      const regEnrollmentCount = Array.isArray(regData.enrollments) ? regData.enrollments.length : 0;
      
      console.log('🔧 Registration date enrollment count:', regEnrollmentCount);
      
      if (regEnrollmentCount > 0) {
        console.log('🔧 Auto-switching to registration date mode');
        
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
          
          console.log('🔧 Auto-switch completed');
        }
      }
    }
  } catch (error) {
    console.error('🔧 Error checking enrollment counts:', error);
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

function updateSystemwideTable(start, end, data) {
  const tbody = document.querySelector('#systemwide-data tbody');
  
  if (!tbody) {
    return;
  }
  
  // Count the arrays to get summary numbers
  const registrationsCount = Array.isArray(data.registrations) ? data.registrations.length : 0;
  const enrollmentsCount = Array.isArray(data.enrollments) ? data.enrollments.length : 0;
  const certificatesCount = Array.isArray(data.certificates) ? data.certificates.length : 0;
  
  const html = `<tr><td>${start}</td><td>${end}</td><td>${registrationsCount}</td><td>${enrollmentsCount}</td><td>${certificatesCount}</td></tr>`;
  
  tbody.innerHTML = html;
}

// Format cohort key (MM-YY) to label like "Aug 25"
function formatCohortLabel(key) {
  const [mmStr, yyStr] = key.split('-');
  const monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
  const monthName = monthNames[parseInt(mmStr, 10) - 1] || mmStr;
  return `${monthName} ${yyStr}`;
}

// Generic function to build cohort keys and populate any cohort select
function populateCohortSelectGeneric(rows, selectId, messagePrefix, dataType) {
  const select = document.getElementById(selectId);
  if (!select) return;

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

  const keysDesc = Array.from(keySet).sort((a, b) => {
    const [am, ay] = a.split('-').map(n => parseInt(n, 10));
    const [bm, by] = b.split('-').map(n => parseInt(n, 10));
    if (ay !== by) return by - ay;
    return bm - am;
  });

  let options = '';
  if (keysDesc.length > 1) {
    options += '<option value="">Select cohort</option>';
    options += '<option value="ALL">Select All</option>';
  }
  options += keysDesc.map(k => `<option value="${k}">${formatCohortLabel(k)}</option>`).join('');
  select.innerHTML = options || '<option value="">Select cohort</option>';

  if (keysDesc.length === 1) {
    select.value = keysDesc[0];
    showDataDisplayMessage(messagePrefix, `Showing data for all ${dataType} submitted for ${formatCohortLabel(keysDesc[0])} cohort`, 'info');
  } else {
    select.value = '';
    showDataDisplayMessage(messagePrefix, `Showing data for all ${dataType} submitted in date range`, 'info');
  }

  return keysDesc;
}

// UI-only: Build cohort keys (MM-YY) from actual data rows in the selected range and populate #cohort-select
function populateCohortSelectFromData(rows) {
  return populateCohortSelectGeneric(rows, 'cohort-select', 'systemwide', 'registrations');
}


// Generic function to wire widget radio buttons and cohort select
function wireWidgetRadiosGeneric(radioName, selectId, messagePrefix, dataType, defaultMode, updateCountFunction) {
  const radios = document.querySelectorAll(`input[name="${radioName}"]`);
  const select = document.getElementById(selectId);
  if (!radios || !radios.length || !select) return;

  // Enforce default mode on init
  const defaultRadio = document.querySelector(`input[name="${radioName}"][value="${defaultMode}"]`);
  if (defaultRadio) {
    defaultRadio.checked = true;
  }

  function updateStatusMessage() {
    const chosen = Array.from(radios).find(r => r.checked)?.value;
    const val = select.value;
    if (chosen === 'by-cohort') {
      if (val === 'ALL') {
        showDataDisplayMessage(messagePrefix, `Showing data for all ${dataType} submitted for cohorts in the date range`, 'info');
      } else if (val) {
        showDataDisplayMessage(messagePrefix, `Showing data for all ${dataType} submitted for ${formatCohortLabel(val)} cohort`, 'info');
      } else {
        showDataDisplayMessage(messagePrefix, 'Please choose an option from the Select cohort menu', 'info');
      }
    } else {
      showDataDisplayMessage(messagePrefix, `Showing data for all ${dataType} submitted in date range`, 'info');
    }
  }

  function applyMode(triggerDataRefresh = false) {
    const chosen = Array.from(radios).find(r => r.checked)?.value;
    const byCohort = chosen === 'by-cohort';
    select.disabled = !byCohort;
    updateStatusMessage();
    // Update count and report link on mode change
    updateCountFunction();
    // If this is the systemwide registrations widget and user changed mode, refresh tables
    if (triggerDataRefresh && typeof window.fetchAndUpdateAllTables === 'function' && window.__lastStart && window.__lastEnd) {
      window.fetchAndUpdateAllTables(window.__lastStart, window.__lastEnd);
    }
  }

  radios.forEach(r => r.addEventListener('change', function() { applyMode(true); }));
  select.addEventListener('change', function() {
    updateStatusMessage();
    updateCountFunction();
  });
  // Initialize state
  applyMode();
}

// Wire UI behavior: enable select only when "by-cohort" selected
function wireSystemwideWidgetRadios() {
  wireWidgetRadiosGeneric('systemwide-data-display', 'cohort-select', 'systemwide', 'registrations', 'by-date', updateSystemwideCountAndLink);
}

// Wire UI behavior for enrollments (no cohort select)
function wireSystemwideEnrollmentsWidgetRadios() {
  console.log('🚀 ENROLLMENT WIDGET: Starting wireSystemwideEnrollmentsWidgetRadios');
  console.log('🔧 wireSystemwideEnrollmentsWidgetRadios: Starting initialization');
  
  const radios = document.querySelectorAll('input[name="systemwide-enrollments-display"]');
  console.log('🔧 Found enrollment radios:', radios.length, radios);
  
  if (!radios || !radios.length) {
    console.error('❌ No enrollment radios found!');
    return;
  }

  // Only set default if no radio button is already selected
  const selectedRadio = Array.from(radios).find(r => r.checked);
  if (!selectedRadio) {
    console.log('🔧 No radio button selected, setting default to by-tou');
    const defaultRadio = document.querySelector('input[name="systemwide-enrollments-display"][value="by-tou"]');
    if (defaultRadio) {
      defaultRadio.checked = true;
      console.log('🔧 Default radio set to checked');
    } else {
      console.error('❌ Default radio not found!');
    }
  } else {
    console.log('🔧 Radio button already selected:', selectedRadio.value);
  }

  function updateStatusMessage() {
    console.log('🔧 updateStatusMessage: Starting');
    const chosen = Array.from(radios).find(r => r.checked)?.value;
    console.log('🔧 Chosen radio value:', chosen);
    
    // Check if showDataDisplayMessage is available
    console.log('🔧 showDataDisplayMessage function type:', typeof showDataDisplayMessage);
    console.log('🔧 window.showDataDisplayMessage function type:', typeof window.showDataDisplayMessage);
    
    // Direct approach for enrollment messages (bypassing generic function)
    const messageElement = document.getElementById('systemwide-enrollments-display-message');
    console.log('🔧 Direct message element found:', messageElement);
    
    if (messageElement) {
      // Clear any existing classes
      messageElement.classList.remove('error-message', 'success-message', 'info-message', 'warning-message');
      
      if (chosen === 'by-tou') {
        console.log('🔧 Setting TOU completion message directly');
        messageElement.classList.add('info-message');
        messageElement.innerHTML = 'Showing data for all TOU completions in the date range';
        messageElement.setAttribute('aria-live', 'polite');
      } else {
        console.log('🔧 Setting registration date message directly');
        messageElement.classList.add('info-message');
        messageElement.innerHTML = 'Showing data for all enrollees that registered in the date range';
        messageElement.setAttribute('aria-live', 'polite');
      }
      
      console.log('🔧 Direct message element after update:', {
        className: messageElement.className,
        innerHTML: messageElement.innerHTML,
        style: messageElement.style.display,
        offsetHeight: messageElement.offsetHeight,
        offsetWidth: messageElement.offsetWidth
      });
    } else {
      console.error('❌ Direct message element not found!');
    }
    
    // Check if message container exists
    const messageContainer = document.getElementById('systemwide-enrollments-display-message');
    console.log('🔧 Message container found:', messageContainer);
    if (messageContainer) {
      console.log('🔧 Message container innerHTML:', messageContainer.innerHTML);
    } else {
      console.error('❌ Message container not found!');
    }
  }

  function applyMode(triggerDataRefresh = false) {
    console.log('🔧 applyMode: Starting, triggerDataRefresh:', triggerDataRefresh);
    updateStatusMessage();
    updateSystemwideEnrollmentsCountAndLink();
    
    // Only trigger data refresh when user actually changes the radio button
    if (triggerDataRefresh && typeof window.fetchAndUpdateAllTables === 'function' && window.__lastStart && window.__lastEnd) {
      console.log('🔧 applyMode: Triggering data refresh for enrollment mode change');
      window.fetchAndUpdateAllTables(window.__lastStart, window.__lastEnd);
    }
    
    console.log('🔧 applyMode: Completed');
  }

  console.log('🔧 Adding event listeners to radios');
  radios.forEach((r, index) => {
    console.log(`🔧 Adding listener to radio ${index}:`, r.value, r.checked);
    r.addEventListener('change', function() {
      console.log(`🔧 Radio ${index} changed to:`, this.value);
      applyMode(true); // Trigger data refresh when user changes radio button
    });
  });
  
  // Initialize state (same pattern as generic function)
  console.log('🔧 Calling applyMode() for initialization');
  applyMode();
  console.log('🔧 wireSystemwideEnrollmentsWidgetRadios: Completed initialization');
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
  const registrationsSelect = document.getElementById('cohort-select');
  if (registrationsSelect) {
    registrationsSelect.disabled = true;
    registrationsSelect.value = '';
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
function updateCountAndLinkGeneric(radioName, selectId, setCellFunction, updateLinkFunction) {
  const radios = document.querySelectorAll(`input[name="${radioName}"]`);
  const select = document.getElementById(selectId);
  const chosen = Array.from(radios).find(r => r.checked)?.value;
  const byCohort = chosen === 'by-cohort';
  // Use submissions rows for counts to match report logic
  const rows = __lastSummaryData && Array.isArray(__lastSummaryData.submissions) ? __lastSummaryData.submissions : [];
  if (!byCohort) {
    setCellFunction(rows.length || 0);
    updateLinkFunction('by-date', '');
    return;
  }
  const cohortValue = select ? select.value : '';
  const counts = buildCohortYearCountsFromRows(rows);
  if (cohortValue === 'ALL') {
    let total = 0;
    counts.forEach(v => { total += v; });
    setCellFunction(total);
    updateLinkFunction('by-cohort', 'ALL');
  } else if (cohortValue) {
    const n = counts.get(cohortValue) || 0;
    setCellFunction(n);
    updateLinkFunction('by-cohort', cohortValue);
  } else {
    // No selection yet; show 0 to prompt selection
    setCellFunction(0);
    updateLinkFunction('by-cohort', '');
  }
}

function updateSystemwideCountAndLink() {
  updateCountAndLinkGeneric('systemwide-data-display', 'cohort-select', setSystemwideRegistrationsCell, updateRegistrantsReportLink);
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


function updateOrganizationTable(organizationData) {
  // Use the new display mode filtering
  updateOrganizationTableWithDisplayMode(organizationData);
  populateDatalistFromTable('organization-data', 'organization-search-datalist');
}

function updateGroupsTable(groupsData) {
  // Only update groups table if enterprise has groups (CCC)
  if (!window.HAS_GROUPS) {
    return;
  }
  
  // Use the new display mode filtering
  updateGroupsTableWithDisplayMode(groupsData);
  populateDatalistFromTable('groups-data', 'groups-search-datalist');
}

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

// Main exported function
export async function fetchAndUpdateAllTables(start, end) {
  try {
    // Check enrollment counts and auto-switch if needed BEFORE getting current mode
    await checkEnrollmentCountsAndAutoSwitch(start, end);
    
    // Get current enrollment mode from radio buttons (may have been auto-switched)
    const enrollmentRadios = document.querySelectorAll('input[name="systemwide-enrollments-display"]');
    const enrollmentMode = Array.from(enrollmentRadios).find(r => r.checked)?.value || 'by-tou';
    
    const summaryUrl = `reports_api.php?start_date=${encodeURIComponent(start)}&end_date=${encodeURIComponent(end)}&enrollment_mode=${encodeURIComponent(enrollmentMode)}`;
    const organizationUrl = `reports_api.php?start_date=${encodeURIComponent(start)}&end_date=${encodeURIComponent(end)}&organization_data=1`;
    
    const summaryData = await fetchWithRetry(summaryUrl);
    const organizationData = await fetchWithRetry(organizationUrl);
    
    updateSystemwideTable(start, end, summaryData);
    // Ensure default count by date is shown immediately
    __lastSummaryData = summaryData;
    __lastStart = start;
    __lastEnd = end;
    
    // Update global variables
    if (typeof window !== 'undefined') {
      window.__lastStart = __lastStart;
      window.__lastEnd = __lastEnd;
    }
    // Populate from actual data rows to include advance registrations
    const submissionRows = Array.isArray(summaryData.submissions) ? summaryData.submissions : [];
    populateCohortSelectFromData(submissionRows);
    wireSystemwideWidgetRadios();
    // Force default mode and update count/link to by-date
    updateSystemwideCountAndLink();
    
    // Wire enrollments widget only if not already initialized (no cohort select needed)
    if (!__enrollmentWidgetInitialized) {
      console.log('🚀 MAIN: Wiring enrollment widget for first time');
      wireSystemwideEnrollmentsWidgetRadios();
      __enrollmentWidgetInitialized = true;
      console.log('🚀 MAIN: Completed wireSystemwideEnrollmentsWidgetRadios');
    } else {
      console.log('🚀 MAIN: Enrollment widget already initialized, skipping');
    }
    
    // Update enrollment count and link using default TOU completion mode
    console.log('🚀 MAIN: About to call updateSystemwideEnrollmentsCountAndLink');
    updateSystemwideEnrollmentsCountAndLink();
    console.log('🚀 MAIN: Completed updateSystemwideEnrollmentsCountAndLink');
    // Cache already set above
    
    // Handle both possible response structures:
    // 1. organizationData.organization_data (nested structure)
    // 2. organizationData (direct structure)
    const orgData = organizationData.organization_data || organizationData;
    updateOrganizationTable(orgData);
    
    // Only fetch groups data if enterprise has groups (CCC)
    if (window.HAS_GROUPS) {
      const groupsUrl = `reports_api.php?start_date=${encodeURIComponent(start)}&end_date=${encodeURIComponent(end)}&groups_data=1`;
      
      const groupsData = await fetchWithRetry(groupsUrl);
      updateGroupsTable(groupsData.groups_data);
    }
  } catch (error) {
    console.error('Failed to fetch and update tables:', error);
    throw error;
  }
} 
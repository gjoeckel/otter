// reports-data.js
// Handles data fetching from API and updating of systemwide, organization, and groups tables. 

import { populateDatalistFromTable } from './datalist-utils.js';
import { updateOrganizationTableWithDisplayMode, updateGroupsTableWithDisplayMode } from './data-display-options.js';
import { showDataDisplayMessage, clearDataDisplayMessage } from './data-display-utility.js';

// Module-level cache of last fetched data and active range for UI reactions
let __lastSummaryData = null;
let __lastStart = '';
let __lastEnd = '';

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

// UI-only: Build cohort keys (MM-YY) inclusive from start..end and populate #cohort-select
function populateCohortSelectFromRange(start, end) {
  const select = document.getElementById('cohort-select');
  if (!select || !start || !end) return;

  const sMM = parseInt(start.slice(0, 2), 10);
  const sYY = parseInt(start.slice(6, 8), 10);
  const eMM = parseInt(end.slice(0, 2), 10);
  const eYY = parseInt(end.slice(6, 8), 10);

  // Ascending keys then reverse to meet descending order requirement
  const asc = [];
  let mm = sMM, yy = sYY;
  while (yy < eYY || (yy === eYY && mm <= eMM)) {
    asc.push(`${String(mm).padStart(2, '0')}-${String(yy).padStart(2, '0')}`);
    mm += 1;
    if (mm > 12) { mm = 1; yy += 1; }
  }
  const keysDesc = asc.reverse();

  // Build options

  let options = '';
  if (keysDesc.length > 1) {
    options += '<option value="">Select cohort</option>';
    options += '<option value="ALL">Select All</option>';
  }
  options += keysDesc.map(k => `<option value="${k}">${formatCohortLabel(k)}</option>`).join('');
  select.innerHTML = options || '<option value="">Select cohort</option>';

  // Auto-select when exactly one cohort exists and no placeholder
  if (keysDesc.length === 1) {
    select.value = keysDesc[0];
    // Announce auto-selection for accessibility
    showDataDisplayMessage('systemwide', `Showing data for all registrations submitted for ${formatCohortLabel(keysDesc[0])} cohort`, 'info');
  } else {
    select.value = '';
    showDataDisplayMessage('systemwide', 'Showing data for all registrations submitted in date range', 'info');
  }

  return keysDesc;
}

// Wire UI behavior: enable select only when "by-cohort" selected
function wireSystemwideWidgetRadios() {
  const radios = document.querySelectorAll('input[name="systemwide-data-display"]');
  const select = document.getElementById('cohort-select');
  if (!radios || !radios.length || !select) return;

  // Enforce default to by-date on init
  const defaultByDate = document.querySelector('input[name="systemwide-data-display"][value="by-date"]');
  if (defaultByDate) {
    defaultByDate.checked = true;
  }

  function updateSystemwideStatusMessage() {
    const chosen = Array.from(radios).find(r => r.checked)?.value;
    const val = select.value;
    if (chosen === 'by-cohort') {
      if (val === 'ALL') {
        showDataDisplayMessage('systemwide', 'Showing data for all registrations submitted for cohorts in the date range', 'info');
      } else if (val) {
        showDataDisplayMessage('systemwide', `Showing data for all registrations submitted for ${formatCohortLabel(val)} cohort`, 'info');
      } else {
        showDataDisplayMessage('systemwide', 'Please choose an option from the Select cohort menu', 'info');
      }
    } else {
      showDataDisplayMessage('systemwide', 'Showing data for all registrations submitted in date range', 'info');
    }
  }

  function applyMode() {
    const chosen = Array.from(radios).find(r => r.checked)?.value;
    const byCohort = chosen === 'by-cohort';
    select.disabled = !byCohort;
    updateSystemwideStatusMessage();
    // Update count and report link on mode change
    updateSystemwideCountAndLink();
  }

  radios.forEach(r => r.addEventListener('change', applyMode));
  select.addEventListener('change', function() {
    updateSystemwideStatusMessage();
    updateSystemwideCountAndLink();
  });
  // Initialize state
  applyMode();
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

function updateSystemwideCountAndLink() {
  const radios = document.querySelectorAll('input[name="systemwide-data-display"]');
  const select = document.getElementById('cohort-select');
  const chosen = Array.from(radios).find(r => r.checked)?.value;
  const byCohort = chosen === 'by-cohort';
  const rows = __lastSummaryData && Array.isArray(__lastSummaryData.registrations) ? __lastSummaryData.registrations : [];
  if (!byCohort) {
    setSystemwideRegistrationsCell(rows.length || 0);
    updateRegistrantsReportLink('by-date', '');
    return;
  }
  const cohortValue = select ? select.value : '';
  const counts = buildCohortYearCountsFromRows(rows);
  if (cohortValue === 'ALL') {
    const keys = new Set(getCohortKeysFromRange(__lastStart, __lastEnd));
    let total = 0;
    keys.forEach(k => { total += counts.get(k) || 0; });
    setSystemwideRegistrationsCell(total);
    updateRegistrantsReportLink('by-cohort', 'ALL');
  } else if (cohortValue) {
    const n = counts.get(cohortValue) || 0;
    setSystemwideRegistrationsCell(n);
    updateRegistrantsReportLink('by-cohort', cohortValue);
  } else {
    // No selection yet; show 0 to prompt selection
    setSystemwideRegistrationsCell(0);
    updateRegistrantsReportLink('by-cohort', '');
  }
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

// Main exported function
export async function fetchAndUpdateAllTables(start, end) {
  try {
    const summaryUrl = `reports_api.php?start_date=${encodeURIComponent(start)}&end_date=${encodeURIComponent(end)}`;
    const organizationUrl = `reports_api.php?start_date=${encodeURIComponent(start)}&end_date=${encodeURIComponent(end)}&organization_data=1`;
    
    const summaryData = await fetchWithRetry(summaryUrl);
    const organizationData = await fetchWithRetry(organizationUrl);
    
    updateSystemwideTable(start, end, summaryData);
    // Ensure default count by date is shown immediately
    __lastSummaryData = summaryData;
    __lastStart = start;
    __lastEnd = end;
    populateCohortSelectFromRange(start, end);
    wireSystemwideWidgetRadios();
    // Force default mode and update count/link to by-date
    updateSystemwideCountAndLink();
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
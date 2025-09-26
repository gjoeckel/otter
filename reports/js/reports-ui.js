// reports-ui.js
// Formerly reports-main.js
import { initializeDataDisplayOptions } from './data-display-options.js';

function initializeReportsMain() {
  // Initialize data display options
  initializeDataDisplayOptions();
  

  // Toggle date picker visibility based on date picker state
  function toggleDatePickerVisibility() {
    var datePicker = document.getElementById('date-picker-container');
    if (!datePicker) return;

    // When date picker is visible (Edit Range disabled), keep it visible
    // When date picker is hidden (Edit Range enabled), hide it and show tables
    if (datePicker.classList.contains('hidden') || datePicker.style.display === 'none') {
      // Date picker is hidden - show tables
      var rangeReports = document.getElementById('range-reports');
      if (rangeReports) {
        rangeReports.style.display = 'block';
      }
    } else {
      // Date picker is visible - hide tables
      var rangeReports = document.getElementById('range-reports');
      if (rangeReports) {
        rangeReports.style.display = 'none';
      }
    }
  }
  toggleDatePickerVisibility();
  var datePickerObserver = new MutationObserver(toggleDatePickerVisibility);
  var datePicker = document.getElementById('date-picker-container');
  if (datePicker) {
    datePickerObserver.observe(datePicker, {
      attributes: true,
      attributeFilter: ['class', 'style']
    });
  }

  // Toggle #range-reports and #date-picker-container visibility
  function toggleRangeReportsVisibility() {
    var datePicker = document.getElementById('date-picker-container');
    var rangeReports = document.getElementById('range-reports');
    var header = document.querySelector('header');
    if (!datePicker || !rangeReports) return;
    // Consider visible if not hidden by class or style
    var datePickerVisible = !datePicker.classList.contains('hidden') && datePicker.style.display !== 'none';
    if (datePickerVisible) {
      rangeReports.style.display = 'none';
      // When date picker is visible, make header not sticky
      if (header) {
        header.classList.add('not-sticky');
      }
    } else {
      rangeReports.style.display = 'block';
      // When range-reports is visible, make header sticky
      if (header) {
        header.classList.remove('not-sticky');
      }
    }
  }
  toggleRangeReportsVisibility();
  if (datePicker) {
    var rangeObserver = new MutationObserver(toggleRangeReportsVisibility);
    rangeObserver.observe(datePicker, { attributes: true, attributeFilter: ['class', 'style'] });
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initializeReportsMain);
} else {
  initializeReportsMain();
}



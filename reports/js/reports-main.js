// reports-main.js
// Entry point for reports page JS. Imports and initializes all modules.

import { initializeDataDisplayOptions } from './data-display-options.js';

function updateRefreshButtonState() {
  const refreshButton = document.getElementById('refresh-data-button');
  if (!refreshButton) return;

        fetch('check_cache.php?auth=1', {
    credentials: 'same-origin',
    headers: {
      'Accept': 'application/json'
    }
  })
      .then(response => {
          if (!response.ok) {
              throw new Error(`HTTP error! status: ${response.status}`);
          }
          return response.json();
      })
      .then(data => {
          refreshButton.disabled = !data.exists;
      })
      .catch(error => {
          console.error('Error checking cache status:', error);
          refreshButton.disabled = true; // Disable on error
      });
}

// Make the function globally available
window.updateRefreshButtonState = updateRefreshButtonState;

document.addEventListener('DOMContentLoaded', function() {
  // Initialize data display options
  initializeDataDisplayOptions();
  
  // Toggle header button visibility based on date picker state
  function toggleHeaderButtons() {
    var datePicker = document.getElementById('date-picker-container');
    var backBtn = document.getElementById('back-btn');
    var logoutForm = document.getElementById('logout-form');
    if (!datePicker || !backBtn || !logoutForm) return;
    
    // When date picker is visible (Edit Range disabled), show Back button and hide Logout
    // When date picker is hidden (Edit Range enabled), hide Back button and show Logout
    if (datePicker.classList.contains('hidden') || datePicker.style.display === 'none') {
      backBtn.style.display = 'none';
      logoutForm.style.display = '';
    } else {
      backBtn.style.display = '';
      logoutForm.style.display = 'none';
    }
  }
  toggleHeaderButtons();
  var headerObserver = new MutationObserver(toggleHeaderButtons);
  var datePicker = document.getElementById('date-picker-container');
  if (datePicker) {
    headerObserver.observe(datePicker, {
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

  // Initial check for the refresh button state
  updateRefreshButtonState();

  // Refresh button functionality
  const refreshButton = document.getElementById('refresh-data-button');
  if (refreshButton) {
    refreshButton.addEventListener('click', function() {
      // Show initial message
      const messageDisplay = document.getElementById('message-display');
      messageDisplay.innerHTML = 'Data refresh started.';
      messageDisplay.className = 'message display-block';

      // Get the date range BEFORE clearing it
      const startDate = document.getElementById('start-date').value;
      const endDate = document.getElementById('end-date').value;

      // 1. Trigger "Clear" button process
      const clearButton = document.getElementById('clear-dates-button');
      if (clearButton) {
        clearButton.click();
      }

      // 2. Delete cache files by calling clear_cache.php
      fetch('clear_cache.php?auth=1', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Accept': 'application/json'
        }
      })
      .then(response => {
          if (!response.ok) {
              throw new Error(`HTTP error! status: ${response.status}`);
          }
          return response.json();
      })
      .then(data => {
        if (data.success) {
          // 3. Trigger the process to regenerate these files
          // We can call the reports_api.php with the stored date range
          let url = `reports_api.php?auth=1`;
          if (startDate && endDate) {
            url += `&start_date=${startDate}&end_date=${endDate}`;
          } else {
            // If there's no date range, we cannot proceed with regeneration
            // as the API requires dates. We'll just show the success message
            // for the cache clearing and let the user apply a new date range.
            messageDisplay.innerHTML = 'Data refresh completed.';
            messageDisplay.className = 'message success-message display-block';
            setTimeout(() => {
              messageDisplay.innerHTML = 'Select a date range to generate a new report.';
              messageDisplay.className = 'message display-block';
            }, 5000); // Show completion message for 3s, then wait 2s before showing next message
            return;
          }

          fetch(url, {
            credentials: 'same-origin',
            headers: {
              'Accept': 'application/json'
            }
          })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
              if (data.error) {
                console.error('Error regenerating data:', data.error);
                messageDisplay.innerHTML = '<strong>Error:</strong> An error occurred while refreshing the data.';
                messageDisplay.className = 'message error-message display-block';
              } else {
                // Show completion message
                messageDisplay.innerHTML = 'Data refresh completed.';
                messageDisplay.className = 'message success-message display-block';
                // Reload the page after showing completion message for 3s and waiting 2s
                setTimeout(() => location.reload(), 5000);
              }
            })
            .catch(error => {
              console.error('Error:', error);
              messageDisplay.innerHTML = '<strong>Error:</strong> An error occurred while fetching report data.';
              messageDisplay.className = 'message error-message display-block';
            });
        } else {
          messageDisplay.innerHTML = '<strong>Error:</strong> ' + data.message;
          messageDisplay.className = 'message error-message display-block';
        }
      })
      .catch(error => {
        console.error('Error:', error);
        messageDisplay.innerHTML = '<strong>Error:</strong> An error occurred while clearing the cache.';
        messageDisplay.className = 'message error-message display-block';
      });
    });
  }
}); 
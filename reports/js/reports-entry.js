// reports/js/reports-entry.js
// Entry that preserves the current module load order for bundling
// Keep classic non-module scripts (../lib/*.js) loaded separately in HTML

import './logging-utils.js';
import './log-viewer.js';
import './enhanced-logging.js';
import './debug-dashboard.js';
import './code-metrics.js';
import './filter-state-manager.js';
import './datalist-utils.js';
import './reports-data.js';
import './date-range-picker.js';
import './groups-search.js';
import './organization-search.js';
import './reports-messaging.js';
import './reports-ui.js';
import './data-display-options.js';
import './unified-data-service.js';
import './unified-table-updater.js';

// Initialize log viewer for debugging
import { initLogViewer } from './log-viewer.js';
import { initEnhancedLogging } from './enhanced-logging.js';
import { logger, logSystemHealth } from './logging-utils.js';

initLogViewer();
initEnhancedLogging();

// Check build system health
// DISABLED: Bundle system health check - using direct module loading instead
// export function checkBuildSystemHealth() {
//   const buildFile = document.querySelector('script[src*="reports.bundle.js"]');
//   if (!buildFile) {
//     logger.error('build-system', 'Missing build file: reports.bundle.js');
//     return false;
//   } else {
//     logger.success('build-system', 'Build file found', { src: buildFile.src });
//     return true;
//   }
// }

// Initialize system health monitoring
document.addEventListener('DOMContentLoaded', () => {
  // DISABLED: Check build system health
  // checkBuildSystemHealth();
  
  // Log system health
  logSystemHealth();
  
  // Set up periodic health checks (every 5 minutes)
  setInterval(logSystemHealth, 5 * 60 * 1000);
});



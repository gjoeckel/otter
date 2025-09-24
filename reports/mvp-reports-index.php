<?php
require_once __DIR__ . '/../lib/session.php';

// Check if session ID is passed in the URL
if (isset($_GET['PHPSESSID'])) {
    session_id($_GET['PHPSESSID']);
}

initializeSession();

require_once __DIR__ . '/../lib/direct_link.php';
require_once __DIR__ . '/../lib/unified_enterprise_config.php';
require_once __DIR__ . '/../lib/enterprise_features.php';
require_once __DIR__ . '/../lib/abbreviation_utils.php';

// Initialize enterprise and environment from single source of truth
$context = UnifiedEnterpriseConfig::initializeFromRequest();

// Check if user is authenticated via session before attempting to initialize enterprise config
if (!isset($_SESSION['organization_authenticated']) || $_SESSION['organization_authenticated'] !== true) {
    // Not authenticated - redirect to login with proper return URL
    $returnUrl = urlencode($_SERVER['REQUEST_URI']);
    header("Location: ../login.php?return=" . $returnUrl);
    exit;
}

// Initialize enterprise and environment from single source of truth
$context = UnifiedEnterpriseConfig::initializeFromRequest();

// If enterprise detection still fails after authentication, show error
if (isset($context['error'])) {
    require_once __DIR__ . '/../lib/error_messages.php';
    http_response_code(500);
    die(ErrorMessages::getTechnicalDifficulties());
}

// Get enterprise configuration
$enterpriseCode = $context['enterprise_code'];
$environment = $context['environment'];

// Load enterprise-specific configuration
$configFile = __DIR__ . "/../config/{$enterpriseCode}.config";
if (!file_exists($configFile)) {
    require_once __DIR__ . '/../lib/error_messages.php';
    http_response_code(500);
    die(ErrorMessages::getTechnicalDifficulties());
}

$config = json_decode(file_get_contents($configFile), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    require_once __DIR__ . '/../lib/error_messages.php';
    http_response_code(500);
    die(ErrorMessages::getTechnicalDifficulties());
}

// Get organization name from session
$organizationName = $_SESSION['organization_name'] ?? 'Unknown Organization';

// Load enterprise features
$features = EnterpriseFeatures::load($enterpriseCode);

// Generate cache bust timestamp
$cacheBust = time();
?>
<!DOCTYPE html>
<html lang="en" style="overflow-y: scroll;">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($config['enterprise_name'] ?? 'MVP') ?> Reports</title>
  <link rel="stylesheet" href="css/reports-main.css?v=<?= $cacheBust ?>">
  <link rel="stylesheet" href="css/date-range-picker.css?v=<?= $cacheBust ?>">
  <link rel="stylesheet" href="css/reports-data.css?v=<?= $cacheBust ?>">
  <link rel="stylesheet" href="css/organization-search.css?v=<?= $cacheBust ?>">
  <link rel="stylesheet" href="css/groups-search.css?v=<?= $cacheBust ?>">
  <link rel="stylesheet" href="../css/messages.css">
  <link rel="stylesheet" href="css/reports-messaging.css?v=<?= $cacheBust ?>">
  <link rel="stylesheet" href="../css/buttons.css">
  <!-- Load flatpickr CSS/JS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <link rel="icon" type="image/svg+xml" href="../lib/otter.svg">
  <link rel="icon" type="image/x-icon" href="../favicon.ico">
  <script src="https://unpkg.com/wicg-inert@3.1.2/dist/inert.min.js"></script>
  <link rel="stylesheet" href="../css/print.css?v=<?= $cacheBust ?>" media="print">
  <script src="../lib/message-dismissal.js?v=<?= $cacheBust ?>"></script>
  <style>
    /* MVP: Groups section visibility controlled by PHP logic */
    .groups-section-hidden {
      display: none;
    }

    .groups-section-visible {
      display: block;
    }
  </style>
  <script type="module">
    import { initializePrintFunctionality } from '../lib/print-utils.js';

    window.APP_PATH = '';
    window.HAS_GROUPS = <?= $features['has_groups'] ? 'true' : 'false' ?>;
    window.ENTERPRISE_CODE = '<?= htmlspecialchars($enterpriseCode) ?>';

    // Shared abbreviation function
    function abbreviateOrganizationNameJS(name) {
        const rules = [
            ['Community College District', 'CCD'],
            ['Junior College District', 'JCD'],
            ['Community College', 'CC'],
            ['Continuing Education', 'Cont Ed'],
        ];

        for (const [pattern, abbr] of rules) {
            if (name.includes(pattern)) {
                return name.replace(pattern, abbr);
            }
        }

        return name;
    }
    
    document.addEventListener('DOMContentLoaded', function() {
      // Initialize print functionality
      initializePrintFunctionality({
        cssPath: '../css/print.css',
        printButtons: [
          {
            id: 'organization-search-print',
            type: 'window',
            sectionId: 'organization-section',
            title: 'Organizations Data',
            orientation: 'landscape'
          },
          {
            id: 'groups-search-print',
            type: 'window',
            sectionId: 'groups-section',
            title: 'Districts Data',
            orientation: 'portrait'
          }
        ]
      });
    });
  </script>
  <link rel="stylesheet" href="../css/print.css?v=<?= $cacheBust ?>271" media="print">
  <script type="module" src="dist/mvp-reports.bundle.js?v=<?= $cacheBust ?>"></script>
</head>

<body>
  <a href="#main-content" class="skip-link">Skip to main content</a>

  <header class="">
    <div class="header-spacer"></div>
    <h1><?= htmlspecialchars($config['enterprise_name'] ?? 'MVP') ?> Reports</h1>
    <nav>
      <button id="edit-date-range" type="button" class="btn action-btn" aria-expanded="true" aria-controls="date-picker-container" aria-label="Edit date range">
        Edit Date Range
      </button>
      <a href="../admin/index.php?auth=1" id="back-btn" class="btn back-btn" style="display: none;">Admin</a>
      <form method="get" action="../login.php" id="logout-form" style="">
        <input type="hidden" name="logout" value="1" aria-label="Logout confirmation">
        <button type="submit" class="link" id="logout-btn">Logout</button>
      </form>
    </nav>
  </header>

  <main id="main-content">
    <!-- Date Range Picker Container (Expandable/Collapsible) -->
    <div id="date-picker-container" class="date-picker-container expanded" tabindex="-1" style="display: none;">
      <h2 class="date-picker-heading">Select Date Range</h2>
      <fieldset class="date-picker-presets" role="group" aria-labelledby="preset-ranges-label">
        <legend id="preset-ranges-label" class="date-picker-presets-legend">Preset Ranges</legend>
        <div class="date-picker-presets-options">
          <label class="date-picker-presets-label">
            <input type="radio" name="date-preset" value="none" class="date-picker-presets-radio" checked=""> None
          </label>
          <label class="date-picker-presets-label">
            <input type="radio" name="date-preset" value="today" class="date-picker-presets-radio"> Today
          </label>
          <label class="date-picker-presets-label">
            <input type="radio" name="date-preset" value="past-month" class="date-picker-presets-radio"> Past Month
          </label>
          <label class="date-picker-presets-label-compact">
            <input type="radio" name="date-preset" value="q1" class="date-picker-presets-radio"> Q1
          </label>
          <label class="date-picker-presets-label-compact">
            <input type="radio" name="date-preset" value="q2" class="date-picker-presets-radio"> Q2
          </label>
          <label class="date-picker-presets-label-compact">
            <input type="radio" name="date-preset" value="q3" class="date-picker-presets-radio"> Q3
          </label>
          <label class="date-picker-presets-label-compact">
            <input type="radio" name="date-preset" value="q4" class="date-picker-presets-radio"> Q4
          </label>
          <label class="date-picker-presets-label-compact">
            <input type="radio" name="date-preset" value="all" class="date-picker-presets-radio"> All
          </label>
        </div>
      </fieldset>
      <div class="date-picker-content">
        <div class="accessible-date-range-picker">
          <div class="date-picker-group">
            <label for="start-date">Start Date (MM-DD-YY)</label>
            <input id="start-date" class="flatpickr" type="text" placeholder="MM-DD-YY" aria-label="Start date" required="" aria-describedby="start-date-help">
            <div id="start-date-help" class="help-text sr-only">Enter date in MM-DD-YY format (e.g., 01-15-24)</div>
          </div>
          <div class="date-picker-group">
            <label for="end-date">End Date (MM-DD-YY)</label>
            <input id="end-date" class="flatpickr" type="text" placeholder="MM-DD-YY" aria-label="End date" required="" aria-describedby="end-date-help">
            <div id="end-date-help" class="help-text sr-only">Enter date in MM-DD-YY format (e.g., 01-15-24)</div>
          </div>
        </div>
        <div class="date-picker-actions">
        <button id="apply-range-button" class="btn action-btn" type="button" aria-describedby="apply-button-status">Apply</button>
        <button id="clear-dates-button" class="btn clear-btn" type="button" aria-label="Clear date range" aria-describedby="clear-button-status">Clear</button>
        <div id="apply-button-status" class="sr-only">Button is disabled until both start and end dates are entered</div>
        <div id="clear-button-status" class="sr-only">Button is disabled when no dates are entered</div>
        </div>
        <div class="message-container">
            <div id="message-display" class="display-block visually-hidden-but-space" aria-live="polite" aria-hidden="true"></div>
        </div>
      </div>
    </div>
    
    <div id="range-reports" style="display: block;">
    <section id="systemwide-section">
      <!-- MVP: No complex count options widgets - simplified interface -->
      <div class="table-responsive">
        <table class="systemwide-data" id="systemwide-data" aria-label="Systemwide Data">
          <caption>
            Systemwide Data
            <!-- MVP: No toggle button - simplified interface -->
          </caption>
          <thead>
            <tr>
              <th scope="col">Start Date</th>
              <th scope="col">End Date</th>
              <th scope="col">Registrations</th>
              <th scope="col">Enrollments</th>
              <th scope="col">Certificates</th>
            </tr>
          </thead>
          <tbody>
            <!-- MVP: Default empty row - will be populated by JavaScript -->
            <tr>
              <td>-</td>
              <td>-</td>
              <td>0</td>
              <td>0</td>
              <td>0</td>
            </tr>
          </tbody>
          <tfoot>
            <tr>
              <td style="height: 60px; padding: 0;"></td>
              <td style="height: 60px; padding: 0;"></td>
              <td style="height: 60px; padding: 0;">
                <div class="report-link-container">
                  <a id="registrations-report-link" href="registrants.php?start_date=01-01-20&end_date=12-31-25&mode=date" class="link external-link" target="_blank" rel="noopener noreferrer">
                    Registrants Report
                    <span aria-label="Opens in a new tab" role="img" class="inline-block-middle">
                      <svg width="18" height="18" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" focusable="false" class="svg-middle">
                        <path d="M14.5 2A1.5 1.5 0 0 1 16 3.5V7a1 1 0 1 1-2 0V5.41l-7.3 7.3a1 1 0 0 1-1.4-1.42l7.3-7.29H11a1 1 0 1 1 0-2h3.5ZM4 6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-3a1 1 0 1 1 2 0v3a4 4 0 0 1-4 4H4a4 4 0 0 1-4-4V8a4 4 0 0 1 4-4h3a1 1 0 1 1 0 2H4Z"></path>
                      </svg>
                    </span>
                  </a>
                </div>
              </td>
              <td style="height: 60px; padding: 0;">
                <div class="report-link-container">
                  <a id="enrollments-report-link" href="enrollees.php?start_date=01-01-20&end_date=12-31-25&enrollment_mode=by-tou&mode=date" class="link external-link" target="_blank" rel="noopener noreferrer">
                    Enrollees Report
                    <span aria-label="Opens in a new tab" role="img" class="inline-block-middle">
                      <svg width="18" height="18" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" focusable="false" class="svg-middle">
                        <path d="M14.5 2A1.5 1.5 0 0 1 16 3.5V7a1 1 0 1 1-2 0V5.41l-7.3 7.3a1 1 0 0 1-1.4-1.42l7.3-7.29H11a1 1 0 1 1 0-2h3.5ZM4 6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-3a1 1 0 1 1 2 0v3a4 4 0 0 1-4 4H4a4 4 0 0 1-4-4V8a4 4 0 0 1 4-4h3a1 1 0 1 1 0 2H4Z"></path>
                      </svg>
                    </span>
                  </a>
                </div>
              </td>
              <td style="height: 60px; padding: 0;">
                <div class="report-link-container">
                  <a id="certificates-report-link" href="certificates-earned.php?start_date=01-01-20&end_date=12-31-25&ent=<?= htmlspecialchars($enterpriseCode) ?>" class="link external-link" target="_blank" rel="noopener noreferrer">
                    Cert Earners Report
                    <span aria-label="Opens in a new tab" role="img" class="inline-block-middle">
                      <svg width="18" height="18" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" focusable="false" class="svg-middle">
                        <path d="M14.5 2A1.5 1.5 0 0 1 16 3.5V7a1 1 0 1 1-2 0V5.41l-7.3 7.3a1 1 0 0 1-1.4-1.42l7.3-7.29H11a1 1 0 1 1 0-2h3.5ZM4 6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-3a1 1 0 1 1 2 0v3a4 4 0 0 1-4 4H4a4 4 0 0 1-4-4V8a4 4 0 0 1 4-4h3a1 1 0 1 1 0 2H4Z"></path>
                      </svg>
                    </span>
                  </a>
                </div>
              </td>
            </tr>
          </tfoot>
        </table>
      </div>
    </section>

    <!-- MVP: Organizations and Groups sections remain the same -->
    <section id="organization-section">
      <div id="organization-search-widget" class="organization-search-widget" style="display: none;">
        <div class="organization-data-display-wrapper">
          <fieldset id="organization-data-display" class="fieldset-box fieldset-stack">
            <legend>Organizations Data Display</legend>
            <div class="organization-data-display-options">
              <label class="organization-data-display-label">
                <input type="radio" name="organization-data-display" value="all" class="organization-data-display-radio" checked=""> show all rows
              </label>
              <label class="organization-data-display-label">
                <input type="radio" name="organization-data-display" value="no-values" class="organization-data-display-radio"> show all rows with no data
              </label>
              <label class="organization-data-display-label">
                <input type="radio" name="organization-data-display" value="hide-empty" class="organization-data-display-radio"> show all rows with data
              </label>
            </div>
            <div class="message-container">
              <div id="organization-data-display-message" class="date-range-status info-message" aria-live="polite">Showing data for all organizations</div>
            </div>
          </fieldset>
        </div>
        <!-- Rest of organization search widget remains the same -->
      </div>
      <!-- Organizations table remains the same -->
    </section>

    <section id="groups-section" class="groups-section-visible">
      <!-- Groups section remains the same -->
    </section>

  </div></main>

  <!-- MVP: Load MVP bundle instead of regular bundle -->
  <script type="module" src="dist/mvp-reports.bundle.js?v=<?= $cacheBust ?>"></script>
  
  <script src="../lib/table-filter-interaction.js?v=<?= $cacheBust ?>"></script>

  <!-- Global Message Display Functions -->
  <script>
    // Global variables
    let currentDateRange = null;
    let currentOrganization = null;
    let currentGroup = null;
    let isDataLoading = false;
    let dataCache = {};
  </script>
</body>
</html>

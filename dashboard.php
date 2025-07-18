<?php
// Start session first
require_once __DIR__ . '/lib/session.php';
initializeSession();

require_once __DIR__ . '/lib/direct_link.php';
require_once __DIR__ . '/lib/unified_database.php';
require_once __DIR__ . '/lib/api/organizations_api.php';
// STANDARDIZED: Uses UnifiedEnterpriseConfig for enterprise detection and config access
require_once __DIR__ . '/lib/unified_enterprise_config.php';
require_once __DIR__ . '/lib/enterprise_cache_manager.php';

$db = new UnifiedDatabase();

// Get organization code (password) from multiple sources
$organizationCode = '';

// Priority order for password detection:
// 1. Query parameter: ?org={password}
if (isset($_GET['org']) && preg_match('/^\d{4}$/', $_GET['org'])) {
    $organizationCode = $_GET['org'];
}
// 2. PATH_INFO: /dashboard.php/{password}
elseif (!empty($_SERVER['PATH_INFO'])) {
    $parts = explode('/', trim($_SERVER['PATH_INFO'], '/'));
    $password = $parts[0] ?? '';
    if (preg_match('/^\d{4}$/', $password)) {
        $organizationCode = $password;
    }
}
// 3. Legacy parameter: ?organization={password}
elseif (isset($_GET['organization']) && preg_match('/^\d{4}$/', $_GET['organization'])) {
    $organizationCode = $_GET['organization'];
}
// 4. Legacy parameter: ?password={password}
elseif (isset($_GET['password']) && preg_match('/^\d{4}$/', $_GET['password'])) {
    $organizationCode = $_GET['password'];
}

// Get organization data from database
$org = null;
$enterprise_code = null;
$valid = false;

if ($organizationCode) {
    $orgData = $db->getOrganizationByPassword($organizationCode);
    if ($orgData) {
        $org = $orgData['name'];
        $enterprise_code = $orgData['enterprise'];
        $valid = true;
    }
}

// If we have an organization, display the dashboard
if ($valid && $org) {
    // Initialize enterprise configuration BEFORE calling API
    // STANDARDIZED: Uses UnifiedEnterpriseConfig::init() pattern for enterprise initialization
    UnifiedEnterpriseConfig::init($enterprise_code);
    $cacheManager = EnterpriseCacheManager::getInstance();

    // Always use the standard cache file path
    $registrantsCacheFile = $cacheManager->getRegistrantsCachePath();

    // Use unified refresh service for data freshness check
    require_once __DIR__ . '/lib/unified_refresh_service.php';
    $refreshService = UnifiedRefreshService::getInstance();

    // Always show loading overlay during data freshness check
    $showLoadingOverlay = true;

    // Auto-refresh if cache is stale (3-hour TTL)
    $refreshPerformed = $refreshService->autoRefreshIfNeeded(10800); // 3 hours

    // Use OrganizationsAPI to get data for this specific organization
    $orgData = OrganizationsAPI::getOrgData($org);

    // Extract data from API response
    $summary = [];
    $enrolled = [];
    $invited = [];
    $showGenericError = false;
    $newSectionData = [];

    if ($orgData) {
        // Process enrollment summary data
        $enrollmentData = $orgData['enrollment'] ?? [];
        $enrolled = $orgData['enrolled'] ?? [];
        $invited = $orgData['invited'] ?? [];

        // Generate summary from enrollment data
        $grouped = [];
        foreach ($enrollmentData as $row) {
            $cohort = $row['cohort'] ?? '';
            $year = $row['year'] ?? '';
            $enrolledVal = ($row['enrolled'] ?? '') === 'Yes' ? 1 : 0;
            $completed = ($row['completed'] ?? '') === 'Yes' ? 1 : 0;
            $certificates = ($row['certificate'] ?? '') === 'Yes' ? 1 : 0;

            $key = $cohort . '-' . $year;
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'cohort' => $cohort,
                    'year' => $year,
                    'enrollments' => 0,
                    'completed' => 0,
                    'certificates' => 0
                ];
            }
            $grouped[$key]['enrollments'] += $enrolledVal;
            $grouped[$key]['completed'] += $completed;
            $grouped[$key]['certificates'] += $certificates;
        }

        // Convert grouped data to array
        foreach ($grouped as $row) {
            $summary[] = $row;
        }

        // Get certificates earned data
        $newSectionData = OrganizationsAPI::getAllCertificatesEarnedRowsAllRange($org);
    } else {
        $showGenericError = true;
    }

    // Sort data
    uasort($summary, function($a, $b) {
        $yearDiff = strcmp($b['year'], $a['year']);
        if ($yearDiff !== 0) return $yearDiff;
        return strcmp($b['cohort'], $a['cohort']);
    });

    usort($enrolled, function($a, $b) {
        $yearDiff = strcmp($b['year'] ?? '', $a['year'] ?? '');
        if ($yearDiff !== 0) return $yearDiff;
        $cohortDiff = strcmp($b['cohort'] ?? '', $a['cohort'] ?? '');
        if ($cohortDiff !== 0) return $cohortDiff;
        $lastDiff = strcmp($a['last'] ?? '', $b['last'] ?? '');
        if ($lastDiff !== 0) return $lastDiff;
        return strcmp($a['first'] ?? '', $b['first'] ?? '');
    });

    usort($invited, function($a, $b) {
        $dateA = null;
        $dateB = null;
        if (!empty($a['invited']) && ($dt = DateTime::createFromFormat('m-d-y', $a['invited']))) {
            $dateA = $dt->getTimestamp();
        }
        if (!empty($b['invited']) && ($dt = DateTime::createFromFormat('m-d-y', $b['invited']))) {
            $dateB = $dt->getTimestamp();
        }
        if ($dateA !== null && $dateB !== null) {
            if ($dateA != $dateB) {
                return $dateB <=> $dateA;
            }
        } elseif ($dateA !== null) {
            return -1;
        } elseif ($dateB !== null) {
            return 1;
        } else {
            $dateStrDiff = strcmp($b['invited'] ?? '', $a['invited'] ?? '');
            if ($dateStrDiff !== 0) return $dateStrDiff;
        }
        $lastDiff = strcmp($a['last'] ?? '', $b['last'] ?? '');
        if ($lastDiff !== 0) return $lastDiff;
        return strcmp($a['first'] ?? '', $b['first'] ?? '');
    });
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($org); ?> Dashboard</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="icon" type="image/svg+xml" href="lib/otter.svg">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <script src="config/config.js"></script>
    <?php if (isset($showLoadingOverlay) && $showLoadingOverlay): ?>
    <style>
    .dashboard-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.75);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .dashboard-overlay .message-display {
        padding: 1.125rem;
        border-radius: 6px;
        font-weight: bold;
        min-height: 3.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        background-color: #e3f2fd;
        color: #1976d2;
        border: 1px solid #2196f3;
        font-size: 1.65em;
    }
    </style>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Ensure minimum display time of 2.5 seconds
        const startTime = Date.now();
        const minDisplayTime = 2500; // 2.5 seconds

        setTimeout(function() {
            const elapsedTime = Date.now() - startTime;
            const remainingTime = Math.max(0, minDisplayTime - elapsedTime);

            setTimeout(function() {
                const overlay = document.getElementById('dashboard-overlay');
                if (overlay) {
                    overlay.style.display = 'none';
                }
            }, remainingTime);
        }, 1000); // Check after 1 second, then wait for remaining time if needed
    });
    </script>
    <?php endif; ?>

</head>
<body>
<?php if (isset($showLoadingOverlay) && $showLoadingOverlay): ?>
<div id="dashboard-overlay" class="dashboard-overlay">
    <div class="message-display" role="status" aria-live="polite">
        Retrieving your data...
    </div>
</div>
<?php endif; ?>
<a href="#main-content" class="skip-link">Skip to main content</a>
<?php if ($valid && $org): ?>
    <header class="dashboard-header">
        <div class="header-center">
            <h1><?php echo htmlspecialchars($org); ?> Dashboard</h1>
            <?php
            // Get timestamp from cache
            $registrantsCache = $cacheManager->readCacheFile('all-registrants-data.json');
            $timestamp = $registrantsCache['global_timestamp'] ?? null;

            if ($timestamp) {
                // Display the timestamp as-is since it's already in the correct format
                echo '<p class="last-updated">Last Updated: ' . htmlspecialchars($timestamp) . '</p>';
            }
            ?>
        </div>
    </header>
    <main id="main-content">
        <?php if ($showGenericError): ?>
            <p class="error-message" role="alert">An error occurred while retrieving data. Please try again later or contact support.</p>
        <?php else: ?>
            <div id="global-toggle-controls">
                <button type="button" id="dismiss-info-button" class="close-button" aria-label="Hide master toggle switch">&times;</button>
                <p>Use this button
                    <button type="button" id="toggle-all-button" aria-expanded="false" aria-label="Show or hide data rows on all tables."></button>
                to show/hide the data rows on <strong>all</strong> tables. Use the buttons on each of the four tables to show/hide its data rows.</p>
            </div>
            <!-- Enrollment Summary -->
            <section>
                <div class="table-responsive">
                    <table class="enrollment-summary" id="enrollment-summary">
                        <caption>
                            Enrollment Summary
                            <button type="button" class="table-toggle-button" aria-expanded="false" aria-label="Show or hide enrollment summary data rows."></button>
                        </caption>
                        <thead>
                            <tr>
                                <th scope="col">Cohort</th>
                                <th scope="col">Year</th>
                                <th scope="col">Enrollments</th>
                                <th scope="col">Completed</th>
                                <th scope="col">Certificates</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($summary)): ?>
                                <tr><td colspan="5">No enrollment summary data available</td></tr>
                            <?php else: ?>
                                <?php foreach ($summary as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['cohort']); ?></td>
                                        <td><?php echo htmlspecialchars($row['year']); ?></td>
                                        <td><?php echo htmlspecialchars($row['enrollments']); ?></td>
                                        <td><?php echo htmlspecialchars($row['completed']); ?></td>
                                        <td><?php echo htmlspecialchars($row['certificates']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <?php
            // Calculate enrollments sum for Enrolled Participants caption
            $enrollmentsSum = 0;
            foreach ($summary as $row) {
                $enrollmentsSum += isset($row['enrollments']) ? intval($row['enrollments']) : 0;
            }
            ?>
            <!-- Enrolled Participants -->
            <section>
                <div class="table-responsive">
                    <table class="enrolled-participants" id="enrolled-participants">
                        <caption>
                            Enrolled Participants | <span class="caption-count"><?php echo $enrollmentsSum; ?></span>
                            <button type="button" class="table-toggle-button" aria-expanded="false" aria-label="Show or hide enrolled participants data rows."></button>
                        </caption>
                        <thead>
                            <tr>
                                <th scope="col">Days to Close</th>
                                <th scope="col">Cohort</th>
                                <th scope="col">Year</th>
                                <th scope="col">First</th>
                                <th scope="col">Last</th>
                                <th scope="col">Email</th>
                                <th scope="col">Completed</th>
                                <th scope="col">Earned</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($enrolled)): ?>
                                <tr><td colspan="8">No enrolled participants data available</td></tr>
                            <?php else: ?>
                                <?php foreach ($enrolled as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['daystoclose'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($row['cohort'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($row['year'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($row['first'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($row['last'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($row['email'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($row['completed'] ?? '0'); ?></td>
                                        <td><?php echo htmlspecialchars($row['certificate'] ?? '0'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <!-- Invited Participants -->
            <section>
                <div class="table-responsive">
                    <table class="invited-participants" id="invited-participants">
                        <caption>
                            Invited Participants | <span class="caption-count"><?php echo count($invited); ?></span>
                            <button type="button" class="table-toggle-button" aria-expanded="false" aria-label="Show or hide invited participants data rows."></button>
                        </caption>
                        <thead>
                            <tr>
                                <th scope="col">Invited</th>
                                <th scope="col">Cohort</th>
                                <th scope="col">Year</th>
                                <th scope="col">First</th>
                                <th scope="col">Last</th>
                                <th scope="col">Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($invited)): ?>
                                <tr><td colspan="6">No invited participants data available</td></tr>
                            <?php else: ?>
                                <?php foreach ($invited as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['invited']); ?></td>
                                        <td><?php echo htmlspecialchars($row['cohort']); ?></td>
                                        <td><?php echo htmlspecialchars($row['year']); ?></td>
                                        <td><?php echo htmlspecialchars($row['first']); ?></td>
                                        <td><?php echo htmlspecialchars($row['last']); ?></td>
                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <!-- New Section Placeholder -->
            <section>
                <div class="table-responsive">
                    <table class="certificates-earned" id="certificates-earned">
                        <caption>
                            Certificates Earned | <span class="caption-count"><?php echo isset($newSectionData) ? count($newSectionData) : 0; ?></span>
                            <button type="button" class="table-toggle-button" aria-expanded="false" aria-label="Show or hide new section data rows."></button>
                        </caption>
                        <thead>
                            <tr>
                                <th scope="col">Cohort</th>
                                <th scope="col">Year</th>
                                <th scope="col">First</th>
                                <th scope="col">Last</th>
                                <th scope="col">Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($newSectionData)): ?>
                                <tr><td colspan="5">No certificates earned data available</td></tr>
                            <?php else: ?>
                                <?php foreach ($newSectionData as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['cohort']); ?></td>
                                        <td><?php echo htmlspecialchars($row['year']); ?></td>
                                        <td><?php echo htmlspecialchars($row['first']); ?></td>
                                        <td><?php echo htmlspecialchars($row['last']); ?></td>
                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>
    </main>
    <script src="lib/table-interaction.js"></script>
<?php else: ?>
    <main style="max-width:600px;margin:40px auto;padding:2rem;background:#fff;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.08);">
        <h1 style="text-align:center;">Organization Dashboard Access</h1>
        <p style="text-align:center; color: red;">
        Invalid organization or password.<br>
        <span style="font-size:0.9em;">Please check your link or contact support.</span>
        </p>
    </main>
<?php endif; ?>
</body>
</html>
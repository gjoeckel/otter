<?php
// Start session first
require_once __DIR__ . '/../lib/session.php';
initializeSession();

// Load unified enterprise configuration
// STANDARDIZED: Uses UnifiedEnterpriseConfig for enterprise detection and config access
require_once __DIR__ . '/../lib/unified_enterprise_config.php';

// Load cache manager for timestamp retrieval
require_once __DIR__ . '/../lib/enterprise_cache_manager.php';

// Add cache control headers to prevent browser caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$message_content = '';
$message_type = '';
$message_role = 'status';
$message_aria = 'polite';
$message_location = '';
$login_success = false;

// Check if user is authenticated as admin
if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
    // Clean up PATH_INFO to prevent redirect loops
    if (!empty($_SERVER['PATH_INFO'])) {
        unset($_SERVER['PATH_INFO']);
    }
    header('Location: ../login.php');
    exit;
}

// Verify enterprise code is set in session
if (!isset($_SESSION['enterprise_code'])) {
    session_destroy();
    // Clean up PATH_INFO to prevent redirect loops
    if (!empty($_SERVER['PATH_INFO'])) {
        unset($_SERVER['PATH_INFO']);
    }
    header('Location: ../login.php');
    exit;
}

// Initialize enterprise configuration from request (maintains session context)
// STANDARDIZED: Uses UnifiedEnterpriseConfig::initializeFromRequest() pattern
$context = UnifiedEnterpriseConfig::initializeFromRequest();

// Handle refresh process
if (isset($_POST['refresh']) && $_POST['refresh'] === '1') {
    // Use unified refresh service
    require_once __DIR__ . '/../lib/unified_refresh_service.php';
    $refreshService = UnifiedRefreshService::getInstance();
    $result = $refreshService->forceRefresh();

    if (isset($result['error'])) {
        $message_content = 'Error: ' . $result['error'];
        $message_type = 'error-message';
    } elseif (isset($result['warning'])) {
        $message_content = 'Refresh completed with warnings: ' . $result['warning'];
        $message_type = 'warning-message';
    } else {
        $message_content = 'Data refreshed successfully.';
        $message_type = 'success-message';
    }

    $message_role = 'status';
    $message_aria = 'polite';
    $message_location = 'success';
}

// Show 'Password validated.' only after login
if (isset($_GET['login']) && $_GET['login'] == '1') {
    // Get timestamp from cache for display
    $cacheManager = EnterpriseCacheManager::getInstance();
    $registrantsCache = $cacheManager->readCacheFile('all-registrants-data.json');
    $timestamp = $registrantsCache['global_timestamp'] ?? null;

    $message_content = 'Password validated';
    if ($timestamp) {
        $message_content .= ' | Data updated: ' . htmlspecialchars($timestamp);
    }
    $message_content .= '.';

    $message_type = 'success-message';
    $message_role = 'status';
    $message_aria = 'polite';
    $message_location = 'success';
}

// Also show admin buttons if ?auth=1 is present
if (isset($_GET['auth']) && $_GET['auth'] == '1') {
    $login_success = true;
}

$has_message = !empty($message_content);
$message_classes = $has_message
    ? "display-block $message_type"
    : "display-block visually-hidden-but-space";
$message_aria_hidden = $has_message ? '' : 'aria-hidden="true"';

// Get enterprise information for display
// STANDARDIZED: Uses UnifiedEnterpriseConfig::getEnterprise() pattern
$enterprise = UnifiedEnterpriseConfig::getEnterprise();
$page_name = 'Admin';
$display_name = $enterprise['display_name'];
$title = "$display_name $page_name";
?>
<!DOCTYPE html>
<html>

<head>
    <title><?php echo htmlspecialchars($title); ?></title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <link rel="stylesheet" href="../css/admin.css?v=<?php echo rand(1000, 9999); ?>">
    <link rel="icon" type="image/svg+xml" href="/lib/otter.svg">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <script src="../lib/message-dismissal.js"></script>

    <!-- Disable shared message dismissal for admin page - using custom refresh logic -->
    <script>
        // Override shared message dismissal for admin page
        document.addEventListener('DOMContentLoaded', function() {
            // Disable the shared message dismissal utility for this page
            if (window.messageDismissal) {
                window.messageDismissal.disabled = true;
            }
        });
    </script>
</head>

<body class="status-page">
    <!-- Main Container: 650px wide with 50px padding -->
    <div class="main-container">
        <!-- Row 1: Heading Container -->
        <div class="heading-container">
            <h1><?php echo htmlspecialchars($title); ?></h1>
        </div>

        <!-- Row 2: Label Container (empty for admin page) -->
        <div class="label-container">
            <!-- Empty - no label needed for admin page -->
        </div>

        <!-- Row 3: Buttons Container -->
        <div class="buttons-container">
            <div class="admin-btn-row" role="group" aria-label="Admin actions">
                <a href="../reports/" class="button reports-btn" id="reports-btn">Reports</a>
                <button id="refresh-data-button" onclick="showRefreshMessage()" aria-label="Refresh Data" tabindex="0">Refresh Data</button>
                <a href="../settings/" class="button settings-btn" id="settings-btn">Settings</a>
                <form method="get" action="../login.php">
                    <input type="hidden" name="logout" value="1">
                    <button type="submit" class="button logout-btn" id="logout-btn">Logout</button>
                </form>
            </div>
        </div>

        <!-- Row 4: Message Container -->
        <div class="message-container">
            <div id="message-display"
                class="<?php echo $message_classes; ?>"
                role="<?php echo $message_role; ?>"
                aria-live="<?php echo $message_aria; ?>"
                <?php echo $message_aria_hidden; ?>
                tabindex="0">
                <?php echo $has_message ? htmlspecialchars($message_content) : ''; ?>
            </div>
        </div>
    </div>
    <script>
        // Force reload if browser is serving cached content
        if (performance.navigation.type === 1) {
            // Page was reloaded, check if we need to force a fresh reload
            // This is now handled by the enterprise detection system
        }

        function showRefreshMessage() {
            const msg = document.getElementById('message-display');

            // Show "Retrieving your data" message
            msg.textContent = 'Retrieving your data...';
            msg.className = 'display-block info-message';
            msg.setAttribute('aria-live', 'polite');
            msg.focus();
            document.getElementById('refresh-data-button').disabled = true;
            document.getElementById('refresh-data-button').style.opacity = '0.5';

            fetch('refresh.php')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        msg.textContent = 'Error: ' + data.error;
                        msg.className = 'display-block error-message';
                        msg.setAttribute('aria-live', 'assertive');
                    } else if (data.warning) {
                        msg.textContent = 'Refresh completed with warnings: ' + data.warning;
                        msg.className = 'display-block warning-message';
                        msg.setAttribute('aria-live', 'polite');
                    } else {
                        msg.textContent = 'Data refresh completed';
                        msg.className = 'display-block success-message';
                        msg.setAttribute('aria-live', 'polite');
                    }
                    msg.focus();
                    document.getElementById('refresh-data-button').disabled = false;
                    document.getElementById('refresh-data-button').style.opacity = '1';

                    // Add custom dismissal listeners for success/error messages
                    addRefreshMessageDismissalListeners();
                })
                .catch(err => {
                    msg.textContent = 'Error: ' + err;
                    msg.className = 'display-block error-message';
                    msg.setAttribute('aria-live', 'assertive');
                    msg.focus();
                    document.getElementById('refresh-data-button').disabled = false;
                    document.getElementById('refresh-data-button').style.opacity = '1';

                    // Add custom dismissal listeners for error messages
                    addRefreshMessageDismissalListeners();
                });
        }

        // Function to add dismissal listeners for refresh messages
        function addRefreshMessageDismissalListeners() {
            // Remove any existing listeners to prevent duplicates
            removeRefreshMessageDismissalListeners();

            // Get all buttons and links
            const buttons = document.querySelectorAll('button, a');

            buttons.forEach(button => {
                button.addEventListener('click', dismissRefreshMessage);
            });
        }

        // Function to remove dismissal listeners
        function removeRefreshMessageDismissalListeners() {
            const buttons = document.querySelectorAll('button, a');
            buttons.forEach(button => {
                button.removeEventListener('click', dismissRefreshMessage);
            });
        }

        // Function to dismiss refresh messages
        function dismissRefreshMessage() {
            const msg = document.getElementById('message-display');

            // Only dismiss if it's a success or error message (not info message)
            if (msg.className.includes('success-message') || msg.className.includes('error-message') || msg.className.includes('warning-message')) {
                msg.textContent = '';
                msg.className = 'display-block visually-hidden-but-space';
                msg.setAttribute('aria-live', 'polite');
                msg.setAttribute('aria-hidden', 'true');

                // Remove dismissal listeners
                removeRefreshMessageDismissalListeners();
            }
        }

        window.onload = function() {
            var pw = document.getElementById('password');
            if (pw) { pw.focus(); }

            // Add dismiss listeners after page loads (now handled by shared utility)
        };
    </script>
</body>

</html>
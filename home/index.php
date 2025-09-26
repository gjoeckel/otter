<?php
// Start output buffering to prevent unwanted output during refresh
ob_start();

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

// Explicitly set content type to HTML to prevent JSON output
header('Content-Type: text/html; charset=UTF-8');

$message_content = '';
$message_type = '';
$message_role = 'status';
$message_aria = 'polite';
$message_location = '';
$login_success = false;

// Check if user is authenticated as admin
if (!isset($_SESSION['home_authenticated']) || $_SESSION['home_authenticated'] !== true) {
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

// Use unified refresh service for automatic data freshness check (like dashboard)
require_once __DIR__ . '/../lib/unified_refresh_service.php';
$refreshService = UnifiedRefreshService::getInstance();

// Auto-refresh if cache is stale (3-hour TTL like dashboard)
$refreshPerformed = $refreshService->autoRefreshIfNeeded(10800); // 3 hours

// Handle manual refresh process - ISOLATED FROM HTML OUTPUT
if (isset($_POST['refresh']) && $_POST['refresh'] === '1') {
    // Clear any existing output to prevent HTML corruption
    if (ob_get_level()) {
        ob_clean();
    }
    
    // Suppress any potential output during refresh process
    ob_start();
    
    try {
        // Force refresh by calling autoRefreshIfNeeded with TTL=0 to always refresh
        $manualRefreshPerformed = @$refreshService->autoRefreshIfNeeded(0); // 0 TTL forces refresh
        
        // Clear any output that might have been generated during refresh
        if (ob_get_level()) {
            ob_clean();
        }
        
        // Read latest timestamp from cache for display
        $cacheManager = EnterpriseCacheManager::getInstance();
        $registrantsCache = $cacheManager->readCacheFile('all-registrants-data.json');
        $timestamp = $registrantsCache['global_timestamp'] ?? null;

        if ($manualRefreshPerformed) {
            $message_content = $timestamp
                ? ('Data refreshed: ' . htmlspecialchars($timestamp) . '.')
                : 'Data refreshed successfully.';
            $message_type = 'success-message';
        } else {
            $message_content = $timestamp
                ? ('Data already up to date: ' . htmlspecialchars($timestamp) . '.')
                : 'Data was already up to date.';
            $message_type = 'info-message';
        }

        $message_role = 'status';
        $message_aria = 'polite';
        $message_location = 'success';
    } catch (Exception $e) {
        // Handle any errors during refresh
        if (ob_get_level()) {
            ob_clean();
        }
        
        $message_content = 'Refresh completed with warnings.';
        $message_type = 'warning-message';
        $message_role = 'status';
        $message_aria = 'polite';
        $message_location = 'warning';
    }
}

// Show 'Password validated.' only after login (not after refresh)
if (isset($_GET['login']) && $_GET['login'] == '1' && !isset($_POST['refresh'])) {
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
$page_name = 'Home';
$enterprise_name = $enterprise['name'];
$title = $enterprise_name;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title><?php echo htmlspecialchars($title); ?></title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <link rel="stylesheet" href="../css/home.css?v=<?php echo rand(1000, 9999); ?>">
    <link rel="icon" type="image/svg+xml" href="../lib/otter.svg">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
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

        <!-- Row 2: Label Container (empty for home page) -->
        <div class="label-container">
            <!-- Empty - no label needed for home page -->
        </div>

        <!-- Row 3: Buttons Container -->
        <div class="buttons-container">
            <div class="home-btn-row" role="group" aria-label="Home actions">
                <a href="set_reports_session.php" class="button reports-btn" id="reports-btn">Reports</a>
                <button id="refresh-data-button" onclick="showRefreshMessage()" aria-label="Refresh" tabindex="0">Refresh</button>
                <a href="../settings/" class="button settings-btn" id="settings-btn">Settings</a>
                <button id="videos-button" onclick="window.open('../videos/', '_blank')" aria-label="Videos" tabindex="0">Videos</button>
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

        // ADMIN REFRESH FUNCTIONALITY - IMPROVED VERSION
        function showRefreshMessage() {
            const msg = document.getElementById('message-display');

            // Show "Retrieving your data" message
            msg.textContent = 'Retrieving your data...';
            msg.className = 'display-block info-message';
            msg.setAttribute('aria-live', 'polite');
            msg.focus();
            document.getElementById('refresh-data-button').disabled = true;
            document.getElementById('refresh-data-button').style.opacity = '0.5';

            // Use improved form submission to prevent BOM issues
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = window.location.href;
            form.style.display = 'none'; // Hide the form
            
            const refreshInput = document.createElement('input');
            refreshInput.type = 'hidden';
            refreshInput.name = 'refresh';
            refreshInput.value = '1';
            
            form.appendChild(refreshInput);
            document.body.appendChild(form);
            
            // Submit form and remove it immediately
            form.submit();
        }

        window.onload = function() {
            var pw = document.getElementById('password');
            if (pw) { pw.focus(); }
        };
    </script>
</body>

</html>
<?php
// Flush output buffer to ensure clean HTML output
ob_end_flush();
?>
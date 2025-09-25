<?php
// Start session and auth check
require_once __DIR__ . '/../lib/session.php';
initializeSession();

// Require admin auth (same as admin/index.php)
if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
    if (!empty($_SERVER['PATH_INFO'])) {
        unset($_SERVER['PATH_INFO']);
    }
    header('Location: ../login.php');
    exit;
}

// Enterprise/config includes (for consistency and future use)
require_once __DIR__ . '/../lib/unified_enterprise_config.php';
$enterprise = UnifiedEnterpriseConfig::getEnterprise();
$display_name = $enterprise['display_name'] ?? 'Enterprise';
$title = $display_name . ' Videos';

// Cache control headers
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title><?php echo htmlspecialchars($title); ?></title>
    <link rel="stylesheet" href="../css/settings.css?v=<?php echo time(); ?>">
    <link rel="icon" type="image/svg+xml" href="../lib/otter.svg">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
</head>
<body>
    <a href="#main-content" class="skip-link">Skip to main content</a>
    <header>
        <div class="header-spacer"></div>
        <h1>Videos</h1>
        <nav>
            <a href="../admin/index.php?auth=1" id="back-btn" class="link">Admin</a>
        </nav>
    </header>

    <main id="main-content">
       
        <section>
            <h2>Reports</h2>
            <ul>
            <li><a href="https://www.loom.com/share/930922cec4aa4664b74da23ba65fc9f7?sid=09c965aa-9918-4137-bef2-970ba6521d65">Refresh Data & Select Date Range</a></li>
            <li>Systemwide Data</li>
            <li>Organizations Data</li>
            <li>Groups Data</li>
            </ul>
        </section>
        <section>
            <h2>Settings</h2>
            <h3>Video #1</h3>
            <p>link #1</p>
        </section>
        <section>
            <h2>Video #3</h2>
            <p>link #3</p>
        </section>
        <section>
            <h2>Video #4</h2>
            <p>link #4</p>
        </section>
        <section>
            <h2>Video #5</h2>
            <p>link #5</p>
        </section>
    </main>
</body>
</html>



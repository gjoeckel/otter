<?php
/**
 * MVP login.php - Simplified authentication controller (50 lines)
 * Replaces 150+ line complex authentication with simple inline logic
 */
require_once __DIR__ . '/lib/session.php';
require_once __DIR__ . '/lib/mvp_config.php';
require_once __DIR__ . '/lib/mvp_utils.php';

initializeSession();

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $enterprise = $_GET['enterprise'] ?? 'ccc';
    
    try {
        $config = MvpConfig::load();
        
        if (!isset($config[$enterprise])) {
            $errorMessage = "Invalid enterprise";
        } else {
            $enterpriseConfig = $config[$enterprise];
            
            // Check admin authentication
            if ($password === $enterpriseConfig['admin_password']) {
                $_SESSION['admin_authenticated'] = true;
                $_SESSION['enterprise_code'] = $enterprise;
                $_SESSION['environment'] = $_GET['environment'] ?? 'production';
                header('Location: mvp_admin.php');
                exit;
            }
            
            // Check organization authentication
            foreach ($enterpriseConfig['organizations'] as $org) {
                if ($password === $org['password']) {
                    $_SESSION['organization_authenticated'] = true;
                    $_SESSION['organization_name'] = $org['name'];
                    $_SESSION['enterprise_code'] = $enterprise;
                    header('Location: reports/index.php');
                    exit;
                }
            }
            
            $errorMessage = "Invalid credentials";
        }
    } catch (Exception $e) {
        $errorMessage = "System error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>MVP Login</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; }
        .error { color: red; margin: 10px 0; }
        input, button { padding: 10px; margin: 5px; }
    </style>
</head>
<body>
    <h1>MVP Login</h1>
    
    <?php if ($errorMessage): ?>
        <div class="error"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>
    
    <p><strong>Test Passwords:</strong></p>
    <ul>
        <li>Admin: ccc-admin, csu-admin, demo-admin</li>
        <li>Organizations: 1111, 2222, 3333, 4444, 5555, 9999</li>
    </ul>
</body>
</html>

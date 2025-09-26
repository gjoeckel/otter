<?php
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/utils.php';
session_start();

$config = Config::load();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $enterprise = $_GET['enterprise'] ?? 'ccc';

    if (!isset($config[$enterprise])) {
        $errorMessage = "Invalid enterprise";
    } else {
        $enterpriseConfig = $config[$enterprise];
        if ($password === $enterpriseConfig['admin_password']) {
            $_SESSION['home_authenticated'] = true;
            $_SESSION['enterprise_code'] = $enterprise;
            header('Location: home/index.php');
            exit;
        }
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
}
?>
<!DOCTYPE html>
<html>
<head><title>Login</title></head>
<body>
<?php if (isset($errorMessage)): ?>
  <div class="error"><?= htmlspecialchars($errorMessage) ?></div>
<?php endif; ?>
<form method="POST">
  <input type="password" name="password" placeholder="Password" required>
  <button type="submit">Login</button>
</form>
</body>
</html>

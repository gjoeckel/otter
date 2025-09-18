<?php
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/unified_enterprise_config.php';

class DirectLink {
    // No static $path or legacy detection

    public function __construct() {}

    public static function getDirectLink($password, $type = 'dashboard') {
        // For universal relative paths, generate simple URLs
        // Note: This method returns relative URLs that work from root directory
        // For subdirectory usage, callers should prepend '../' as needed
        switch ($type) {
            case 'dashboard_org':
                return "dashboard.php?org={$password}";
            case 'dashboard':
            default:
                return "dashboard.php?org={$password}";
        }
    }

    /**
     * Get dashboard URL with query parameters
     * @param string $password The 4-digit password
     * @return string Dashboard URL with org parameter
     */
    public static function getDashboardUrlPHP($password) {
        // Prefer dashboards.json pattern if available
        try {
            $enterprise = UnifiedEnterpriseConfig::detectEnterprise();
            $dashboardsPath = __DIR__ . '/../config/dashboards.json';
            if (file_exists($dashboardsPath)) {
                $json = json_decode(file_get_contents($dashboardsPath), true);
                // Try enterprise-specific entry first
                if (isset($json['enterprises'][$enterprise])) {
                    $baseUrl = $json['enterprises'][$enterprise]['base_url'] ?? null;
                    $pattern = $json['enterprises'][$enterprise]['dashboard_pattern'] ?? '{base_url}/dashboard.php?org={password}';
                    if (!empty($baseUrl)) {
                        $url = str_replace(['{base_url}', '{password}'], [$baseUrl, $password], $pattern);
                        return $url;
                    }
                }
                // Fallback to production environment if defined
                if (isset($json['environments']['production'])) {
                    $baseUrl = $json['environments']['production']['base_url'] ?? null;
                    $pattern = $json['environments']['production']['dashboard_pattern'] ?? '{base_url}/dashboard.php?org={password}';
                    if (!empty($baseUrl)) {
                        $url = str_replace(['{base_url}', '{password}'], [$baseUrl, $password], $pattern);
                        return $url;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Fall through to relative path on any error
        }
        // Relative fallback for local/dev or missing config
        return "dashboard.php?org={$password}";
    }

    /**
     * Get org parameter direct link (RECOMMENDED)
     * @param string $password The 4-digit password
     * @return string Dashboard URL with org parameter
     */
    public static function getOrgDirectLink($password) {
        return self::getDirectLink($password, 'dashboard_org');
    }

    /**
     * Get the enterprise.json file path dynamically based on enterprise detection
     * Note: This method is kept for backward compatibility but should be phased out
     */
    public static function getEnterpriseJsonPath() {
        $enterprise = UnifiedEnterpriseConfig::detectEnterprise();
        return __DIR__ . "/../config/{$enterprise}/enterprise.json";
    }

    public static function regenerateEnterpriseJson() {
        // This method is deprecated - enterprise.json files are no longer used
        // The unified system uses passwords.json, dashboards.json, and {enterprise}.config
        return true;
    }
}
<?php
/**
 * MVP Config loader - Simplified configuration management
 * Replaces complex UnifiedEnterpriseConfig with simple JSON loading
 */
class MvpConfig {
    private static $data;

    public static function load(): array {
        if (!self::$data) {
            $json = file_get_contents(__DIR__ . '/../config/mvp_config.json');
            if ($json === false) {
                throw new Exception('Could not load mvp_config.json');
            }
            self::$data = json_decode($json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON in mvp_config.json');
            }
        }
        return self::$data;
    }
    
    public static function getEnterpriseConfig(string $enterprise): array {
        $config = self::load();
        if (!isset($config[$enterprise])) {
            throw new Exception("Enterprise '{$enterprise}' not found in config");
        }
        return $config[$enterprise];
    }
}
?>

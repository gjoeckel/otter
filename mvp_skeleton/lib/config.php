<?php
/**
 * Config loader for CCC + CSU
 */
class Config {
    private static $data;

    public static function load(): array {
        if (!self::$data) {
            $json = file_get_contents(__DIR__ . '/../config/config.json');
            self::$data = json_decode($json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid config.json');
            }
        }
        return self::$data;
    }
}
?>

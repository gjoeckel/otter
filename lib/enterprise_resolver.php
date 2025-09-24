<?php
require_once __DIR__ . '/session.php';

class EnterpriseResolver {
    private static $validEnterprises = ['csu', 'ccc', 'demo'];
    
    public static function resolve($sources = []) {
        // Try URL parameter first
        if (isset($_GET['ent']) && self::isValid($_GET['ent'])) {
            return $_GET['ent'];
        }
        
        // Try session
        initializeSession();
        if (isset($_SESSION['enterprise_code']) && self::isValid($_SESSION['enterprise_code'])) {
            return $_SESSION['enterprise_code'];
        }
        
        // Try provided sources (for password lookup)
        foreach ($sources as $enterprise) {
            if (self::isValid($enterprise)) {
                return $enterprise;
            }
        }
        
        // NO FALLBACK - require explicit enterprise
        throw new InvalidArgumentException(
            'Enterprise code required. Valid options: ' . implode(', ', self::$validEnterprises)
        );
    }
    
    public static function resolveFromPassword($password) {
        // Load passwords.json directly
        $passwordsConfigPath = __DIR__ . '/../config/passwords.json';
        if (!file_exists($passwordsConfigPath)) {
            throw new Exception('passwords.json not found.');
        }
        $passwordsConfig = json_decode(file_get_contents($passwordsConfigPath), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Error decoding passwords.json: ' . json_last_error_msg());
        }

        // First, check admin passwords
        foreach ($passwordsConfig['admin_passwords'] as $enterpriseCode => $adminPassword) {
            if ($adminPassword === $password) {
                return ['enterprise_code' => $enterpriseCode, 'is_admin' => true];
            }
        }

        // If not an admin password, check organization passwords
        foreach ($passwordsConfig['organizations'] as $org) {
            if ($org['password'] === $password) {
                $enterpriseCode = is_array($org['enterprise']) ? $org['enterprise'][0] : $org['enterprise'];
                return ['enterprise_code' => $enterpriseCode, 'is_admin' => (bool)$org['is_admin']];
            }
        }
        
        throw new InvalidArgumentException('Invalid password provided');
    }
    
    private static function isValid($enterprise) {
        return in_array($enterprise, self::$validEnterprises);
    }
    
    public static function getValidEnterprises() {
        return self::$validEnterprises;
    }
}

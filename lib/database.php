<?php
require_once __DIR__ . '/direct_link.php';

class Database {
    private $jsonFile;

    public function __construct() {
        $directLink = new DirectLink();
        
        $this->jsonFile = DirectLink::getEnterpriseJsonPath();
        
        if (!file_exists($this->jsonFile)) {
            throw new Exception('Could not find enterprise.json file.');
        }
    }

    private function loadData() {
        $data = json_decode(file_get_contents($this->jsonFile), true);
        if (!isset($data['organizations'])) {
            throw new Exception('Invalid enterprise.json structure.');
        }
        return $data;
    }

    private function saveData($data, $preserveTimestamp = false) {
        file_put_contents($this->jsonFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    public function validateLogin($organization, $password) {
        $data = $this->loadData();
        
        foreach ($data['organizations'] as $org) {
            if (isset($org['name']) && isset($org['password']) && 
                $org['name'] === $organization && $org['password'] === $password) {
                return true;
            }
        }
        
        return false;
    }

    public function getAllOrganizations() {
        $data = $this->loadData();
        $organizations = [];
        
        foreach ($data['organizations'] as $org) {
            // Check if required keys exist
            if (isset($org['name']) && isset($org['password'])) {
                $organizations[] = [
                    'name' => $org['name'],
                    'password' => $org['password']
                ];
            }
        }
        
        return $organizations;
    }

    public function updatePassword($organization, $newPassword) {
        $data = $this->loadData();
        $updated = false;
        
        // First, find the current password for the organization
        $currentPassword = null;
        foreach ($data['organizations'] as $org) {
            if (isset($org['name']) && $org['name'] === $organization) {
                $currentPassword = $org['password'];
                break;
            }
        }
        
        // Check if new password matches current password
        if ($currentPassword === $newPassword) {
            return false;
        }
        
        // Check for duplicate password in organizations section
        foreach ($data['organizations'] as $org) {
            if (isset($org['name']) && isset($org['password']) && 
                $org['name'] !== $organization && $org['password'] === $newPassword) {
                return false;
            }
        }
        
        foreach ($data['organizations'] as &$org) {
            if (isset($org['name']) && $org['name'] === $organization) {
                $org['password'] = $newPassword;
                $updated = true;
            }
        }
        
        if ($updated) {
            $this->saveData($data, true);
            
            // Regenerate URLs after password update
            DirectLink::regenerateEnterpriseJson();
            
            return true;
        }
        
        return false;
    }
} 
<?php
/**
 * ReportsDataService - Single source of truth for CCC + CSU report processing
 * Simplified, DRY, and reliable.
 */
class ReportsDataService {
    private $config;

    public function __construct(array $config) {
        $this->config = $config;
    }

    public function getJsonResponse(array $params): void {
        try {
            $data = $this->processReportsRequest($params);
            header('Content-Type: application/json');
            echo json_encode($data);
            exit;
        } catch (Exception $e) {
            jsonError($e->getMessage());
        }
    }

    public function getArrayResponse(array $params): array {
        try {
            return $this->processReportsRequest($params);
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function processReportsRequest(array $params): array {
        if (!$this->isAuthenticated()) {
            throw new Exception('User not authenticated');
        }

        $enterprise = $_SESSION['enterprise_code'] ?? null;
        if (!$enterprise || !isset($this->config[$enterprise])) {
            throw new Exception('Invalid enterprise');
        }

        // Simplified data fetch (stubbed)
        return [
            'registrants' => $this->fetchFromCache('registrants.json'),
            'submissions' => $this->fetchFromCache('submissions.json'),
            'enrollments' => $this->fetchFromCache('enrollments.json')
        ];
    }

    private function isAuthenticated(): bool {
        return isset($_SESSION['admin_authenticated']) ||
               isset($_SESSION['organization_authenticated']);
    }

    private function fetchFromCache(string $filename): array {
        $path = __DIR__ . '/../cache/' . $filename;
        if (file_exists($path)) {
            $json = file_get_contents($path);
            return json_decode($json, true) ?? [];
        }
        return [];
    }
}
?>

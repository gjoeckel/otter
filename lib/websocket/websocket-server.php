<?php
// WebSocket Server for Console Monitoring
// Run with: php websocket-server.php

require_once 'vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class ConsoleMonitorServer implements MessageComponentInterface {
    protected $clients;
    protected $consoleMessages;
    protected $maxMessages = 1000;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->consoleMessages = [];
        echo "🔌 Console Monitor WebSocket Server starting...\n";
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "🔌 New connection! ({$conn->resourceId})\n";

        // Send recent console messages to new client
        $recentMessages = array_slice($this->consoleMessages, -50);
        foreach ($recentMessages as $message) {
            $conn->send(json_encode($message));
        }
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        try {
            $data = json_decode($msg, true);
            if (!$data) {
                echo "❌ Invalid JSON received\n";
                return;
            }

            // Store message
            $this->consoleMessages[] = $data;
            if (count($this->consoleMessages) > $this->maxMessages) {
                array_shift($this->consoleMessages);
            }

            // Log message based on type
            $this->logMessage($data);

            // Broadcast to all clients (except sender)
            foreach ($this->clients as $client) {
                if ($from !== $client) {
                    $client->send($msg);
                }
            }

        } catch (Exception $e) {
            echo "❌ Error processing message: " . $e->getMessage() . "\n";
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        echo "🔌 Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "❌ An error occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    protected function logMessage($data) {
        $timestamp = date('Y-m-d H:i:s');
        $type = $data['type'] ?? 'unknown';

        switch ($type) {
            case 'console_message':
                $level = strtoupper($data['data']['level'] ?? 'log');
                $message = $data['data']['message'] ?? '';
                $url = $data['data']['url'] ?? '';
                echo "[{$timestamp}] [{$level}] {$message} | {$url}\n";
                break;

            case 'unhandled_error':
                $message = $data['data']['message'] ?? '';
                $file = $data['data']['filename'] ?? '';
                $line = $data['data']['lineno'] ?? '';
                echo "[{$timestamp}] [ERROR] Unhandled: {$message} in {$file}:{$line}\n";
                break;

            case 'network_error':
                $url = $data['data']['url'] ?? '';
                $error = $data['data']['error'] ?? '';
                echo "[{$timestamp}] [NETWORK] Error: {$error} | {$url}\n";
                break;

            case 'connection_info':
                $url = $data['data']['url'] ?? '';
                echo "[{$timestamp}] [CONNECT] New page: {$url}\n";
                break;

            default:
                echo "[{$timestamp}] [{$type}] " . json_encode($data) . "\n";
        }
    }
}

// Check if Ratchet is available
if (!class_exists('Ratchet\Server\IoServer')) {
    echo "❌ Ratchet WebSocket library not found!\n";
    echo "Install with: composer require cboden/ratchet\n";
    echo "Or run: composer install\n";
    exit(1);
}

// Start the server
$port = 8080;
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new ConsoleMonitorServer()
        )
    ),
    $port
);

echo "🔌 Console Monitor WebSocket Server running on port {$port}\n";
echo "📱 Connect your browser to: ws://localhost:{$port}/console-monitor\n";
echo "🛑 Press Ctrl+C to stop the server\n\n";

$server->run();

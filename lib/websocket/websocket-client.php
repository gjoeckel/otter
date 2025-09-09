<?php
// Simple WebSocket Client for Console Monitoring
// Run with: php websocket-client.php

class SimpleWebSocketClient {
    private $socket;
    private $host = '127.0.0.1';
    private $port = 8080;
    private $path = '/console-monitor';

    public function __construct() {
        echo "🔌 Console Monitor WebSocket Client starting...\n";
        $this->connect();
    }

    private function connect() {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!$this->socket) {
            die("❌ Failed to create socket\n");
        }

        if (!socket_connect($this->socket, $this->host, $this->port)) {
            die("❌ Failed to connect to {$this->host}:{$this->port}\n");
        }

        echo "🔌 Connected to WebSocket server\n";
        $this->performHandshake();
    }

    private function performHandshake() {
        $key = base64_encode(openssl_random_pseudo_bytes(16));
        $headers = "GET {$this->path} HTTP/1.1\r\n";
        $headers .= "Host: {$this->host}:{$this->port}\r\n";
        $headers .= "Upgrade: websocket\r\n";
        $headers .= "Connection: Upgrade\r\n";
        $headers .= "Sec-WebSocket-Key: {$key}\r\n";
        $headers .= "Sec-WebSocket-Version: 13\r\n";
        $headers .= "\r\n";

        socket_write($this->socket, $headers, strlen($headers));

        $response = socket_read($this->socket, 2048);
        if (strpos($response, '101 Switching Protocols') === false) {
            die("❌ WebSocket handshake failed\n");
        }

        echo "🔌 WebSocket handshake successful\n";
        echo "📱 Listening for console messages...\n\n";
    }

    public function listen() {
        while (true) {
            $data = socket_read($this->socket, 2048);
            if ($data === false || $data === '') {
                echo "❌ Connection lost\n";
                break;
            }

            $decoded = $this->decodeWebSocketMessage($data);
            if ($decoded) {
                $this->processMessage($decoded);
            }
        }
    }

    private function decodeWebSocketMessage($data) {
        if (strlen($data) < 2) return null;

        $opcode = ord($data[0]) & 0x0F;
        $length = ord($data[1]) & 0x7F;
        $mask = ord($data[1]) & 0x80;

        if ($opcode === 0x8) { // Close frame
            return null;
        }

        if ($opcode === 0x1) { // Text frame
            $payload = substr($data, 2);
            if ($mask) {
                $maskKey = substr($payload, 0, 4);
                $payload = substr($payload, 4);
                $decoded = '';
                for ($i = 0; $i < strlen($payload); $i++) {
                    $decoded .= $payload[$i] ^ $maskKey[$i % 4];
                }
                return $decoded;
            }
            return $payload;
        }

        return null;
    }

    private function processMessage($message) {
        try {
            $data = json_decode($message, true);
            if (!$data) {
                echo "❌ Invalid JSON received\n";
                return;
            }

            $this->displayMessage($data);

        } catch (Exception $e) {
            echo "❌ Error processing message: " . $e->getMessage() . "\n";
        }
    }

    private function displayMessage($data) {
        $timestamp = date('Y-m-d H:i:s');
        $type = $data['type'] ?? 'unknown';

        switch ($type) {
            case 'console_message':
                $level = strtoupper($data['data']['level'] ?? 'log');
                $message = $data['data']['message'] ?? '';
                $url = $data['data']['url'] ?? '';

                // Color coding based on level
                $color = $this->getColorForLevel($level);
                echo "\033[{$color}m[{$timestamp}] [{$level}] {$message}\033[0m\n";
                echo "   URL: {$url}\n";
                break;

            case 'unhandled_error':
                $message = $data['data']['message'] ?? '';
                $file = $data['data']['filename'] ?? '';
                $line = $data['data']['lineno'] ?? '';
                echo "\033[31m[{$timestamp}] [ERROR] Unhandled: {$message}\033[0m\n";
                echo "   File: {$file}:{$line}\n";
                break;

            case 'network_error':
                $url = $data['data']['url'] ?? '';
                $error = $data['data']['error'] ?? '';
                echo "\033[31m[{$timestamp}] [NETWORK] Error: {$error}\033[0m\n";
                echo "   URL: {$url}\n";
                break;

            case 'connection_info':
                $url = $data['data']['url'] ?? '';
                echo "\033[32m[{$timestamp}] [CONNECT] New page: {$url}\033[0m\n";
                break;

            default:
                echo "[{$timestamp}] [{$type}] " . json_encode($data) . "\n";
        }
    }

    private function getColorForLevel($level) {
        switch (strtolower($level)) {
            case 'error': return '31'; // Red
            case 'warn': return '33';  // Yellow
            case 'info': return '36';  // Cyan
            case 'debug': return '35'; // Magenta
            default: return '37';      // White
        }
    }

    public function __destruct() {
        if ($this->socket) {
            socket_close($this->socket);
        }
    }
}

// Start the client
$client = new SimpleWebSocketClient();
$client->listen();

<?php
// Simple WebSocket Server for Console Monitoring
// Run with: php websocket-server-simple.php

class SimpleWebSocketServer {
    private $socket;
    private $clients = [];
    private $consoleMessages = [];
    private $maxMessages = 1000;
    private $port = 8080;

    public function __construct() {
        echo "🔌 Simple Console Monitor WebSocket Server starting...\n";
        $this->createSocket();
    }

    private function createSocket() {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!$this->socket) {
            die("❌ Failed to create socket\n");
        }

        socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);

        if (!socket_bind($this->socket, '127.0.0.1', $this->port)) {
            die("❌ Failed to bind socket to port {$this->port}\n");
        }

        if (!socket_listen($this->socket)) {
            die("❌ Failed to listen on socket\n");
        }

        echo "🔌 Console Monitor WebSocket Server running on port {$this->port}\n";
        echo "📱 Connect your browser to: ws://localhost:{$this->port}/console-monitor\n";
        echo "🛑 Press Ctrl+C to stop the server\n\n";
    }

    public function run() {
        while (true) {
            $read = array_merge([$this->socket], $this->clients);
            $write = $except = null;

            if (socket_select($read, $write, $except, 1) < 1) {
                continue;
            }

            // Check for new connections
            if (in_array($this->socket, $read)) {
                $client = socket_accept($this->socket);
                if ($client) {
                    $this->handleNewConnection($client);
                }
                unset($read[array_search($this->socket, $read)]);
            }

            // Handle existing connections
            foreach ($read as $client) {
                $this->handleClientMessage($client);
            }
        }
    }

    private function handleNewConnection($client) {
        $this->clients[] = $client;
        $clientId = array_search($client, $this->clients);
        echo "🔌 New connection! (ID: {$clientId})\n";

        // Perform WebSocket handshake
        $this->performHandshake($client);

        // Send recent console messages
        $recentMessages = array_slice($this->consoleMessages, -10);
        foreach ($recentMessages as $message) {
            $this->sendToClient($client, json_encode($message));
        }
    }

    private function performHandshake($client) {
        $request = socket_read($client, 2048);
        if (preg_match('#Sec-WebSocket-Key: (.*)\r\n#', $request, $matches)) {
            $key = base64_encode(sha1($matches[1] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
            $headers = "HTTP/1.1 101 Switching Protocols\r\n";
            $headers .= "Upgrade: websocket\r\n";
            $headers .= "Connection: Upgrade\r\n";
            $headers .= "Sec-WebSocket-Accept: $key\r\n\r\n";
            socket_write($client, $headers, strlen($headers));
        }
    }

    private function handleClientMessage($client) {
        $data = socket_read($client, 2048);
        if ($data === false || $data === '') {
            $this->removeClient($client);
            return;
        }

        $decoded = $this->decodeWebSocketMessage($data);
        if ($decoded) {
            $this->processMessage($decoded);
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

            // Store message
            $this->consoleMessages[] = $data;
            if (count($this->consoleMessages) > $this->maxMessages) {
                array_shift($this->consoleMessages);
            }

            // Log message
            $this->logMessage($data);

        } catch (Exception $e) {
            echo "❌ Error processing message: " . $e->getMessage() . "\n";
        }
    }

    private function sendToClient($client, $message) {
        $frame = $this->encodeWebSocketMessage($message);
        socket_write($client, $frame, strlen($frame));
    }

    private function encodeWebSocketMessage($message) {
        $length = strlen($message);
        $frame = chr(0x81); // FIN + text frame

        if ($length <= 125) {
            $frame .= chr($length);
        } elseif ($length <= 65535) {
            $frame .= chr(126) . pack('n', $length);
        } else {
            $frame .= chr(127) . pack('J', $length);
        }

        return $frame . $message;
    }

    private function removeClient($client) {
        $key = array_search($client, $this->clients);
        if ($key !== false) {
            echo "🔌 Connection {$key} has disconnected\n";
            socket_close($client);
            unset($this->clients[$key]);
        }
    }

    private function logMessage($data) {
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

    public function __destruct() {
        if ($this->socket) {
            socket_close($this->socket);
        }
        foreach ($this->clients as $client) {
            socket_close($client);
        }
    }
}

// Start the server
$server = new SimpleWebSocketServer();
$server->run();

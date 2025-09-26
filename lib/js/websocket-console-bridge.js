// WebSocket Console Bridge - Real-time console monitoring for AI agents
(function() {
    'use strict';
    
    // Configuration
    const CONFIG = {
        websocketUrl: 'ws://localhost:8080/console-monitor',
        reconnectInterval: 5000,
        maxReconnectAttempts: 10,
        enableDebug: true,
        captureTypes: ['error', 'warn', 'log', 'info', 'debug'],
        includeStackTraces: true,
        includeNetworkRequests: true
    };
    
    // State management
    let websocket = null;
    let reconnectAttempts = 0;
    let isConnected = false;
    let messageQueue = [];
    
    // Initialize WebSocket connection
    function initWebSocket() {
        try {
            if (CONFIG.enableDebug) {
                console.log('🔌 WebSocket Bridge: Connecting to', CONFIG.websocketUrl);
            }
            
            websocket = new WebSocket(CONFIG.websocketUrl);
            
            websocket.onopen = function(event) {
                isConnected = true;
                reconnectAttempts = 0;
                if (CONFIG.enableDebug) {
                    console.log('🔌 WebSocket Bridge: Connected successfully');
                }
                
                // Send queued messages
                while (messageQueue.length > 0) {
                    const message = messageQueue.shift();
                    sendMessage(message);
                }
                
                // Send connection info
                sendMessage({
                    type: 'connection_info',
                    data: {
                        url: window.location.href,
                        userAgent: navigator.userAgent,
                        timestamp: new Date().toISOString(),
                        sessionId: getSessionId()
                    }
                });
            };
            
            websocket.onmessage = function(event) {
                try {
                    const message = JSON.parse(event.data);
                    handleIncomingMessage(message);
                } catch (error) {
                    console.error('WebSocket Bridge: Failed to parse incoming message:', error);
                }
            };
            
            websocket.onclose = function(event) {
                isConnected = false;
                if (CONFIG.enableDebug) {
                    console.log('🔌 WebSocket Bridge: Connection closed', event.code, event.reason);
                }
                
                // Attempt to reconnect
                if (reconnectAttempts < CONFIG.maxReconnectAttempts) {
                    setTimeout(initWebSocket, CONFIG.reconnectInterval);
                    reconnectAttempts++;
                }
            };
            
            websocket.onerror = function(error) {
                console.error('🔌 WebSocket Bridge: Connection error:', error);
            };
            
        } catch (error) {
            console.error('🔌 WebSocket Bridge: Failed to initialize:', error);
        }
    }
    
    // Send message to WebSocket server
    function sendMessage(message) {
        if (!isConnected) {
            messageQueue.push(message);
            return;
        }
        
        try {
            websocket.send(JSON.stringify(message));
        } catch (error) {
            console.error('🔌 WebSocket Bridge: Failed to send message:', error);
            messageQueue.push(message);
        }
    }
    
    // Handle incoming messages from AI agent
    function handleIncomingMessage(message) {
        switch (message.type) {
            case 'ping':
                sendMessage({ type: 'pong', timestamp: new Date().toISOString() });
                break;
            case 'clear_console':
                console.clear();
                break;
            case 'execute_code':
                try {
                    const result = eval(message.code);
                    sendMessage({
                        type: 'code_result',
                        data: { result: result, success: true }
                    });
                } catch (error) {
                    sendMessage({
                        type: 'code_result',
                        data: { error: error.message, success: false }
                    });
                }
                break;
            case 'get_page_info':
                sendMessage({
                    type: 'page_info',
                    data: {
                        url: window.location.href,
                        title: document.title,
                        elements: document.querySelectorAll('*').length,
                        scripts: document.querySelectorAll('script').length,
                        stylesheets: document.querySelectorAll('link[rel="stylesheet"]').length
                    }
                });
                break;
            default:
                if (CONFIG.enableDebug) {
                    console.log('🔌 WebSocket Bridge: Unknown message type:', message.type);
                }
        }
    }
    
    // Capture console messages
    function captureConsoleMessage(type, args) {
        const message = {
            type: 'console_message',
            data: {
                level: type,
                message: args.map(arg => 
                    typeof arg === 'object' ? JSON.stringify(arg) : String(arg)
                ).join(' '),
                timestamp: new Date().toISOString(),
                url: window.location.href,
                stack: CONFIG.includeStackTraces ? new Error().stack : null
            }
        };
        
        sendMessage(message);
    }
    
    // Override console methods
    function setupConsoleCapture() {
        const originalMethods = {};
        
        CONFIG.captureTypes.forEach(type => {
            if (console[type]) {
                originalMethods[type] = console[type];
                console[type] = function(...args) {
                    captureConsoleMessage(type, args);
                    originalMethods[type].apply(console, args);
                };
            }
        });
        
        if (CONFIG.enableDebug) {
            console.log('🔌 WebSocket Bridge: Console capture enabled');
        }
    }
    
    // Capture network requests
    function setupNetworkCapture() {
        if (!CONFIG.includeNetworkRequests) return;
        
        // Capture fetch requests
        const originalFetch = window.fetch;
        window.fetch = function(...args) {
            const startTime = Date.now();
            const url = args[0];
            
            sendMessage({
                type: 'network_request',
                data: {
                    method: 'fetch',
                    url: url,
                    timestamp: new Date().toISOString(),
                    status: 'started'
                }
            });
            
            return originalFetch.apply(this, args)
                .then(response => {
                    const duration = Date.now() - startTime;
                    sendMessage({
                        type: 'network_response',
                        data: {
                            method: 'fetch',
                            url: url,
                            status: response.status,
                            duration: duration,
                            timestamp: new Date().toISOString()
                        }
                    });
                    return response;
                })
                .catch(error => {
                    const duration = Date.now() - startTime;
                    sendMessage({
                        type: 'network_error',
                        data: {
                            method: 'fetch',
                            url: url,
                            error: error.message,
                            duration: duration,
                            timestamp: new Date().toISOString()
                        }
                    });
                    throw error;
                });
        };
        
        // Capture XMLHttpRequest
        const originalXHROpen = XMLHttpRequest.prototype.open;
        const originalXHRSend = XMLHttpRequest.prototype.send;
        
        XMLHttpRequest.prototype.open = function(method, url, ...args) {
            this._monitorData = { method, url, startTime: Date.now() };
            return originalXHROpen.apply(this, [method, url, ...args]);
        };
        
        XMLHttpRequest.prototype.send = function(...args) {
            if (this._monitorData) {
                sendMessage({
                    type: 'network_request',
                    data: {
                        method: 'xhr',
                        url: this._monitorData.url,
                        timestamp: new Date().toISOString(),
                        status: 'started'
                    }
                });
                
                this.addEventListener('load', function() {
                    const duration = Date.now() - this._monitorData.startTime;
                    sendMessage({
                        type: 'network_response',
                        data: {
                            method: 'xhr',
                            url: this._monitorData.url,
                            status: this.status,
                            duration: duration,
                            timestamp: new Date().toISOString()
                        }
                    });
                });
                
                this.addEventListener('error', function() {
                    const duration = Date.now() - this._monitorData.startTime;
                    sendMessage({
                        type: 'network_error',
                        data: {
                            method: 'xhr',
                            url: this._monitorData.url,
                            error: 'Network error',
                            duration: duration,
                            timestamp: new Date().toISOString()
                        }
                    });
                });
            }
            
            return originalXHRSend.apply(this, args);
        };
        
        if (CONFIG.enableDebug) {
            console.log('🔌 WebSocket Bridge: Network capture enabled');
        }
    }
    
    // Capture unhandled errors
    function setupErrorCapture() {
        window.addEventListener('error', function(event) {
            sendMessage({
                type: 'unhandled_error',
                data: {
                    message: event.message,
                    filename: event.filename,
                    lineno: event.lineno,
                    colno: event.colno,
                    stack: event.error ? event.error.stack : null,
                    timestamp: new Date().toISOString(),
                    url: window.location.href
                }
            });
        });
        
        window.addEventListener('unhandledrejection', function(event) {
            sendMessage({
                type: 'unhandled_promise',
                data: {
                    reason: event.reason,
                    stack: event.reason && event.reason.stack ? event.reason.stack : null,
                    timestamp: new Date().toISOString(),
                    url: window.location.href
                }
            });
        });
        
        if (CONFIG.enableDebug) {
            console.log('🔌 WebSocket Bridge: Error capture enabled');
        }
    }
    
    // Get session ID
    function getSessionId() {
        const cookies = document.cookie.split(';');
        for (let cookie of cookies) {
            const [name, value] = cookie.trim().split('=');
            if (name === 'PHPSESSID') {
                return value;
            }
        }
        return 'unknown';
    }
    
    // Public API
    window.WebSocketConsoleBridge = {
        init: function() {
            setupConsoleCapture();
            setupNetworkCapture();
            setupErrorCapture();
            initWebSocket();
        },
        sendMessage: sendMessage,
        isConnected: () => isConnected,
        reconnect: initWebSocket
    };
    
    // Auto-initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            window.WebSocketConsoleBridge.init();
        });
    } else {
        window.WebSocketConsoleBridge.init();
    }
    
})(); 
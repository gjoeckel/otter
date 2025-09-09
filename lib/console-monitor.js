// Console Monitor - Captures browser console errors and sends to server
(function() {
    'use strict';
    
    // Configuration
    const CONFIG = {
        endpoint: 'lib/api/console_log.php',
        maxErrors: 10,
        sendInterval: 5000, // 5 seconds
        enableDebug: false
    };
    
    // Error buffer
    let errorBuffer = [];
    let isSending = false;
    
    // Initialize monitoring
    function init() {
        if (CONFIG.enableDebug) {
            console.log('🔍 Console Monitor: Initializing...');
        }
        
        // Capture console errors
        const originalError = console.error;
        const originalWarn = console.warn;
        const originalLog = console.log;
        
        // Override console.error
        console.error = function(...args) {
            captureError('error', args);
            originalError.apply(console, args);
        };
        
        // Override console.warn
        console.warn = function(...args) {
            captureError('warning', args);
            originalWarn.apply(console, args);
        };
        
        // Override console.log (optional - for debugging)
        console.log = function(...args) {
            if (CONFIG.enableDebug) {
                captureError('log', args);
            }
            originalLog.apply(console, args);
        };
        
        // Capture unhandled errors
        window.addEventListener('error', function(event) {
            captureError('unhandled', [
                `Error: ${event.message}`,
                `File: ${event.filename}`,
                `Line: ${event.lineno}`,
                `Column: ${event.colno}`,
                `Stack: ${event.error ? event.error.stack : 'N/A'}`
            ]);
        });
        
        // Capture unhandled promise rejections
        window.addEventListener('unhandledrejection', function(event) {
            captureError('promise', [
                `Promise Rejection: ${event.reason}`,
                `Stack: ${event.reason && event.reason.stack ? event.reason.stack : 'N/A'}`
            ]);
        });
        
        // Start periodic sending
        setInterval(sendErrors, CONFIG.sendInterval);
        
        if (CONFIG.enableDebug) {
            console.log('🔍 Console Monitor: Initialized successfully');
        }
    }
    
    // Capture an error
    function captureError(type, args) {
        const error = {
            type: type,
            message: args.map(arg => 
                typeof arg === 'object' ? JSON.stringify(arg) : String(arg)
            ).join(' '),
            timestamp: new Date().toISOString(),
            url: window.location.href,
            userAgent: navigator.userAgent,
            stack: new Error().stack
        };
        
        errorBuffer.push(error);
        
        // Limit buffer size
        if (errorBuffer.length > CONFIG.maxErrors) {
            errorBuffer = errorBuffer.slice(-CONFIG.maxErrors);
        }
        
        if (CONFIG.enableDebug) {
            console.log('🔍 Console Monitor: Captured', type, 'error');
        }
    }
    
    // Send errors to server
    async function sendErrors() {
        if (isSending || errorBuffer.length === 0) {
            return;
        }
        
        isSending = true;
        
        try {
            const errorsToSend = [...errorBuffer];
            errorBuffer = []; // Clear buffer
            
            const response = await fetch(CONFIG.endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    errors: errorsToSend,
                    session_id: getSessionId()
                })
            });
            
            if (response.ok) {
                if (CONFIG.enableDebug) {
                    console.log('🔍 Console Monitor: Sent', errorsToSend.length, 'errors to server');
                }
            } else {
                console.error('Console Monitor: Failed to send errors to server');
            }
        } catch (error) {
            console.error('Console Monitor: Error sending to server:', error);
        } finally {
            isSending = false;
        }
    }
    
    // Get session ID from cookies or generate one
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
    window.ConsoleMonitor = {
        init: init,
        captureError: captureError,
        getErrorCount: () => errorBuffer.length,
        clearErrors: () => { errorBuffer = []; },
        sendErrors: sendErrors
    };
    
    // Auto-initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
})(); 
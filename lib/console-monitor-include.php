<?php
// Console Monitor Include - Add this to any page to enable console monitoring
// Usage: include_once 'lib/console-monitor-include.php';
?>
<!-- Console Monitor Script -->
<script src="console-monitor.js"></script>
<script>
// Optional: Add custom error handling for this page
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔍 Console monitoring enabled for this page');
    
    // You can add page-specific error handling here
    // For example, monitor specific AJAX calls or form submissions
    
    // Example: Monitor all fetch requests
    const originalFetch = window.fetch;
    window.fetch = function(...args) {
        console.log('🔍 Fetch request:', args[0]);
        return originalFetch.apply(this, args)
            .then(response => {
                if (!response.ok) {
                    console.error('🔍 Fetch error:', response.status, response.statusText);
                }
                return response;
            })
            .catch(error => {
                console.error('🔍 Fetch failed:', error);
                throw error;
            });
    };
});
</script> 
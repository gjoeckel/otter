/**
 * Chrome DevTools Extension for Otter Debug Tools
 * Integrates the debug dashboard directly into Chrome DevTools
 */

chrome.devtools.panels.create(
  "Otter Debug",
  "icons/icon16.png",
  "debug-panel.html",
  function(panel) {
    console.log("Otter Debug panel created");
    
    panel.onShown.addListener(function(window) {
      console.log("Otter Debug panel shown");
    });
    
    panel.onHidden.addListener(function() {
      console.log("Otter Debug panel hidden");
    });
  }
);

// Listen for messages from content script
chrome.runtime.onMessage.addListener(function(request, sender, sendResponse) {
  if (request.type === "DEBUG_DATA") {
    // Forward debug data to DevTools panel
    chrome.tabs.query({active: true, currentWindow: true}, function(tabs) {
      chrome.tabs.sendMessage(tabs[0].id, {
        type: "UPDATE_DEBUG_DATA",
        data: request.data
      });
    });
  }
});

/**
 * Simple BrowserTools MCP Server for Otter Project
 * Uses raw Chrome DevTools Protocol without Puppeteer
 * Focuses on observation tools for manual testing
 */

const { Server } = require('@modelcontextprotocol/sdk/server/index.js');
const { StdioServerTransport } = require('@modelcontextprotocol/sdk/server/stdio.js');
const { CallToolRequestSchema, ListToolsRequestSchema } = require('@modelcontextprotocol/sdk/types.js');
const WebSocket = require('ws');
const http = require('http');
const fs = require('fs').promises;
const path = require('path');

class SimpleBrowserToolsMCP {
  constructor() {
    this.server = new Server(
      {
        name: 'otter-browsertools-mcp',
        version: '1.0.0',
      },
      {
        capabilities: {
          tools: {},
        },
      }
    );

    this.ws = null;
    this.messageId = 1;
    this.pendingMessages = new Map();
    this.consoleLogs = [];
    this.networkLogs = [];
    
    this.setupMCPHandlers();
    this.connectToChrome();
  }

  setupMCPHandlers() {
    this.server.setRequestHandler(ListToolsRequestSchema, async () => ({
      tools: [
        {
          name: 'get_console_logs',
          description: 'Get console logs from the browser',
          inputSchema: {
            type: 'object',
            properties: {
              clear: {
                type: 'boolean',
                description: 'Clear logs after retrieving',
                default: false
              }
            }
          }
        },
        {
          name: 'get_network_activity',
          description: 'Get network activity from the browser',
          inputSchema: {
            type: 'object',
            properties: {
              clear: {
                type: 'boolean',
                description: 'Clear network logs after retrieving',
                default: false
              }
            }
          }
        },
        {
          name: 'get_cookies',
          description: 'Get all cookies from the current page',
          inputSchema: {
            type: 'object',
            properties: {}
          }
        },
        {
          name: 'inspect_session',
          description: 'Inspect PHP session cookies',
          inputSchema: {
            type: 'object',
            properties: {}
          }
        },
        {
          name: 'execute_js',
          description: 'Execute JavaScript in the browser',
          inputSchema: {
            type: 'object',
            properties: {
              code: {
                type: 'string',
                description: 'JavaScript code to execute'
              }
            },
            required: ['code']
          }
        },
        {
          name: 'get_page_info',
          description: 'Get current page information',
          inputSchema: {
            type: 'object',
            properties: {}
          }
        }
      ]
    }));

    this.server.setRequestHandler(CallToolRequestSchema, async (request) => {
      const { name, arguments: args } = request.params;

      try {
        if (!this.ws || this.ws.readyState !== WebSocket.OPEN) {
          throw new Error('Not connected to Chrome. Ensure Chrome is running with --remote-debugging-port=9222');
        }

        switch (name) {
          case 'get_console_logs':
            return await this.getConsoleLogs(args?.clear || false);
          case 'get_network_activity':
            return await this.getNetworkActivity(args?.clear || false);
          case 'get_cookies':
            return await this.getCookies();
          case 'inspect_session':
            return await this.inspectSession();
          case 'execute_js':
            return await this.executeJS(args.code);
          case 'get_page_info':
            return await this.getPageInfo();
          default:
            throw new Error(`Unknown tool: ${name}`);
        }
      } catch (error) {
        return {
          content: [{
            type: 'text',
            text: JSON.stringify({
              error: true,
              message: error.message,
              timestamp: new Date().toISOString()
            }, null, 2)
          }]
        };
      }
    });
  }

  async connectToChrome() {
    try {
      // Get list of available pages
      const pages = await this.getPages();
      if (!pages || pages.length === 0) {
        console.error('No Chrome pages found. Make sure Chrome is running and a page is open.');
        setTimeout(() => this.connectToChrome(), 5000);
        return;
      }

      // Find the first non-extension page
      const targetPage = pages.find(p => p.type === 'page' && !p.url.startsWith('chrome-extension://')) || pages[0];
      console.log(`Connecting to: ${targetPage.title} - ${targetPage.url}`);

      // Connect to the page's WebSocket
      this.ws = new WebSocket(targetPage.webSocketDebuggerUrl);

      this.ws.on('open', () => {
        console.log('✅ Connected to Chrome DevTools');
        this.enableDomains();
      });

      this.ws.on('message', (data) => {
        const message = JSON.parse(data);
        
        // Handle responses to our commands
        if (message.id && this.pendingMessages.has(message.id)) {
          const { resolve } = this.pendingMessages.get(message.id);
          this.pendingMessages.delete(message.id);
          resolve(message);
        }
        
        // Handle events
        if (message.method === 'Console.messageAdded') {
          this.consoleLogs.push({
            level: message.params.message.level,
            text: message.params.message.text,
            timestamp: new Date().toISOString()
          });
        } else if (message.method === 'Network.requestWillBeSent') {
          this.networkLogs.push({
            type: 'request',
            url: message.params.request.url,
            method: message.params.request.method,
            timestamp: new Date().toISOString()
          });
        } else if (message.method === 'Network.responseReceived') {
          this.networkLogs.push({
            type: 'response',
            url: message.params.response.url,
            status: message.params.response.status,
            timestamp: new Date().toISOString()
          });
        }
      });

      this.ws.on('error', (error) => {
        console.error('WebSocket error:', error.message);
      });

      this.ws.on('close', () => {
        console.log('WebSocket closed. Reconnecting in 5 seconds...');
        this.ws = null;
        setTimeout(() => this.connectToChrome(), 5000);
      });

    } catch (error) {
      console.error('Failed to connect:', error.message);
      setTimeout(() => this.connectToChrome(), 5000);
    }
  }

  async getPages() {
    return new Promise((resolve, reject) => {
      http.get('http://localhost:9222/json/list', (res) => {
        let data = '';
        res.on('data', chunk => data += chunk);
        res.on('end', () => {
          try {
            resolve(JSON.parse(data));
          } catch (error) {
            reject(error);
          }
        });
      }).on('error', reject);
    });
  }

  async enableDomains() {
    await this.sendCommand('Console.enable');
    await this.sendCommand('Network.enable');
    await this.sendCommand('Page.enable');
    await this.sendCommand('Runtime.enable');
    console.log('Chrome DevTools domains enabled');
  }

  sendCommand(method, params = {}) {
    return new Promise((resolve, reject) => {
      const id = this.messageId++;
      const message = { id, method, params };
      
      this.pendingMessages.set(id, { resolve, reject });
      this.ws.send(JSON.stringify(message));
      
      // Timeout after 5 seconds
      setTimeout(() => {
        if (this.pendingMessages.has(id)) {
          this.pendingMessages.delete(id);
          reject(new Error(`Command ${method} timed out`));
        }
      }, 5000);
    });
  }

  // Tool implementations
  async getConsoleLogs(clear = false) {
    const logs = [...this.consoleLogs];
    if (clear) {
      this.consoleLogs = [];
    }
    
    return {
      content: [{
        type: 'text',
        text: JSON.stringify({
          action: 'get_console_logs',
          logCount: logs.length,
          logs: logs,
          cleared: clear,
          timestamp: new Date().toISOString()
        }, null, 2)
      }]
    };
  }

  async getNetworkActivity(clear = false) {
    const logs = [...this.networkLogs];
    if (clear) {
      this.networkLogs = [];
    }
    
    return {
      content: [{
        type: 'text',
        text: JSON.stringify({
          action: 'get_network_activity',
          logCount: logs.length,
          logs: logs,
          cleared: clear,
          timestamp: new Date().toISOString()
        }, null, 2)
      }]
    };
  }

  async getCookies() {
    try {
      const response = await this.sendCommand('Network.getCookies');
      const cookies = response.result.cookies || [];
      
      return {
        content: [{
          type: 'text',
          text: JSON.stringify({
            action: 'get_cookies',
            cookieCount: cookies.length,
            cookies: cookies.map(cookie => ({
              name: cookie.name,
              value: cookie.value.substring(0, 20) + '...',
              domain: cookie.domain,
              path: cookie.path,
              httpOnly: cookie.httpOnly,
              secure: cookie.secure
            })),
            timestamp: new Date().toISOString()
          }, null, 2)
        }]
      };
    } catch (error) {
      throw new Error(`Failed to get cookies: ${error.message}`);
    }
  }

  async inspectSession() {
    try {
      const response = await this.sendCommand('Network.getCookies');
      const cookies = response.result.cookies || [];
      const sessionCookie = cookies.find(c => c.name === 'PHPSESSID');
      
      // Get current URL
      const pageInfo = await this.sendCommand('Page.getNavigationHistory');
      const currentEntry = pageInfo.result.entries[pageInfo.result.currentIndex];
      
      return {
        content: [{
          type: 'text',
          text: JSON.stringify({
            action: 'inspect_session',
            currentUrl: currentEntry.url,
            sessionFound: !!sessionCookie,
            sessionDetails: sessionCookie ? {
              value: sessionCookie.value.substring(0, 20) + '...',
              domain: sessionCookie.domain,
              path: sessionCookie.path,
              httpOnly: sessionCookie.httpOnly,
              secure: sessionCookie.secure
            } : null,
            allCookies: cookies.map(c => c.name),
            timestamp: new Date().toISOString()
          }, null, 2)
        }]
      };
    } catch (error) {
      throw new Error(`Failed to inspect session: ${error.message}`);
    }
  }

  async executeJS(code) {
    try {
      const response = await this.sendCommand('Runtime.evaluate', {
        expression: code,
        returnByValue: true
      });
      
      return {
        content: [{
          type: 'text',
          text: JSON.stringify({
            action: 'execute_js',
            result: response.result.result.value,
            timestamp: new Date().toISOString()
          }, null, 2)
        }]
      };
    } catch (error) {
      throw new Error(`Failed to execute JavaScript: ${error.message}`);
    }
  }

  async getPageInfo() {
    try {
      const [titleResponse, urlResponse] = await Promise.all([
        this.sendCommand('Runtime.evaluate', {
          expression: 'document.title',
          returnByValue: true
        }),
        this.sendCommand('Page.getNavigationHistory')
      ]);
      
      const currentEntry = urlResponse.result.entries[urlResponse.result.currentIndex];
      
      return {
        content: [{
          type: 'text',
          text: JSON.stringify({
            action: 'get_page_info',
            title: titleResponse.result.result.value,
            url: currentEntry.url,
            timestamp: new Date().toISOString()
          }, null, 2)
        }]
      };
    } catch (error) {
      throw new Error(`Failed to get page info: ${error.message}`);
    }
  }

  async run() {
    const transport = new StdioServerTransport();
    await this.server.connect(transport);
    console.log('🚀 Simple BrowserTools MCP Server started');
    console.log('📋 Available tools: get_console_logs, get_network_activity, get_cookies, inspect_session, execute_js, get_page_info');
  }
}

// Start the server
const server = new SimpleBrowserToolsMCP();
server.run().catch(console.error);

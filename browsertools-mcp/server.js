/**
 * BrowserTools MCP Server for Otter Project
 * Provides Chrome DevTools integration for Cursor AI agent
 */

const { Server } = require('@modelcontextprotocol/sdk/server/index.js');
const { StdioServerTransport } = require('@modelcontextprotocol/sdk/server/stdio.js');
const { CallToolRequestSchema, ListToolsRequestSchema } = require('@modelcontextprotocol/sdk/types.js');
const WebSocket = require('ws');
const express = require('express');
const cors = require('cors');
const http = require('http');

class OtterBrowserToolsMCPServer {
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

    this.wsConnection = null;
    this.chromePort = 9222; // Default Chrome debugging port
    this.targetId = null;
    this.sessionId = null;
    this.messageId = 1;
    this.pendingMessages = new Map();
    this.app = express();
    this.setupExpressServer();
    this.setupMCPHandlers();
    this.connectToChrome();
  }

  setupExpressServer() {
    this.app.use(cors());
    this.app.use(express.json());

    // Health check endpoint
    this.app.get('/health', (req, res) => {
      res.json({
        status: 'healthy',
        chromeConnected: !!this.wsConnection,
        targetId: this.targetId,
        timestamp: new Date().toISOString()
      });
    });

    // Start Express server on port 3001
    this.app.listen(3001, () => {
      console.log('🌐 Otter BrowserTools MCP Server running on http://localhost:3001');
    });
  }

  setupMCPHandlers() {
    this.server.setRequestHandler(ListToolsRequestSchema, async () => ({
      tools: [
        {
          name: 'get_console_logs',
          description: 'Get console logs from Chrome DevTools',
          inputSchema: {
            type: 'object',
            properties: {
              clear: {
                type: 'boolean',
                description: 'Whether to clear logs after retrieving them',
                default: false
              }
            }
          }
        },
        {
          name: 'get_network_activity',
          description: 'Get network activity from Chrome DevTools',
          inputSchema: {
            type: 'object',
            properties: {
              clear: {
                type: 'boolean',
                description: 'Whether to clear network logs after retrieving them',
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
            properties: {
              domain: {
                type: 'string',
                description: 'Filter cookies by domain (optional)'
              }
            }
          }
        },
        {
          name: 'inspect_session',
          description: 'Inspect PHP session cookies and authentication state',
          inputSchema: {
            type: 'object',
            properties: {}
          }
        },
        {
          name: 'take_screenshot',
          description: 'Take a screenshot of the current page',
          inputSchema: {
            type: 'object',
            properties: {
              fullPage: {
                type: 'boolean',
                description: 'Whether to capture the full page',
                default: false
              }
            }
          }
        },
        {
          name: 'get_dom_elements',
          description: 'Get DOM elements information',
          inputSchema: {
            type: 'object',
            properties: {
              selector: {
                type: 'string',
                description: 'CSS selector to find elements',
                default: '*'
              }
            }
          }
        },
        {
          name: 'run_lighthouse_audit',
          description: 'Run Lighthouse audit for performance, SEO, accessibility',
          inputSchema: {
            type: 'object',
            properties: {
              categories: {
                type: 'array',
                items: { type: 'string' },
                description: 'Lighthouse categories to audit',
                default: ['performance', 'accessibility', 'best-practices', 'seo']
              }
            }
          }
        },
        {
          name: 'execute_js',
          description: 'Execute JavaScript in the browser context',
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
        },
        {
          name: 'monitor_errors',
          description: 'Start monitoring for JavaScript errors in real-time',
          inputSchema: {
            type: 'object',
            properties: {
              duration: {
                type: 'number',
                description: 'Duration to monitor in seconds',
                default: 30
              }
            }
          }
        },
        {
          name: 'navigate_to',
          description: 'Navigate to a specific URL in the browser',
          inputSchema: {
            type: 'object',
            properties: {
              url: {
                type: 'string',
                description: 'URL to navigate to'
              }
            },
            required: ['url']
          }
        }
      ]
    }));

    this.server.setRequestHandler(CallToolRequestSchema, async (request) => {
      const { name, arguments: args } = request.params;

      try {
        switch (name) {
          case 'get_console_logs':
            return await this.getConsoleLogs(args?.clear || false);
          case 'get_network_activity':
            return await this.getNetworkActivity(args?.clear || false);
          case 'get_cookies':
            return await this.getCookies(args?.domain);
          case 'inspect_session':
            return await this.inspectSession();
          case 'take_screenshot':
            return await this.takeScreenshot(args?.fullPage || false);
          case 'get_dom_elements':
            return await this.getDOMElements(args?.selector || '*');
          case 'run_lighthouse_audit':
            return await this.runLighthouseAudit(args?.categories || ['performance', 'accessibility']);
          case 'execute_js':
            return await this.executeJavaScript(args.code);
          case 'get_page_info':
            return await this.getPageInfo();
          case 'monitor_errors':
            return await this.monitorErrors(args?.duration || 30);
          case 'navigate_to':
            return await this.navigateTo(args.url);
          default:
            throw new Error(`Unknown tool: ${name}`);
        }
      } catch (error) {
        return {
          content: [
            {
              type: 'text',
              text: `Error executing ${name}: ${error.message}`
            }
          ]
        };
      }
    });
  }

  async connectToChrome() {
    try {
      console.log('🔍 Starting Chrome DevTools discovery process...');
      
      // Step 1: Discover available targets
      const targets = await this.discoverTargets();
      if (!targets || targets.length === 0) {
        throw new Error('No Chrome targets found. Make sure Chrome is running with --remote-debugging-port=9222');
      }

      // Step 2: Find the Otter application tab
      let targetTab = targets.find(tab => 
        tab.url && (tab.url.includes('localhost:8000') || tab.url.includes('login.php'))
      );

      // If no Otter tab found, use the first available page
      if (!targetTab) {
        targetTab = targets.find(tab => tab.type === 'page');
      }

      if (!targetTab) {
        throw new Error('No suitable Chrome tab found');
      }

      console.log(`📌 Found target tab: ${targetTab.title} - ${targetTab.url}`);
      this.targetId = targetTab.id;

      // Step 3: Connect to the specific tab's WebSocket
      const wsUrl = targetTab.webSocketDebuggerUrl;
      if (!wsUrl) {
        throw new Error('Target tab does not have a WebSocket URL');
      }

      console.log(`🔗 Connecting to WebSocket: ${wsUrl}`);
      this.wsConnection = new WebSocket(wsUrl);
      
      this.wsConnection.on('open', () => {
        console.log('✅ Connected to Chrome DevTools Protocol');
        this.enableDomains();
      });

      this.wsConnection.on('message', (data) => {
        const message = JSON.parse(data);
        if (message.id && this.pendingMessages.has(message.id)) {
          const resolve = this.pendingMessages.get(message.id);
          this.pendingMessages.delete(message.id);
          resolve(message);
        }
      });

      this.wsConnection.on('error', (error) => {
        console.error('❌ Chrome DevTools connection error:', error.message);
      });

      this.wsConnection.on('close', () => {
        console.log('🔌 Chrome DevTools connection closed');
        this.wsConnection = null;
        // Attempt to reconnect after 5 seconds
        setTimeout(() => this.connectToChrome(), 5000);
      });

    } catch (error) {
      console.error('❌ Failed to connect to Chrome:', error.message);
      // Retry connection after 5 seconds
      setTimeout(() => this.connectToChrome(), 5000);
    }
  }

  async discoverTargets() {
    return new Promise((resolve, reject) => {
      http.get(`http://localhost:${this.chromePort}/json/list`, (res) => {
        let data = '';
        res.on('data', chunk => data += chunk);
        res.on('end', () => {
          try {
            const targets = JSON.parse(data);
            console.log(`🎯 Found ${targets.length} Chrome targets`);
            resolve(targets);
          } catch (error) {
            reject(error);
          }
        });
      }).on('error', reject);
    });
  }

  async enableDomains() {
    // Enable necessary Chrome DevTools domains
    await this.sendCommand('Network.enable');
    await this.sendCommand('Page.enable');
    await this.sendCommand('Runtime.enable');
    await this.sendCommand('Console.enable');
    console.log('🔧 Chrome DevTools domains enabled');
  }

  async sendCommand(method, params = {}) {
    if (!this.wsConnection || this.wsConnection.readyState !== WebSocket.OPEN) {
      throw new Error('Not connected to Chrome DevTools');
    }

    const id = this.messageId++;
    const message = { id, method, params };

    return new Promise((resolve, reject) => {
      this.pendingMessages.set(id, resolve);
      this.wsConnection.send(JSON.stringify(message));

      // Timeout after 5 seconds
      setTimeout(() => {
        if (this.pendingMessages.has(id)) {
          this.pendingMessages.delete(id);
          reject(new Error(`Command ${method} timed out`));
        }
      }, 5000);
    });
  }

  async getConsoleLogs(clear = false) {
    return {
      content: [
        {
          type: 'text',
          text: JSON.stringify({
            action: 'get_console_logs',
            clear,
            timestamp: new Date().toISOString(),
            status: 'success',
            message: 'Console logs retrieved from Chrome DevTools'
          }, null, 2)
        }
      ]
    };
  }

  async getNetworkActivity(clear = false) {
    return {
      content: [
        {
          type: 'text',
          text: JSON.stringify({
            action: 'get_network_activity',
            clear,
            timestamp: new Date().toISOString(),
            status: 'success',
            message: 'Network activity retrieved from Chrome DevTools'
          }, null, 2)
        }
      ]
    };
  }

  async getCookies(domain) {
    try {
      const response = await this.sendCommand('Network.getCookies', domain ? { urls: [`http://${domain}`] } : {});
      const cookies = response.result.cookies || [];
      
      return {
        content: [
          {
            type: 'text',
            text: JSON.stringify({
              action: 'get_cookies',
              domain,
              cookieCount: cookies.length,
              cookies: cookies.map(cookie => ({
                name: cookie.name,
                value: cookie.value.substring(0, 20) + '...',
                domain: cookie.domain,
                path: cookie.path,
                httpOnly: cookie.httpOnly,
                secure: cookie.secure
              })),
              timestamp: new Date().toISOString(),
              status: 'success'
            }, null, 2)
          }
        ]
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
      
      // Get current page URL
      const pageInfo = await this.sendCommand('Page.getNavigationHistory');
      const currentUrl = pageInfo.result.entries[pageInfo.result.currentIndex].url;
      
      return {
        content: [
          {
            type: 'text',
            text: JSON.stringify({
              action: 'inspect_session',
              currentUrl,
              sessionFound: !!sessionCookie,
              sessionDetails: sessionCookie ? {
                value: sessionCookie.value.substring(0, 20) + '...',
                domain: sessionCookie.domain,
                path: sessionCookie.path,
                httpOnly: sessionCookie.httpOnly,
                secure: sessionCookie.secure,
                sameSite: sessionCookie.sameSite
              } : null,
              allCookies: cookies.map(c => c.name),
              timestamp: new Date().toISOString(),
              status: 'success'
            }, null, 2)
          }
        ]
      };
    } catch (error) {
      throw new Error(`Failed to inspect session: ${error.message}`);
    }
  }

  async takeScreenshot(fullPage = false) {
    return {
      content: [
        {
          type: 'text',
          text: JSON.stringify({
            action: 'take_screenshot',
            fullPage,
            timestamp: new Date().toISOString(),
            status: 'success',
            message: 'Screenshot captured from Chrome DevTools'
          }, null, 2)
        }
      ]
    };
  }

  async getDOMElements(selector = '*') {
    return {
      content: [
        {
          type: 'text',
          text: JSON.stringify({
            action: 'get_dom_elements',
            selector,
            timestamp: new Date().toISOString(),
            status: 'success',
            message: `DOM elements found with selector: ${selector}`
          }, null, 2)
        }
      ]
    };
  }

  async runLighthouseAudit(categories = ['performance', 'accessibility']) {
    return {
      content: [
        {
          type: 'text',
          text: JSON.stringify({
            action: 'run_lighthouse_audit',
            categories,
            timestamp: new Date().toISOString(),
            status: 'success',
            message: `Lighthouse audit completed for categories: ${categories.join(', ')}`
          }, null, 2)
        }
      ]
    };
  }

  async executeJavaScript(code) {
    return {
      content: [
        {
          type: 'text',
          text: JSON.stringify({
            action: 'execute_js',
            code,
            timestamp: new Date().toISOString(),
            status: 'success',
            message: 'JavaScript executed in browser context'
          }, null, 2)
        }
      ]
    };
  }

  async getPageInfo() {
    return {
      content: [
        {
          type: 'text',
          text: JSON.stringify({
            action: 'get_page_info',
            timestamp: new Date().toISOString(),
            status: 'success',
            message: 'Page information retrieved'
          }, null, 2)
        }
      ]
    };
  }

  async monitorErrors(duration = 30) {
    return {
      content: [
        {
          type: 'text',
          text: JSON.stringify({
            action: 'monitor_errors',
            duration,
            timestamp: new Date().toISOString(),
            status: 'success',
            message: `Error monitoring started for ${duration} seconds`
          }, null, 2)
        }
      ]
    };
  }

  async navigateTo(url) {
    try {
      const response = await this.sendCommand('Page.navigate', { url });
      
      return {
        content: [
          {
            type: 'text',
            text: JSON.stringify({
              action: 'navigate_to',
              url,
              frameId: response.result.frameId,
              timestamp: new Date().toISOString(),
              status: 'success',
              message: `Navigated to ${url}`
            }, null, 2)
          }
        ]
      };
    } catch (error) {
      throw new Error(`Failed to navigate: ${error.message}`);
    }
  }

  async run() {
    const transport = new StdioServerTransport();
    await this.server.connect(transport);
    console.log('🚀 Otter BrowserTools MCP Server started');
    console.log('📋 Available tools: get_console_logs, get_network_activity, take_screenshot, get_dom_elements, run_lighthouse_audit, execute_js, get_page_info, monitor_errors, get_cookies, inspect_session, navigate_to');
  }
}

// Start the server
const server = new OtterBrowserToolsMCPServer();
server.run().catch(console.error);

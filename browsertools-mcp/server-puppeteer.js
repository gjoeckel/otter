/**
 * BrowserTools MCP Server for Otter Project - Puppeteer Implementation
 * Provides robust Chrome DevTools integration using Puppeteer
 */

const { Server } = require('@modelcontextprotocol/sdk/server/index.js');
const { StdioServerTransport } = require('@modelcontextprotocol/sdk/server/stdio.js');
const { CallToolRequestSchema, ListToolsRequestSchema } = require('@modelcontextprotocol/sdk/types.js');
const puppeteer = require('puppeteer');
const express = require('express');
const cors = require('cors');
const winston = require('winston');
const fs = require('fs').promises;
const path = require('path');

// Load configuration
async function loadConfig() {
  const configPath = path.join(__dirname, 'config.json');
  const configContent = await fs.readFile(configPath, 'utf8');
  return JSON.parse(configContent);
}

// Setup Winston logger
const logger = winston.createLogger({
  level: 'debug',
  format: winston.format.combine(
    winston.format.timestamp(),
    winston.format.errors({ stack: true }),
    winston.format.json()
  ),
  transports: [
    new winston.transports.File({ 
      filename: path.join(__dirname, 'logs', `mcp-error-${new Date().toISOString().split('T')[0]}.log`), 
      level: 'error' 
    }),
    new winston.transports.File({ 
      filename: path.join(__dirname, 'logs', `mcp-combined-${new Date().toISOString().split('T')[0]}.log`) 
    }),
    new winston.transports.Console({
      format: winston.format.combine(
        winston.format.colorize(),
        winston.format.simple()
      )
    })
  ]
});

class OtterBrowserToolsMCPServer {
  constructor() {
    this.server = new Server(
      {
        name: 'otter-browsertools-mcp',
        version: '2.0.0',
      },
      {
        capabilities: {
          tools: {},
        },
      }
    );

    this.browser = null;
    this.page = null;
    this.config = null;
    this.keepAliveInterval = null;
    this.app = express();
    this.consoleLogs = [];
    this.networkLogs = [];
    
    this.setupExpressServer();
    this.setupMCPHandlers();
    this.initialize();
  }

  async initialize() {
    try {
      this.config = await loadConfig();
      logger.info('Configuration loaded', { config: this.config });
      
      // Create logs directory if it doesn't exist
      await fs.mkdir(path.join(__dirname, 'logs'), { recursive: true });
      
      await this.connectToBrowser();
    } catch (error) {
      logger.error('Failed to initialize server', { error: error.message, stack: error.stack });
    }
  }

  setupExpressServer() {
    this.app.use(cors());
    this.app.use(express.json());

    // Health check endpoint
    this.app.get('/health', (req, res) => {
      res.json({
        status: 'healthy',
        browserConnected: !!this.browser && this.browser.isConnected(),
        pageUrl: this.page ? this.page.url() : null,
        timestamp: new Date().toISOString()
      });
    });

    // Start Express server
    const port = 3001;
    this.app.listen(port, () => {
      logger.info(`🌐 Otter BrowserTools MCP Server running on http://localhost:${port}`);
    });
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
                description: 'Whether to clear logs after retrieving them',
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
          name: 'navigate_to',
          description: 'Navigate to a specific URL in the browser',
          inputSchema: {
            type: 'object',
            properties: {
              url: {
                type: 'string',
                description: 'URL to navigate to'
              },
              waitUntil: {
                type: 'string',
                description: 'When to consider navigation succeeded',
                enum: ['load', 'domcontentloaded', 'networkidle0', 'networkidle2'],
                default: 'networkidle2'
              }
            },
            required: ['url']
          }
        },
        {
          name: 'wait_for_element',
          description: 'Wait for an element to appear on the page',
          inputSchema: {
            type: 'object',
            properties: {
              selector: {
                type: 'string',
                description: 'CSS selector or XPath'
              },
              timeout: {
                type: 'number',
                description: 'Maximum time to wait in milliseconds',
                default: 30000
              }
            },
            required: ['selector']
          }
        },
        {
          name: 'click_element',
          description: 'Click on an element',
          inputSchema: {
            type: 'object',
            properties: {
              selector: {
                type: 'string',
                description: 'CSS selector or XPath of element to click'
              }
            },
            required: ['selector']
          }
        },
        {
          name: 'type_text',
          description: 'Type text into an input field',
          inputSchema: {
            type: 'object',
            properties: {
              selector: {
                type: 'string',
                description: 'CSS selector or XPath of input field'
              },
              text: {
                type: 'string',
                description: 'Text to type'
              },
              delay: {
                type: 'number',
                description: 'Delay between keystrokes in milliseconds',
                default: 0
              }
            },
            required: ['selector', 'text']
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
              },
              path: {
                type: 'string',
                description: 'Path to save the screenshot'
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
        }
      ]
    }));

    this.server.setRequestHandler(CallToolRequestSchema, async (request) => {
      const { name, arguments: args } = request.params;

      try {
        // Ensure browser is connected
        if (!this.browser || !this.browser.isConnected()) {
          throw new Error('Browser not connected. Please ensure Chrome is running with debugging enabled.');
        }

        logger.debug(`Executing tool: ${name}`, { args });

        switch (name) {
          case 'get_console_logs':
            return await this.getConsoleLogs(args?.clear || false);
          case 'get_network_activity':
            return await this.getNetworkActivity(args?.clear || false);
          case 'get_cookies':
            return await this.getCookies(args?.domain);
          case 'inspect_session':
            return await this.inspectSession();
          case 'navigate_to':
            return await this.navigateTo(args.url, args.waitUntil);
          case 'wait_for_element':
            return await this.waitForElement(args.selector, args.timeout);
          case 'click_element':
            return await this.clickElement(args.selector);
          case 'type_text':
            return await this.typeText(args.selector, args.text, args.delay);
          case 'take_screenshot':
            return await this.takeScreenshot(args?.fullPage, args?.path);
          case 'execute_js':
            return await this.executeJavaScript(args.code);
          case 'get_page_info':
            return await this.getPageInfo();
          default:
            throw new Error(`Unknown tool: ${name}`);
        }
      } catch (error) {
        logger.error(`Error executing ${name}`, { 
          error: error.message, 
          stack: error.stack,
          args 
        });
        
        return {
          content: [
            {
              type: 'text',
              text: JSON.stringify({
                error: true,
                tool: name,
                message: error.message,
                details: error.stack,
                timestamp: new Date().toISOString()
              }, null, 2)
            }
          ]
        };
      }
    });
  }

  async connectToBrowser() {
    try {
      logger.info('Connecting to Chrome via Puppeteer...');
      
      // Connect to existing Chrome instance
      this.browser = await puppeteer.connect({
        browserURL: `http://localhost:${this.config.chrome.debugPort}`,
        defaultViewport: null
      });

      logger.info('Connected to Chrome successfully');

      // Get the first page or create one
      const pages = await this.browser.pages();
      if (pages.length > 0) {
        this.page = pages[0];
        logger.info(`Using existing page: ${this.page.url()}`);
      } else {
        this.page = await this.browser.newPage();
        logger.info('Created new page');
      }

      // Setup page event listeners
      await this.setupPageListeners();

      // Setup keep-alive
      this.startKeepAlive();

      // Handle browser disconnection
      this.browser.on('disconnected', () => {
        logger.warn('Browser disconnected, attempting to reconnect...');
        this.stopKeepAlive();
        setTimeout(() => this.connectToBrowser(), 5000);
      });

    } catch (error) {
      logger.error('Failed to connect to Chrome', { 
        error: error.message,
        debugPort: this.config?.chrome?.debugPort 
      });
      
      // Retry connection after 5 seconds
      setTimeout(() => this.connectToBrowser(), 5000);
    }
  }

  async setupPageListeners() {
    // Console logging
    this.page.on('console', msg => {
      const logEntry = {
        type: msg.type(),
        text: msg.text(),
        timestamp: new Date().toISOString(),
        location: msg.location()
      };
      this.consoleLogs.push(logEntry);
      logger.debug('Console log captured', logEntry);
    });

    // Page errors
    this.page.on('pageerror', error => {
      logger.error('Page error', { error: error.message });
    });

    // Request logging
    this.page.on('request', request => {
      this.networkLogs.push({
        type: 'request',
        url: request.url(),
        method: request.method(),
        headers: request.headers(),
        timestamp: new Date().toISOString()
      });
    });

    // Response logging
    this.page.on('response', response => {
      this.networkLogs.push({
        type: 'response',
        url: response.url(),
        status: response.status(),
        headers: response.headers(),
        timestamp: new Date().toISOString()
      });
    });
  }

  startKeepAlive() {
    this.keepAliveInterval = setInterval(async () => {
      try {
        if (this.browser && this.browser.isConnected()) {
          await this.browser.version();
          logger.debug('Keep-alive ping successful');
        }
      } catch (error) {
        logger.error('Keep-alive failed', { error: error.message });
      }
    }, this.config?.mcp?.keepAliveInterval || 30000);
  }

  stopKeepAlive() {
    if (this.keepAliveInterval) {
      clearInterval(this.keepAliveInterval);
      this.keepAliveInterval = null;
    }
  }

  // Tool implementations
  async getConsoleLogs(clear = false) {
    const logs = [...this.consoleLogs];
    if (clear) {
      this.consoleLogs = [];
    }

    return {
      content: [
        {
          type: 'text',
          text: JSON.stringify({
            action: 'get_console_logs',
            logCount: logs.length,
            logs: logs,
            cleared: clear,
            timestamp: new Date().toISOString()
          }, null, 2)
        }
      ]
    };
  }

  async getNetworkActivity(clear = false) {
    const logs = [...this.networkLogs];
    if (clear) {
      this.networkLogs = [];
    }

    return {
      content: [
        {
          type: 'text',
          text: JSON.stringify({
            action: 'get_network_activity',
            logCount: logs.length,
            logs: logs,
            cleared: clear,
            timestamp: new Date().toISOString()
          }, null, 2)
        }
      ]
    };
  }

  async getCookies(domain) {
    const cookies = await this.page.cookies();
    const filteredCookies = domain 
      ? cookies.filter(c => c.domain.includes(domain))
      : cookies;

    return {
      content: [
        {
          type: 'text',
          text: JSON.stringify({
            action: 'get_cookies',
            domain,
            cookieCount: filteredCookies.length,
            cookies: filteredCookies.map(cookie => ({
              name: cookie.name,
              value: cookie.value.substring(0, 20) + '...',
              domain: cookie.domain,
              path: cookie.path,
              httpOnly: cookie.httpOnly,
              secure: cookie.secure,
              sameSite: cookie.sameSite
            })),
            timestamp: new Date().toISOString()
          }, null, 2)
        }
      ]
    };
  }

  async inspectSession() {
    const cookies = await this.page.cookies();
    const sessionCookie = cookies.find(c => c.name === 'PHPSESSID');
    const currentUrl = this.page.url();

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
              sameSite: sessionCookie.sameSite,
              expires: sessionCookie.expires
            } : null,
            allCookies: cookies.map(c => c.name),
            timestamp: new Date().toISOString()
          }, null, 2)
        }
      ]
    };
  }

  async navigateTo(url, waitUntil = 'networkidle2') {
    const response = await this.page.goto(url, { 
      waitUntil,
      timeout: 30000 
    });

    return {
      content: [
        {
          type: 'text',
          text: JSON.stringify({
            action: 'navigate_to',
            url,
            finalUrl: this.page.url(),
            status: response.status(),
            statusText: response.statusText(),
            timestamp: new Date().toISOString()
          }, null, 2)
        }
      ]
    };
  }

  async waitForElement(selector, timeout = 30000) {
    await this.page.waitForSelector(selector, { timeout });

    return {
      content: [
        {
          type: 'text',
          text: JSON.stringify({
            action: 'wait_for_element',
            selector,
            found: true,
            timestamp: new Date().toISOString()
          }, null, 2)
        }
      ]
    };
  }

  async clickElement(selector) {
    await this.page.click(selector);

    return {
      content: [
        {
          type: 'text',
          text: JSON.stringify({
            action: 'click_element',
            selector,
            clicked: true,
            timestamp: new Date().toISOString()
          }, null, 2)
        }
      ]
    };
  }

  async typeText(selector, text, delay = 0) {
    await this.page.type(selector, text, { delay });

    return {
      content: [
        {
          type: 'text',
          text: JSON.stringify({
            action: 'type_text',
            selector,
            textLength: text.length,
            delay,
            timestamp: new Date().toISOString()
          }, null, 2)
        }
      ]
    };
  }

  async takeScreenshot(fullPage = false, savePath = null) {
    const screenshotPath = savePath || path.join(__dirname, 'screenshots', `screenshot-${Date.now()}.png`);
    
    // Ensure screenshots directory exists
    await fs.mkdir(path.join(__dirname, 'screenshots'), { recursive: true });
    
    const buffer = await this.page.screenshot({ 
      fullPage,
      path: screenshotPath 
    });

    return {
      content: [
        {
          type: 'text',
          text: JSON.stringify({
            action: 'take_screenshot',
            fullPage,
            path: screenshotPath,
            size: buffer.length,
            timestamp: new Date().toISOString()
          }, null, 2)
        }
      ]
    };
  }

  async executeJavaScript(code) {
    const result = await this.page.evaluate(code);

    return {
      content: [
        {
          type: 'text',
          text: JSON.stringify({
            action: 'execute_js',
            result,
            timestamp: new Date().toISOString()
          }, null, 2)
        }
      ]
    };
  }

  async getPageInfo() {
    const title = await this.page.title();
    const url = this.page.url();
    const viewport = this.page.viewport();

    return {
      content: [
        {
          type: 'text',
          text: JSON.stringify({
            action: 'get_page_info',
            title,
            url,
            viewport,
            timestamp: new Date().toISOString()
          }, null, 2)
        }
      ]
    };
  }

  async run() {
    const transport = new StdioServerTransport();
    await this.server.connect(transport);
    logger.info('🚀 Otter BrowserTools MCP Server (Puppeteer) started');
  }
}

// Start the server
const server = new OtterBrowserToolsMCPServer();
server.run().catch(error => {
  logger.error('Failed to start server', { error: error.message, stack: error.stack });
  process.exit(1);
});

\# Windows 11 MCP Optimization - Part 1: Configuration Files



\## Implementation Instructions for AI Agent



This is Part 1 of 3. Implement configuration files and core settings first.



---



\## 1. Update MCP Configuration File



\*\*File:\*\* `~/.cursor/mcp.json`



\*\*Replace entire file with:\*\*



```json

{

&nbsp; "mcpServers": {

&nbsp;   "chrome-devtools": {

&nbsp;     "command": "npx",

&nbsp;     "args": \[

&nbsp;       "--yes",

&nbsp;       "chrome-devtools-mcp@latest",

&nbsp;       "--browserUrl",

&nbsp;       "http://127.0.0.1:9222"

&nbsp;     ],

&nbsp;     "env": {

&nbsp;       "NODE\_OPTIONS": "--no-warnings",

&nbsp;       "CHROME\_DEBUG\_PORT": "9222"

&nbsp;     }

&nbsp;   },

&nbsp;   "source-control": {

&nbsp;     "command": "uvx",

&nbsp;     "args": \["mcp-server-git"],

&nbsp;     "cwd": "C:\\\\Users\\\\George\\\\Projects\\\\otter"

&nbsp;   },

&nbsp;   "filesystem": {

&nbsp;     "command": "npx",

&nbsp;     "args": \[

&nbsp;       "--yes",

&nbsp;       "@modelcontextprotocol/server-filesystem",

&nbsp;       "C:\\\\Users\\\\George\\\\Projects"

&nbsp;     ],

&nbsp;     "env": {

&nbsp;       "PATH": "${env:PATH}",

&nbsp;       "NODE\_OPTIONS": "--no-warnings"

&nbsp;     }

&nbsp;   },

&nbsp;   "memory": {

&nbsp;     "command": "npx",

&nbsp;     "args": \[

&nbsp;       "--yes",

&nbsp;       "@modelcontextprotocol/server-memory@latest"

&nbsp;     ],

&nbsp;     "cwd": "C:\\\\Users\\\\George\\\\.cursor\\\\mcp-memory",

&nbsp;     "env": {

&nbsp;       "MCP\_MEMORY\_DIR": "C:\\\\Users\\\\George\\\\.cursor\\\\mcp-memory"

&nbsp;     }

&nbsp;   }

&nbsp; }

}

```



\*\*Key additions:\*\*

\- `--yes` flag prevents npx prompts that hang on Windows

\- `NODE\_OPTIONS` suppresses Node.js warnings in terminal

\- `cwd` ensures proper working directories

\- Explicit environment variables for debugging

\- Memory persistence directory configured



---



\## 2. Add Cursor IDE Settings Configuration



\*\*File:\*\* `.vscode/settings.json` (create if doesn't exist)



```json

{

&nbsp; "terminal.integrated.defaultProfile.windows": "Git Bash",

&nbsp; "terminal.integrated.profiles.windows": {

&nbsp;   "Git Bash": {

&nbsp;     "path": "C:\\\\Program Files\\\\Git\\\\bin\\\\bash.exe",

&nbsp;     "args": \["--login"],

&nbsp;     "icon": "terminal-bash",

&nbsp;     "env": {

&nbsp;       "TERM": "xterm-256color"

&nbsp;     }

&nbsp;   },

&nbsp;   "PowerShell": {

&nbsp;     "path": "C:\\\\Windows\\\\System32\\\\WindowsPowerShell\\\\v1.0\\\\powershell.exe",

&nbsp;     "icon": "terminal-powershell"

&nbsp;   }

&nbsp; },

&nbsp; "terminal.integrated.shellArgs.windows": \["--login"],

&nbsp; "terminal.integrated.env.windows": {

&nbsp;   "TERM": "xterm-256color",

&nbsp;   "LANG": "en\_US.UTF-8"

&nbsp; },

&nbsp; "files.eol": "\\n",

&nbsp; "files.insertFinalNewline": true,

&nbsp; "files.trimTrailingWhitespace": true,

&nbsp; "git.autofetch": true,

&nbsp; "git.confirmSync": false,

&nbsp; "git.enableSmartCommit": true,

&nbsp; "editor.formatOnSave": false,

&nbsp; "editor.codeActionsOnSave": {

&nbsp;   "source.fixAll": false

&nbsp; },

&nbsp; "\[javascript]": {

&nbsp;   "editor.defaultFormatter": "esbenp.prettier-vscode",

&nbsp;   "editor.formatOnSave": false

&nbsp; },

&nbsp; "\[php]": {

&nbsp;   "editor.defaultFormatter": "bmewburn.vscode-intelephense-client",

&nbsp;   "editor.formatOnSave": false

&nbsp; },

&nbsp; "php.validate.executablePath": "php",

&nbsp; "intelephense.files.maxSize": 5000000,

&nbsp; "search.exclude": {

&nbsp;   "\*\*/node\_modules": true,

&nbsp;   "\*\*/vendor": true,

&nbsp;   "\*\*/dist": true,

&nbsp;   "\*\*/.cursor": true

&nbsp; },

&nbsp; "files.exclude": {

&nbsp;   "\*\*/.git": false,

&nbsp;   "\*\*/.cursor/mcp-cache": true

&nbsp; },

&nbsp; "files.watcherExclude": {

&nbsp;   "\*\*/node\_modules/\*\*": true,

&nbsp;   "\*\*/vendor/\*\*": true,

&nbsp;   "\*\*/.cursor/mcp-cache/\*\*": true,

&nbsp;   "\*\*/C:/temp/chrome-debug-mcp/\*\*": true

&nbsp; }

}

```



---



\## 3. Update .gitignore for MCP Artifacts



\*\*File:\*\* `.gitignore` (add to existing file)



```gitignore

\# MCP-specific artifacts

.cursor/mcp-cache/

.cursor/mcp-memory/

mcp-debug.log

chrome-debug-profile/



\# Chrome debugging

C:/temp/chrome-debug-mcp/



\# Windows-specific

Thumbs.db

Desktop.ini

$RECYCLE.BIN/

\*.lnk



\# IDE

.vscode/

.idea/

\*.code-workspace



\# Temporary files

\*.tmp

\*.temp

\*.log

.DS\_Store



\# npm/node

node\_modules/

npm-debug.log\*

.npm/

.npx/



\# PHP

vendor/

composer.lock

```



---



\## Part 1 Complete



\*\*Next:\*\* Proceed to Part 2 for automation scripts.



\*\*Files created/modified:\*\*

\- ✅ `~/.cursor/mcp.json` - Enhanced MCP configuration

\- ✅ `.vscode/settings.json` - Cursor IDE settings

\- ✅ `.gitignore` - MCP artifact exclusions


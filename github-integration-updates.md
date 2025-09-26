# GitHub Integration - AI Agent Guide

## Core Rules
1. **Git Bash only** for git operations
2. **"push to github"** exact token required for pushes
3. **GitHub MCP** for all repository operations via Cursor AI

## AI Agent Commands

### Status & Info
```
Ask Cursor AI: "Show git status and recent commits"
Ask Cursor AI: "Show repository information" 
Ask Cursor AI: "Show latest workflow runs"
```

### Local Operations
```
Ask Cursor AI: "Stage files for commit"
Ask Cursor AI: "Prepare commit for feature X"
Ask Cursor AI: "Preview push changes"
```

### Push Operations
```
Ask Cursor AI: "Push changes to github" (requires "push to github" token)
```

### Repository Management
```
Ask Cursor AI: "Create pull request for feature branch"
Ask Cursor AI: "Show open issues"
Ask Cursor AI: "Create issue for deployment failure"
```

## Decision Tree

**Need to push changes?**
- YES → Use "push to github" token + GitHub MCP
- NO → Use local GitHub MCP commands

**Need to create PR?**
- YES → Ask Cursor AI: "Create pull request"
- NO → Continue local development

**Need to check deployment?**
- YES → Ask Cursor AI: "Show latest workflow runs"
- NO → Use local operations

## MCP Integration
- **GitHub MCP**: Repository operations
- **Memory MCP**: Context and patterns
- **Filesystem MCP**: File operations
- **Chrome MCP**: Frontend testing

## Success Criteria
- All operations use GitHub MCP via Cursor AI
- Push operations require exact authorization token
- Memory MCP maintains context across sessions

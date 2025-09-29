# GitHub MCP Setup Guide

This guide explains how to set up GitHub MCP (Model Context Protocol) for enhanced repository operations.

## Overview

GitHub MCP provides AI agents with direct access to GitHub repositories for:
- Repository information and metadata
- Commit history and details
- File contents and directory structure
- Issues and pull request management
- Code search and analysis

## Setup Options

### Option 1: Public Repository (No Token Required)
- Make repository public temporarily
- All read operations work immediately
- No authentication setup needed
- Can switch back to private after testing

### Option 2: Private Repository with Token
- Set up GitHub Personal Access Token
- Full functionality including write operations
- More secure for production use

## Quick Start

1. **Test with Public Repository**:
   ```bash
   # Make repository public via GitHub web interface
   # Then test GitHub MCP operations
   ```

2. **Set up Token for Private Repository**:
   ```bash
   # Run the setup script
   ./scripts/setup-github-token.sh
   ```

## Available Operations

### Read Operations (Public Repos)
- `mcp_github_search_repositories` - Find repositories
- `mcp_github_list_commits` - Get commit history
- `mcp_github_get_file_contents` - Read file contents
- `mcp_github_list_issues` - List issues
- `mcp_github_list_pull_requests` - List pull requests

### Write Operations (Requires Token)
- `mcp_github_create_pull_request` - Create PRs
- `mcp_github_create_issue` - Create issues
- `mcp_github_search_code` - Search code across repos

## Security Notes

- Never commit actual GitHub tokens to the repository
- Use example files (`.example`) for configuration templates
- GitHub's push protection will block commits containing secrets
- Store tokens in environment variables or secure configuration files

## Troubleshooting

### Push Protection Errors
If you see "Repository rule violations found":
1. Remove any actual tokens from files
2. Use `.example` files for templates
3. Reset Git history if secrets were committed

### Authentication Errors
- Verify token has correct permissions
- Check token format (starts with `ghp_`, `gho_`, etc.)
- Ensure token is set in environment variables

## Best Practices

1. **Use Public Repos for Testing**: Quick way to verify MCP functionality
2. **Token Management**: Store tokens securely, never in code
3. **Example Files**: Provide templates without actual secrets
4. **Documentation**: Keep setup guides current and clear

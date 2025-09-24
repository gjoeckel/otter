#### Known Issues & Technical Debt

#### Critical Development Tool Issues (Cline Extension)
- **File Editing Reliability**: Severe "Diff Edit Mismatch" errors in Cline extension affecting productivity
  - `replace_in_file` tool fails consistently even with exact content matches
  - Line ending variations (LF vs CRLF) breaking diff matching
  - VSCode auto-formatting causing subsequent edit failures
  - Model-specific formatting issues (Claude 4 search block format errors)
- **Tool Call Format Failures**: "[ERROR] You did not use a tool in your previous response!" across multiple models
- **Gray Screen Bug**: Cline extension becomes unresponsive after 5+ minutes, requiring VS Code restarts
- **Performance Issues**: Infinite retry loops burning through API tokens due to failed operations
- **Cost Impact**: Failed operations causing excessive API usage and increased development costs

#### Project-Specific Issues
- **Reports Data Loading**: Systemwide Data tables (Registrations and Enrollments) are not loading, suspected race conditions
- **Session Warnings**: Non-critical session_start() warnings in some test contexts
- **Build Dependencies**: Node.js 20+ requirement for build system
- **File Permissions**: Manual cache directory permission setup in some environments

#### Cline-Specific Workarounds in Use
- **Alternative Editing Strategy**: Using `write_to_file` for complete rewrites when `replace_in_file` fails
- **Exact Content Matching**: Extra attention to whitespace, indentation, and line endings
- **Frequent Restarts**: Regular VS Code restarts to avoid Gray Screen Bug
- **Token Monitoring**: Close monitoring of API usage due to failed operation costs
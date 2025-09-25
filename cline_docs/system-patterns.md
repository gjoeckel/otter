## Development Environment & Tool Reliability Patterns

### AI Development Agent Considerations
- **Tool Reliability Challenges**: Development workflow affected by AI agent file editing failures
- **Diff Edit Mismatch Prevention**: Implement exact whitespace matching and consistent line endings
- **Error Recovery Strategies**: Graceful fallback from targeted edits to complete file rewrites
- **Cost Management**: Monitor API usage due to failed operations causing retry loops

### File Editing Reliability Patterns
```php
// File operation with fallback strategy
function updateFileContent($filepath, $searchContent, $replaceContent) {
    // Attempt targeted edit first
    if (!diffEditReplace($filepath, $searchContent, $replaceContent)) {
        // Fallback to complete rewrite
        return completeFileRewrite($filepath, $newContent);
    }
    return true;
}
```

### Development Workflow Adaptations
- **Frequent Checkpoints**: Regular commits due to potential tool failures
- **Validation Steps**: Post-edit verification of file integrity
- **Backup Strategies**: Maintain working state backups during complex edits
- **Tool Monitoring**: Track development tool performance and stability

## Error Handling Patterns
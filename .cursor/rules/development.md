---
alwaysApply: false
description: "Development workflow and best practices"
---

# Development Workflow Rules

## Development Environment Setup

### Local Development
**Primary Command:** `.\mvp-local.ps1`

**What it does:**
- Starts PHP server on port 8000
- Builds JavaScript bundle
- Performs health checks
- Sets up MVP environment

**Alternative Commands:**
```powershell
# Individual components
.\scripts\start-mvp-testing.ps1
.\mvp-local-function.ps1
.\mvp-local.cmd
```

### JavaScript Bundle Management
```bash
# Production build
npm run build:mvp

# Development build with watch
npm run dev:mvp

# Standard build (legacy)
npm run build:reports
npm run dev:reports
```

**Bundle Output:** `reports/dist/reports.bundle.js`

## Git Workflow

### Branch Strategy
- **Feature Branches:** `mvp`, `investigate-broken-functionality`, etc.
- **Main Branch:** `main` (production-ready code)
- **Development Branch:** `develop` (integration branch)

### Commit Strategy
- **Roll-up Commits:** Single commit with descriptive summary
- **Commit Message Format:** Descriptive one-line summary
- **Push Permission:** Use "push to github" token for remote pushes

### Example Workflow
```bash
# Create feature branch
git checkout -b feature/new-feature

# Make changes and stage
git add .

# Commit with descriptive message
git commit -m "Feature: Add new functionality with comprehensive testing"

# Push to remote (requires "push to github" token)
# Use "push to github" command to trigger push
```

## Code Standards

### PHP Standards
- **PSR Compliance:** Follow PSR-1, PSR-2, PSR-4 standards
- **Type Hints:** Use proper type hints for parameters and return types
- **Error Handling:** Include proper try-catch blocks and error handling
- **Documentation:** Use PHPDoc comments for classes and methods
- **JSON Storage:** Use JSON file storage (passwords.json, cache files) - no MySQL

**Example:**
```php
/**
 * Process enterprise data with validation
 * @param string $enterpriseCode The enterprise code
 * @param array $data The data to process
 * @return array Processed data
 * @throws InvalidArgumentException If enterprise code is invalid
 */
public function processEnterpriseData(string $enterpriseCode, array $data): array {
    try {
        // Implementation
        return $processedData;
    } catch (Exception $e) {
        throw new InvalidArgumentException("Invalid enterprise code: $enterpriseCode");
    }
}
```

### JavaScript Standards
- **ES6+ Modules:** Use modern module syntax
- **Import/Export:** Use named imports and exports
- **Async/Await:** Use async/await instead of callbacks
- **Error Handling:** Include proper error handling

**Example:**
```javascript
// Import statements
import { MvpReportsDataService } from './unified-data-service.js';
import { MvpUnifiedTableUpdater } from './unified-table-updater.js';

// Async function with error handling
async function fetchAndUpdateData(start, end, enrollmentMode) {
    try {
        const data = await reportsDataService.fetchAllData(start, end, enrollmentMode);
        await tableUpdater.updateAllTables(data);
        return data;
    } catch (error) {
        console.error('Error fetching data:', error);
        throw error;
    }
}

// Export function
export { fetchAndUpdateData };
```

### CSS Standards
- **Enterprise-Specific Styling:** Use enterprise-specific CSS classes
- **Avoid Inline Styles:** Use external stylesheets
- **Responsive Design:** Ensure mobile compatibility
- **Consistent Naming:** Use consistent class naming conventions

## Testing Workflow

### Test Execution Order
1. **Unit Tests:** Test individual components
2. **Integration Tests:** Test component interactions
3. **Chrome MCP Tests:** Test frontend functionality
4. **Performance Tests:** Validate performance metrics
5. **E2E Tests:** Test complete user journeys

### Test Commands
```bash
# Run all tests
php tests/run_comprehensive_tests.php

# Run specific test categories
php tests/unit/config_test.php
php tests/integration/reports_tables_validation_test.php
php tests/chrome-mcp/run_chrome_mcp_tests.php
php tests/performance/mvp_bundle_performance_test.php
php tests/e2e/mvp_user_journey_test.php
php tests/mvp/mvp_reports_validation_test.php

# Run with specific enterprise
php tests/run_comprehensive_tests.php csu
```

### Test-Driven Development
1. **Write Tests First:** Create tests before implementing functionality
2. **Run Tests:** Execute tests to verify they fail
3. **Implement Code:** Write code to make tests pass
4. **Refactor:** Improve code while keeping tests green
5. **Repeat:** Continue the cycle for new features

## Documentation Workflow

### Changelog Management
**File:** `changelog-mvp.md`

**Format:**
```markdown
## [Version] - Date

### Added
- New features and functionality

### Changed
- Changes to existing functionality

### Fixed
- Bug fixes and corrections

### Removed
- Removed features or functionality
```

### Documentation Updates
- **Update README:** Keep project overview current
- **Update Architecture Docs:** Document system changes
- **Update API Docs:** Document API changes
- **Update Test Docs:** Document testing changes

## Deployment Workflow

### Pre-Deployment Checklist
1. **Run All Tests:** Ensure all tests pass
2. **Build Bundle:** Create production JavaScript bundle
3. **Validate Configuration:** Check enterprise configurations
4. **Update Documentation:** Ensure docs are current
5. **Commit Changes:** Commit with descriptive message

### Deployment Steps
1. **Switch to Main Branch:** `git checkout main`
2. **Merge Feature Branch:** `git merge feature-branch`
3. **Run Final Tests:** Execute comprehensive test suite
4. **Build Production Bundle:** `npm run build:mvp`
5. **Deploy to Production:** Follow deployment procedures

## Troubleshooting Workflow

### Common Issues and Solutions

#### 1. Bundle Not Found
**Problem:** `dist/reports.bundle.js not found`
**Solution:** Run `npm run build:mvp`

#### 2. Session Issues
**Problem:** Session not persisting or authentication failing
**Solution:** Check enterprise configuration and admin credentials

#### 3. Date Range Picker Broken
**Problem:** Date picker not populating fields
**Solution:** Verify `date-range-picker.js` is imported in bundle

#### 4. Apply Button Not Working
**Problem:** Apply button not triggering updates
**Solution:** Check `window.handleApplyClick` function exists

#### 5. Chrome MCP Not Available
**Problem:** Chrome MCP tools not accessible
**Solution:** Restart Cursor and verify MCP configuration

### Debug Commands
```bash
# Check bundle exists
ls reports/dist/reports.bundle.js

# Test enterprise configuration
php tests/test_all_enterprises.php

# Validate MVP system
php tests/mvp/mvp_reports_validation_test.php

# Check Chrome MCP status
cd browsertools-mcp && node server-simple.js
```

## Code Review Workflow

### Review Checklist
1. **Code Standards:** Follows established coding standards
2. **Testing:** Includes appropriate tests
3. **Documentation:** Updated documentation
4. **Performance:** No performance regressions
5. **Security:** No security vulnerabilities
6. **Compatibility:** Works across all enterprises

### Review Process
1. **Self Review:** Review your own code before submitting
2. **Peer Review:** Have another developer review the code
3. **Test Review:** Ensure all tests pass
4. **Documentation Review:** Verify documentation is updated
5. **Final Approval:** Get final approval before merging

## Performance Monitoring

### Bundle Size Monitoring
- **Target Size:** < 50KB for MVP bundle
- **Current Size:** ~22.9KB (within target)
- **Monitoring:** Track size changes over time

### Performance Metrics
- **API Response Time:** < 5000ms
- **Table Update Time:** < 3000ms
- **UI Interaction Time:** < 500ms
- **Memory Usage:** < 100MB

### Performance Testing
```bash
# Run performance tests
php tests/performance/mvp_bundle_performance_test.php

# Monitor performance metrics
php tests/chrome-mcp/run_chrome_mcp_tests.php
```

## Security Workflow

### Security Best Practices
1. **Input Validation:** Validate all user inputs
2. **SQL Injection Prevention:** Use prepared statements
3. **XSS Prevention:** Sanitize output data
4. **CSRF Protection:** Implement CSRF tokens
5. **Session Security:** Secure session management

### Security Testing
- **Input Validation Testing:** Test with malicious inputs
- **Authentication Testing:** Verify authentication mechanisms
- **Authorization Testing:** Check access controls
- **Session Testing:** Validate session security

## Maintenance Workflow

### Regular Maintenance Tasks
1. **Update Dependencies:** Keep npm packages current
2. **Security Updates:** Apply security patches
3. **Performance Monitoring:** Track performance metrics
4. **Test Maintenance:** Keep tests current and relevant
5. **Documentation Updates:** Maintain current documentation

### Maintenance Schedule
- **Weekly:** Run comprehensive test suite
- **Monthly:** Update dependencies and security patches
- **Quarterly:** Performance review and optimization
- **Annually:** Architecture review and updates

## Development Best Practices

### 1. Start Fresh
- **New Chat for New Tasks:** Begin new chat for distinct tasks
- **Review Context:** Always review essential context files
- **Verify Environment:** Ensure development environment is ready

### 2. Follow Conventions
- **Use Established Patterns:** Follow existing code patterns
- **Maintain Consistency:** Keep code style consistent
- **Document Changes:** Update documentation with changes

### 3. Test Everything
- **Write Tests:** Create tests for new functionality
- **Run Tests:** Execute tests after changes
- **Validate Integration:** Ensure components work together

### 4. Monitor Performance
- **Track Metrics:** Monitor performance metrics
- **Optimize Code:** Optimize for performance
- **Profile Applications:** Use profiling tools

### 5. Maintain Security
- **Validate Inputs:** Always validate user inputs
- **Secure APIs:** Implement proper API security
- **Protect Data:** Ensure data protection

### 6. Document Changes
- **Update Changelog:** Document all changes
- **Update Documentation:** Keep docs current
- **Version Control:** Use proper version control

### 7. Collaborate Effectively
- **Code Reviews:** Participate in code reviews
- **Share Knowledge:** Share knowledge with team
- **Communicate Changes:** Communicate changes clearly

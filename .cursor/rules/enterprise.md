---
alwaysApply: false
description: "Enterprise configuration and management rules"
---

# Enterprise Configuration Rules

## Enterprise Overview

The Otter project supports multiple enterprises with unified configuration management:

- **CSU:** California State University system
- **CCC:** California Community Colleges  
- **DEMO:** Demo/testing environment

## Configuration Files

### Enterprise Config Structure
```
config/
├── csu.config          # CSU-specific configuration
├── ccc.config          # CCC-specific configuration
├── demo.config         # Demo environment configuration
└── groups/             # Enterprise group configurations
    ├── csu_groups.json
    └── ccc_groups.json
```

### Configuration Format
```json
{
  "enterprise": {
    "code": "csu",
    "name": "California State University",
    "description": "CSU system configuration"
  },
  "organizations": [
    {
      "id": "admin",
      "name": "Admin Organization",
      "password": "admin_password",
      "is_admin": true
    }
  ],
  "settings": {
    "start_date": "08-06-22",
    "cache_ttl": 3600,
    "timezone": "America/Los_Angeles"
  }
}
```

## UnifiedEnterpriseConfig Class

### Core Methods
```php
class UnifiedEnterpriseConfig {
    // Initialization
    public static function init($enterprise_code);
    
    // Enterprise information
    public static function getEnterprise();
    public static function getEnterpriseCode();
    public static function getEnterpriseName();
    
    // Organization management
    public static function getOrganizations();
    public static function getAdminOrganization();
    public static function isValidOrganizationPassword($password);
    
    // Settings access
    public static function getSettings();
    public static function getStartDate();
    public static function getCacheTtl();
    
    // URL generation
    public static function generateUrl($path, $type);
    public static function getBaseUrl();
}
```

## Enterprise-Specific Rules

### 1. CSU (California State University)
**Config File:** `config/csu.config`

**Key Characteristics:**
- **Start Date:** 08-06-22
- **Organizations:** Multiple CSU campuses
- **Admin Access:** Centralized admin organization
- **Data Range:** Full academic year data

**Usage:**
```php
TestBase::initEnterprise('csu');
$config = UnifiedEnterpriseConfig::getEnterprise();
$startDate = UnifiedEnterpriseConfig::getStartDate(); // "08-06-22"
```

### 2. CCC (California Community Colleges)
**Config File:** `config/ccc.config`

**Key Characteristics:**
- **Start Date:** 08-06-22
- **Organizations:** Multiple community colleges
- **Admin Access:** District-level administration
- **Data Range:** Full academic year data

**Usage:**
```php
TestBase::initEnterprise('ccc');
$config = UnifiedEnterpriseConfig::getEnterprise();
$startDate = UnifiedEnterpriseConfig::getStartDate(); // "08-06-22"
```

### 3. DEMO (Demo Environment)
**Config File:** `config/demo.config`

**Key Characteristics:**
- **Start Date:** 01-01-20
- **Organizations:** Demo organizations
- **Admin Access:** Demo admin credentials
- **Data Range:** Sample data for testing

**Usage:**
```php
TestBase::initEnterprise('demo');
$config = UnifiedEnterpriseConfig::getEnterprise();
$startDate = UnifiedEnterpriseConfig::getStartDate(); // "01-01-20"
```

## Enterprise Integration Patterns

### 1. Initialization Pattern
```php
// Initialize enterprise configuration
TestBase::initEnterprise('csu');

// Get enterprise information
$enterprise = UnifiedEnterpriseConfig::getEnterprise();
$enterpriseCode = UnifiedEnterpriseConfig::getEnterpriseCode();
$enterpriseName = UnifiedEnterpriseConfig::getEnterpriseName();
```

### 2. Organization Management Pattern
```php
// Get all organizations
$organizations = UnifiedEnterpriseConfig::getOrganizations();

// Get admin organization
$adminOrg = UnifiedEnterpriseConfig::getAdminOrganization();

// Validate organization password
$isValid = UnifiedEnterpriseConfig::isValidOrganizationPassword($password);
```

### 3. Settings Access Pattern
```php
// Get all settings
$settings = UnifiedEnterpriseConfig::getSettings();

// Get specific settings
$startDate = UnifiedEnterpriseConfig::getStartDate();
$cacheTtl = UnifiedEnterpriseConfig::getCacheTtl();
```

### 4. URL Generation Pattern
```php
// Generate enterprise-specific URLs
$dashboardUrl = UnifiedEnterpriseConfig::generateUrl('', 'dashboard');
$reportsUrl = UnifiedEnterpriseConfig::generateUrl('reports', 'index');
$adminUrl = UnifiedEnterpriseConfig::generateUrl('admin', 'index');
```

## Enterprise Testing

### Configuration Validation
**File:** `tests/test_all_enterprises.php`

**Purpose:** Validate all enterprise configurations

**Tests:**
- Enterprise configuration loading
- Organization validation
- Admin organization verification
- Settings validation
- URL generation testing

### Enterprise-Specific Tests
**File:** `tests/enterprise/csu_test.php`

**Purpose:** CSU-specific functionality testing

**Tests:**
- CSU configuration validation
- CSU-specific data handling
- CSU organization management
- CSU URL generation

## Enterprise Data Handling

### Start Date Integration
```javascript
// Pass enterprise start date to JavaScript
window.ENTERPRISE_START_DATE = '<?php echo UnifiedEnterpriseConfig::getStartDate(); ?>';

// Use in date range picker
if (window.ENTERPRISE_START_DATE) {
    startInput.value = window.ENTERPRISE_START_DATE;
    endInput.value = getTodayMMDDYY();
}
```

### Organization Authentication
```php
// Validate organization credentials
$isValid = UnifiedEnterpriseConfig::isValidOrganizationPassword($password);

if ($isValid) {
    // Set session data
    $_SESSION['admin_authenticated'] = true;
    $_SESSION['enterprise_code'] = UnifiedEnterpriseConfig::getEnterpriseCode();
}
```

### Cache Management
```php
// Get cache TTL for enterprise
$cacheTtl = UnifiedEnterpriseConfig::getCacheTtl();

// Set cache headers
header("Cache-Control: public, max-age=" . $cacheTtl);
```

## Enterprise Troubleshooting

### Common Issues
1. **Configuration not loading** - Check config file exists and is valid JSON
2. **Organization authentication failing** - Verify password in config file
3. **Start date not available** - Check start_date setting in config
4. **URL generation failing** - Verify enterprise code is correct
5. **Session not persisting** - Check enterprise code in session

### Debug Commands
```bash
# Test all enterprise configurations
php tests/test_all_enterprises.php

# Test specific enterprise
php tests/enterprise/csu_test.php

# Validate configuration files
php -l config/csu.config
php -l config/ccc.config
php -l config/demo.config
```

### Configuration Validation
```php
// Validate enterprise configuration
try {
    TestBase::initEnterprise('csu');
    $config = UnifiedEnterpriseConfig::getEnterprise();
    
    // Check required fields
    TestBase::assertNotEmpty($config['code'], 'Enterprise code should not be empty');
    TestBase::assertNotEmpty($config['name'], 'Enterprise name should not be empty');
    
    // Check organizations
    $organizations = UnifiedEnterpriseConfig::getOrganizations();
    TestBase::assertGreaterThan(0, count($organizations), 'Should have at least one organization');
    
    // Check admin organization
    $adminOrg = UnifiedEnterpriseConfig::getAdminOrganization();
    TestBase::assertNotNull($adminOrg, 'Admin organization should exist');
    TestBase::assertTrue($adminOrg['is_admin'], 'Admin organization should have is_admin flag');
    
} catch (Exception $e) {
    echo "Configuration validation failed: " . $e->getMessage();
}
```

## Enterprise Best Practices

### 1. Configuration Management
- **Use consistent structure** - Follow established config format
- **Validate configurations** - Test all enterprise configs
- **Handle missing data** - Provide fallbacks for missing settings
- **Secure credentials** - Protect admin passwords and sensitive data

### 2. Testing
- **Test all enterprises** - Ensure compatibility across all enterprises
- **Validate configurations** - Test configuration loading and validation
- **Test enterprise-specific features** - Verify enterprise-specific functionality
- **Handle enterprise differences** - Account for variations between enterprises

### 3. Development
- **Use enterprise-aware code** - Always consider enterprise context
- **Handle enterprise switching** - Support switching between enterprises
- **Maintain enterprise isolation** - Keep enterprise data separate
- **Use unified interfaces** - Provide consistent APIs across enterprises

### 4. Deployment
- **Environment-specific configs** - Use appropriate configs for each environment
- **Secure configuration files** - Protect configuration files in production
- **Validate on startup** - Check configuration validity on application startup
- **Monitor configuration changes** - Track and log configuration modifications

## Enterprise Integration Examples

### Reports Page Integration
```php
// In reports/index.php
$enterpriseCode = UnifiedEnterpriseConfig::getEnterpriseCode();
$startDate = UnifiedEnterpriseConfig::getStartDate();

// Pass to JavaScript
echo "window.ENTERPRISE_CODE = '$enterpriseCode';";
echo "window.ENTERPRISE_START_DATE = '$startDate';";
```

### API Integration
```php
// In API endpoints
$enterprise = UnifiedEnterpriseConfig::getEnterprise();
$startDate = UnifiedEnterpriseConfig::getStartDate();

// Filter data by enterprise start date
$query = "SELECT * FROM data WHERE date >= '$startDate'";
```

### Admin Interface Integration
```php
// In admin interface
$organizations = UnifiedEnterpriseConfig::getOrganizations();
$adminOrg = UnifiedEnterpriseConfig::getAdminOrganization();

// Display enterprise-specific information
echo "Enterprise: " . UnifiedEnterpriseConfig::getEnterpriseName();
echo "Organizations: " . count($organizations);
```

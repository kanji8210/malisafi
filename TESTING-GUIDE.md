# Malisafi MLS - Testing Guide

**Version:** 1.0  
**Last Updated:** January 2026  
**Test Coverage Goal:** 80%+

---

## 📋 Table of Contents

1. [Overview](#overview)
2. [Getting Started](#getting-started)
3. [Running Tests](#running-tests)
4. [Writing Tests](#writing-tests)
5. [Test Structure](#test-structure)
6. [CI/CD Integration](#cicd-integration)
7. [Best Practices](#best-practices)
8. [Troubleshooting](#troubleshooting)

---

## 🎯 Overview

This guide covers automated testing for the Malisafi MLS plugin using PHPUnit and WordPress test framework.

### Test Types

- **Unit Tests** - Test individual functions and classes in isolation
- **Integration Tests** - Test how components work together
- **Security Tests** - Verify security measures are working

### Technologies

- **PHPUnit 9.5** - Testing framework
- **WordPress Test Library** - WordPress-specific test helpers
- **Mockery** - Mocking framework for dependencies
- **GitHub Actions** - CI/CD automation

---

## 🚀 Getting Started

### Prerequisites

- PHP 7.2+ (8.1 recommended)
- Composer
- MySQL/MariaDB
- Git
- WordPress development environment

### Installation

#### 1. Install Composer Dependencies

```bash
cd /path/to/malisafi-mls
composer install
```

This installs:
- `phpunit/phpunit` - Testing framework
- `wp-phpunit/wp-phpunit` - WordPress test library
- `mockery/mockery` - Mocking framework
- `phpstan/phpstan` - Static analysis
- `squizlabs/php_codesniffer` - Code style checker

#### 2. Install WordPress Test Suite

```bash
# Linux/Mac
bash bin/install-wp-tests.sh wordpress_test root '' localhost latest

# Windows (PowerShell)
# You may need to run this in WSL or Git Bash
```

**Parameters:**
- `wordpress_test` - Test database name
- `root` - Database user
- `''` - Database password (empty)
- `localhost` - Database host
- `latest` - WordPress version

#### 3. Configure Test Environment

Create a `.env.testing` file (optional):

```bash
WP_TESTS_DIR=/tmp/wordpress-tests-lib
WP_CORE_DIR=/tmp/wordpress
DB_NAME=wordpress_test
DB_USER=root
DB_PASS=
DB_HOST=localhost
```

---

## 🧪 Running Tests

### Run All Tests

```bash
composer test
```

### Run Specific Test Suites

```bash
# Unit tests only
composer test:unit

# Integration tests only
composer test:integration
```

### Run Specific Test File

```bash
vendor/bin/phpunit tests/unit/ValidatorTest.php
```

### Run Specific Test Method

```bash
vendor/bin/phpunit --filter test_email_validation_valid tests/unit/ValidatorTest.php
```

### Generate Code Coverage Report

```bash
composer test:coverage
```

Coverage report will be in `tests/coverage/index.html`

### Run Code Quality Checks

```bash
# Check code style
composer phpcs

# Fix code style issues
composer phpcs:fix

# Run static analysis
composer phpstan
```

---

## ✍️ Writing Tests

### Basic Test Structure

```php
<?php
namespace MalisafiMLS\Tests\Unit;

use MalisafiMLS\Validator;
use WP_UnitTestCase;

class ValidatorTest extends WP_UnitTestCase {
    
    public function setUp(): void {
        parent::setUp();
        // Setup code before each test
    }
    
    public function tearDown(): void {
        // Cleanup code after each test
        parent::tearDown();
    }
    
    public function test_something() {
        // Arrange
        $validator = new Validator();
        
        // Act
        $result = $validator->email('test@example.com');
        
        // Assert
        $this->assertTrue($result);
    }
}
```

### Using Test Helpers

```php
use MalisafiMLS\Tests\Helpers\TestCase;
use MalisafiMLS\Tests\Helpers\PropertyFactory;
use MalisafiMLS\Tests\Helpers\UserFactory;

class MyTest extends TestCase {
    
    public function test_with_helpers() {
        // Create authenticated user
        $user_id = $this->actingAs('administrator');
        
        // Create test property
        $property_id = $this->createProperty([
            'post_title' => 'Custom Property'
        ]);
        
        // Create agent
        $agent_id = $this->createAgent([
            'user_login' => 'testagent'
        ]);
        
        // Assertions
        $this->assertPropertyMeta($property_id, '_malisafi_price', 1500000);
        $this->assertUserMeta($agent_id, 'agency_name', 'Test Agency');
    }
}
```

### Testing AJAX Endpoints

```php
public function test_ajax_endpoint() {
    // Arrange
    $user_id = $this->actingAs('malisafi_agent');
    $nonce = wp_create_nonce('malisafi_action');
    
    // Act
    $response = $this->mockAjaxRequest('malisafi_action', [
        'nonce' => $nonce,
        'property_id' => 123
    ]);
    
    // Assert
    $this->assertJsonSuccess($response);
    
    $data = json_decode($response, true);
    $this->assertArrayHasKey('data', $data);
}
```

### Testing Database Operations

```php
public function test_database_operation() {
    global $wpdb;
    $user_id = $this->factory->user->create();
    
    // Insert subscription
    $table = $wpdb->prefix . 'mf_subscriptions';
    $wpdb->insert($table, [
        'user_id' => $user_id,
        'plan_type' => 'agent_premium',
        'status' => 'active'
    ]);
    
    // Assert
    $this->assertDatabaseHas('mf_subscriptions', [
        'user_id' => $user_id,
        'status' => 'active'
    ]);
}
```

### Mocking External Services

```php
use Mockery;

public function test_with_mock() {
    // Mock Stripe API
    $mock = Mockery::mock('Stripe\Subscription');
    $mock->shouldReceive('retrieve')
         ->once()
         ->andReturn((object)[
             'id' => 'sub_123',
             'status' => 'active'
         ]);
    
    // Your test code
}

public function tearDown(): void {
    Mockery::close();
    parent::tearDown();
}
```

---

## 📁 Test Structure

```
tests/
├── bootstrap.php              # PHPUnit bootstrap
├── unit/                      # Unit tests
│   ├── ValidatorTest.php
│   ├── CacheManagerTest.php
│   └── DatabaseTest.php
├── integration/               # Integration tests
│   ├── StripeIntegrationTest.php
│   ├── PropertySubmissionTest.php
│   └── UserRegistrationTest.php
├── Helpers/                   # Test helpers
│   ├── TestCase.php          # Base test case
│   ├── PropertyFactory.php   # Property factory
│   └── UserFactory.php       # User factory
├── coverage/                  # Coverage reports (generated)
└── logs/                      # Test logs (generated)
```

---

## 🔄 CI/CD Integration

### GitHub Actions Workflow

Tests run automatically on:
- Push to `main` or `develop` branches
- Pull requests to `main` or `develop`
- Manual workflow dispatch

#### Workflow Features

1. **Matrix Testing**
   - PHP versions: 7.4, 8.0, 8.1
   - WordPress versions: 6.0, 6.3, latest

2. **Code Quality Checks**
   - PHP CodeSniffer (PHPCS)
   - PHPStan static analysis

3. **Security Checks**
   - Composer security audit

4. **Code Coverage**
   - Generated for PHP 8.1 + WordPress latest
   - Uploaded to Codecov

### Viewing Test Results

1. Go to **Actions** tab in GitHub repository
2. Click on latest workflow run
3. View job results and logs

### Adding Status Badge

Add to README.md:

```markdown
![Tests](https://github.com/malisafi/mls-plugin/workflows/Run%20Tests/badge.svg)
```

---

## ✅ Best Practices

### 1. Test Naming

```php
// ✅ GOOD - Descriptive, clear intent
public function test_email_validation_rejects_invalid_format()

// ❌ BAD - Vague, unclear
public function test_validation()
```

### 2. Arrange-Act-Assert Pattern

```php
public function test_property_creation() {
    // Arrange - Set up test data
    $user_id = $this->factory->user->create();
    $data = ['post_title' => 'Test Property'];
    
    // Act - Execute the action
    $property_id = wp_insert_post($data);
    
    // Assert - Verify the result
    $this->assertGreaterThan(0, $property_id);
}
```

### 3. Test One Thing

```php
// ✅ GOOD - Tests one specific behavior
public function test_validator_rejects_empty_email() {
    $validator = new Validator();
    $result = $validator->email('', 'email', true);
    $this->assertFalse($result);
}

// ❌ BAD - Tests multiple things
public function test_validator() {
    // Tests email, phone, password all in one test
}
```

### 4. Use Data Providers

```php
/**
 * @dataProvider invalidEmailProvider
 */
public function test_rejects_invalid_emails($email) {
    $validator = new Validator();
    $this->assertFalse($validator->email($email));
}

public function invalidEmailProvider() {
    return [
        ['not-an-email'],
        ['@example.com'],
        ['user@'],
        [''],
    ];
}
```

### 5. Clean Up After Tests

```php
public function tearDown(): void {
    // Clear caches
    Cache_Manager::clear_all();
    
    // Reset global state
    wp_set_current_user(0);
    
    parent::tearDown();
}
```

### 6. Mock External Dependencies

```php
// Don't make real API calls in tests
// Use mocks or test mode

public function test_stripe_checkout() {
    // Use test Stripe keys
    update_option('malisafi_stripe_mode', 'test');
    
    // Or mock the Stripe API
    $mock = Mockery::mock('Stripe\Checkout\Session');
    // ...
}
```

---

## 🐛 Troubleshooting

### Issue: "Could not find WordPress test suite"

**Solution:**
```bash
# Reinstall WordPress test suite
bash bin/install-wp-tests.sh wordpress_test root '' localhost latest
```

### Issue: Database connection failed

**Solution:**
```bash
# Check MySQL is running
sudo service mysql status

# Verify database exists
mysql -u root -p -e "SHOW DATABASES LIKE 'wordpress_test';"

# Recreate if needed
mysql -u root -p -e "DROP DATABASE IF EXISTS wordpress_test; CREATE DATABASE wordpress_test;"
```

### Issue: "Class not found"

**Solution:**
```bash
# Regenerate autoload files
composer dump-autoload

# Verify bootstrap.php is correct
cat tests/bootstrap.php
```

### Issue: Tests are slow

**Optimization:**
```bash
# Run specific test suite
composer test:unit

# Run tests in parallel (requires paratest)
composer require --dev brianium/paratest
vendor/bin/paratest --processes 4
```

### Issue: Permission denied on bin/install-wp-tests.sh

**Solution:**
```bash
chmod +x bin/install-wp-tests.sh
```

### Issue: Windows line ending issues

**Solution:**
```bash
# Convert to Unix line endings
dos2unix bin/install-wp-tests.sh

# Or use Git Bash / WSL on Windows
```

---

## 📊 Test Coverage Goals

| Component | Current | Target | Priority |
|-----------|---------|--------|----------|
| Validator | 95% | 100% | ✅ High |
| Cache Manager | 90% | 95% | ✅ High |
| Database | 70% | 85% | 🟡 Medium |
| Stripe Integration | 60% | 80% | 🟡 Medium |
| AJAX Handlers | 50% | 75% | 🟡 Medium |
| Shortcodes | 40% | 70% | 🟢 Low |

---

## 🎓 Learning Resources

### PHPUnit Documentation
- [PHPUnit Manual](https://phpunit.de/documentation.html)
- [Assertions Reference](https://phpunit.de/manual/current/en/appendixes.assertions.html)

### WordPress Testing
- [WordPress Plugin Handbook - Testing](https://developer.wordpress.org/plugins/testing/)
- [WP_UnitTestCase Reference](https://make.wordpress.org/core/handbook/testing/)

### Testing Best Practices
- [Test-Driven Development (TDD)](https://en.wikipedia.org/wiki/Test-driven_development)
- [Writing Testable Code](https://testing.googleblog.com/2008/08/by-miko-hevery-so-you-decided-to.html)

---

## 📝 Checklist for New Features

When adding new features, ensure:

- [ ] Unit tests written for all new functions
- [ ] Integration tests for feature workflows
- [ ] Edge cases and error conditions tested
- [ ] Security validations tested
- [ ] Code coverage maintained above 80%
- [ ] Tests pass in CI/CD pipeline
- [ ] Documentation updated

---

## 🤝 Contributing Tests

### Pull Request Checklist

- [ ] All tests pass locally
- [ ] New tests added for new functionality
- [ ] Test names are descriptive
- [ ] Code follows WordPress coding standards
- [ ] No commented-out test code
- [ ] Coverage report shows improvement

---

## 📞 Support

### Need Help?

- **Testing Issues:** Create an issue on GitHub
- **CI/CD Questions:** Check GitHub Actions logs
- **General Questions:** Contact dev@malisafi.com

---

## 🎉 Quick Commands Reference

```bash
# Install dependencies
composer install

# Run all tests
composer test

# Run unit tests
composer test:unit

# Run integration tests
composer test:integration

# Generate coverage
composer test:coverage

# Check code style
composer phpcs

# Fix code style
composer phpcs:fix

# Static analysis
composer phpstan

# Security check
composer audit
```

---

**Last Updated:** January 9, 2026  
**Maintained By:** Malisafi Development Team  
**Next Review:** April 9, 2026

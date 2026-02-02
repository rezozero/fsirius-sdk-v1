# Upgrade FSiriusSdk

## Upgrading from 4.x to 5.x

### ⚠️ Breaking Changes

#### 1. PHP 8.3 minimum required

The minimum PHP version is now **8.3**. Update your environment accordingly.

#### 2. Symfony 7.4 required

This SDK now requires Symfony 7.4.* components:

```bash
composer require symfony/security-http:^7.4 symfony/http-client:^7.4
```

#### 3. PHP 8.3 typed constants

All class constants now use PHP 8.3 typed constant syntax. If you extend any classes from this SDK, make sure your code is compatible with typed constants.

**Example:**
```php
// Constants are now typed
public const string BASE_ROLE = 'ROLE_FORUMSIRIUS_USER';
public const string PRO_ROLE = 'ROLE_FORUMSIRIUS_PRO_USER';
```

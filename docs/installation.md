# Installation Guide

This guide will walk you through installing and setting up the Laravel ARES package in your Laravel application.

## Requirements

Before installing, ensure your system meets the following requirements:

- **PHP**: 8.2 or higher
- **Laravel**: 10.0, 11.0, 12.0, or 13.0
- **Composer**: Latest stable version
- **Guzzle**: 7.0 or higher (usually included with Laravel)

## Installation

### 1. Install via Composer

Install the package using Composer:

```bash
composer require nyoncode/laravel-ares
```

### 2. Publish Configuration (Optional)

Publish the configuration file to customize the package settings:

```bash
php artisan vendor:publish --tag="ares-config"
```

This will create a `config/ares.php` file in your application.

### 3. Register Service Provider

The package uses Laravel's package discovery, so the service provider is automatically registered. If you're not using package discovery, add it manually to your `config/app.php`:

```php
'providers' => [
    // ... other providers
    NyonCode\Ares\Providers\AresServiceProvider::class,
],
```

### 4. Register Facade (Optional)

If you want to use the Ares facade, add it to your `config/app.php` aliases:

```php
'aliases' => [
    // ... other aliases
    'Ares' => NyonCode\Ares\Facades\Ares::class,
],
```

## Configuration

After publishing the configuration file, you can customize the settings in `config/ares.php`:

```php
<?php

return [
    'api_url' => env('ARES_API_URL', 'https://ares.gov.cz/ekonomicke-subjekty-v-be/rest'),
    'cache_ttl' => env('ARES_CACHE_TTL', 86400), // 24 hours
    'log_channel' => env('ARES_LOG_CHANNEL', 'stack'),
    'http_options' => [
        'timeout' => env('ARES_HTTP_TIMEOUT', 5.0),
        'connect_timeout' => env('ARES_HTTP_CONNECT_TIMEOUT', 3.0),
    ],
];
```

### Environment Variables

Add the following environment variables to your `.env` file:

```env
# ARES Configuration
ARES_API_URL=https://ares.gov.cz/ekonomicke-subjekty-v-be/rest
ARES_CACHE_TTL=86400
ARES_LOG_CHANNEL=stack
ARES_HTTP_TIMEOUT=5.0
ARES_HTTP_CONNECT_TIMEOUT=3.0
```

## Verification

### 1. Test the Installation

You can test the installation using the provided Artisan command:

```bash
php artisan ares:test 12345678
```

Replace `12345678` with a valid Czech company identification number.

### 2. Check Helper Functions

The global helper functions should be available after installation:

```php
// In any Laravel route, controller, or service:
if (function_exists('ares')) {
    echo "ARES helper is available!";
}
```

### 3. Verify Service Container

Check if the service is properly registered in the Laravel container:

```php
// In a route or controller:
$ares = app(\NyonCode\Ares\Contracts\AresClientInterface::class);
// Should return an instance of AresClient
```

## Troubleshooting

### Common Issues

#### 1. Helper Functions Not Available

If the global helper functions are not available, run:

```bash
composer dump-autoload
```

This ensures the helper files are properly autoloaded.

#### 2. Configuration Not Found

If you get configuration errors, make sure to publish the config file:

```bash
php artisan vendor:publish --tag="ares-config"
```

#### 3. Cache Issues

If you're experiencing caching problems, clear your application cache:

```bash
php artisan cache:clear
php artisan config:clear
```

#### 4. HTTP Connection Issues

If you're experiencing connection timeouts, adjust the HTTP timeout settings in your `.env` file:

```env
ARES_HTTP_TIMEOUT=10.0
ARES_HTTP_CONNECT_TIMEOUT=5.0
```

### Debug Mode

To enable debug mode for troubleshooting, you can temporarily modify your configuration:

```php
'log_channel' => 'stack', // Use verbose logging
'cache_ttl' => 60, // Short cache for testing
```

## Upgrade Guide

### From Previous Versions

When upgrading to a new version, always:

1. **Backup your configuration**: Copy your `config/ares.php` file
2. **Update dependencies**: Run `composer update nyoncode/laravel-ares`
3. **Check for breaking changes**: Review the release notes
4. **Test your application**: Ensure all functionality works as expected

### Version Compatibility

| Package Version | Laravel Version | PHP Version |
|----------------|----------------|-------------|
| 1.x | 10.x | 8.2+ |
| 2.x | 10.x, 11.x | 8.2+ |
| 3.x | 10.x, 11.x, 12.x | 8.2+ |

## Next Steps

After successful installation:

1. Read the [Usage Examples](usage.md) to learn how to use the package
2. Check the [Helper Functions](helpers.md) documentation for available helpers
3. Review the [API Reference](api.md) for detailed method documentation
4. Configure [Events](events.md) if you need to handle lookup events

---

*Previous: [Overview](README.md) | Next: [Usage Examples](usage.md)*

# Configuration Guide

This guide covers all configuration options available in the Laravel ARES package.

## Configuration File

Publish the configuration file to customize package settings:

```bash
php artisan vendor:publish --tag="ares-config"
```

This creates `config/ares.php` with the following structure:

```php
<?php

return [
    'api_url' => env('ARES_API_URL', 'https://ares.gov.cz/ekonomicke-subjekty-v-be/rest'),
    'cache_ttl' => env('ARES_CACHE_TTL', 3600),
    'log_channel' => env('ARES_LOG_CHANNEL', 'default'),
    'http_options' => [
        'timeout' => env('ARES_HTTP_TIMEOUT', 5.0),
        'connect_timeout' => env('ARES_HTTP_CONNECT_TIMEOUT', 3.0),
    ],
];
```

## Configuration Options

### api_url

The base URL for the ARES API endpoint.

**Type:** `string`  
**Default:** `https://ares.gov.cz/ekonomicke-subjekty-v-be/rest`  
**Environment Variable:** `ARES_API_URL`

#### Available Endpoints

- **Production:** `https://ares.gov.cz/ekonomicke-subjekty-v-be/rest`
- **Testing:** You can use mock services for testing

#### Example

```env
ARES_API_URL=https://ares.gov.cz/ekonomicke-subjekty-v-be/rest
```

```php
// config/ares.php
'api_url' => 'https://ares.gov.cz/ekonomicke-subjekty-v-be/rest',
```

### cache_ttl

Cache time-to-live in seconds for ARES API responses.

**Type:** `int`  
**Default:** `3600` (1 hour)  
**Environment Variable:** `ARES_CACHE_TTL`

#### Recommended Values

- **Development:** `60` (1 minute) - for frequent testing
- **Production:** `3600` (1 hour) - balanced performance
- **High Traffic:** `86400` (24 hours) - maximum performance
- **Real-time Data:** `0` - disable caching

#### Examples

```env
# Development
ARES_CACHE_TTL=60

# Production
ARES_CACHE_TTL=3600

# High traffic applications
ARES_CACHE_TTL=86400

# Disable caching
ARES_CACHE_TTL=0
```

```php
// config/ares.php
'cache_ttl' => 3600, // 1 hour
```

### log_channel

The Laravel log channel used for ARES-related logging.

**Type:** `string`  
**Default:** `default`  
**Environment Variable:** `ARES_LOG_CHANNEL`

#### Available Channels

- `default` - Uses your default log channel
- `single` - Logs to `storage/logs/laravel.log`
- `daily` - Creates daily log files
- `stack` - Multiple log channels
- Custom channels defined in `config/logging.php`

#### Examples

```env
# Use default channel
ARES_LOG_CHANNEL=default

# Use dedicated ARES log file
ARES_LOG_CHANNEL=ares

# Use stack for multiple channels
ARES_LOG_CHANNEL=stack
```

```php
// config/logging.php - add custom channel
'channels' => [
    'ares' => [
        'driver' => 'single',
        'path' => storage_path('logs/ares.log'),
        'level' => 'info',
    ],
],

// config/ares.php
'log_channel' => 'ares',
```

### http_options

HTTP client configuration for API requests.

#### timeout

Request timeout in seconds.

**Type:** `float`  
**Default:** `5.0`  
**Environment Variable:** `ARES_HTTP_TIMEOUT`

#### connect_timeout

Connection timeout in seconds.

**Type:** `float`  
**Default:** `3.0`  
**Environment Variable:** `ARES_HTTP_CONNECT_TIMEOUT`

#### Recommended Values

- **Fast Networks:** `timeout: 3.0, connect_timeout: 2.0`
- **Standard:** `timeout: 5.0, connect_timeout: 3.0` (default)
- **Slow Networks:** `timeout: 10.0, connect_timeout: 5.0`
- **Unreliable Networks:** `timeout: 15.0, connect_timeout: 8.0`

#### Examples

```env
# Standard configuration
ARES_HTTP_TIMEOUT=5.0
ARES_HTTP_CONNECT_TIMEOUT=3.0

# Fast networks
ARES_HTTP_TIMEOUT=3.0
ARES_HTTP_CONNECT_TIMEOUT=2.0

# Slow networks
ARES_HTTP_TIMEOUT=10.0
ARES_HTTP_CONNECT_TIMEOUT=5.0
```

```php
// config/ares.php
'http_options' => [
    'timeout' => 5.0,
    'connect_timeout' => 3.0,
],
```

## Environment Configuration

### .env File

Add these variables to your `.env` file:

```env
# ARES Configuration
ARES_API_URL=https://ares.gov.cz/ekonomicke-subjekty-v-be/rest
ARES_CACHE_TTL=3600
ARES_LOG_CHANNEL=default
ARES_HTTP_TIMEOUT=5.0
ARES_HTTP_CONNECT_TIMEOUT=3.0
```

### Environment-Specific Configurations

#### Development Environment

```env
# .env
ARES_CACHE_TTL=60
ARES_LOG_CHANNEL=stack
ARES_HTTP_TIMEOUT=3.0
ARES_HTTP_CONNECT_TIMEOUT=2.0
```

```php
// config/ares.php (development)
'cache_ttl' => env('ARES_CACHE_TTL', 60),
'log_channel' => env('ARES_LOG_CHANNEL', 'stack'),
'http_options' => [
    'timeout' => env('ARES_HTTP_TIMEOUT', 3.0),
    'connect_timeout' => env('ARES_HTTP_CONNECT_TIMEOUT', 2.0),
],
```

#### Production Environment

```env
# .env.production
ARES_CACHE_TTL=3600
ARES_LOG_CHANNEL=daily
ARES_HTTP_TIMEOUT=5.0
ARES_HTTP_CONNECT_TIMEOUT=3.0
```

```php
// config/ares.php (production)
'cache_ttl' => env('ARES_CACHE_TTL', 3600),
'log_channel' => env('ARES_LOG_CHANNEL', 'daily'),
'http_options' => [
    'timeout' => env('ARES_HTTP_TIMEOUT', 5.0),
    'connect_timeout' => env('ARES_HTTP_CONNECT_TIMEOUT', 3.0),
],
```

#### Testing Environment

```env
# .env.testing
ARES_CACHE_TTL=0
ARES_LOG_CHANNEL=stack
ARES_HTTP_TIMEOUT=1.0
ARES_HTTP_CONNECT_TIMEOUT=0.5
```

## Advanced Configuration

### Custom Cache Configuration

You can customize cache behavior by modifying the cache store:

```php
// config/ares.php
// Use Redis for better performance
'cache_store' => env('ARES_CACHE_STORE', 'redis'),
'cache_prefix' => env('ARES_CACHE_PREFIX', 'ares'),
```

### Retry Configuration

Configure retry logic for failed requests:

```php
// config/ares.php
'retry' => [
    'attempts' => env('ARES_RETRY_ATTEMPTS', 3),
    'delay' => env('ARES_RETRY_DELAY', 1000), // milliseconds
],
```

### Rate Limiting

Configure rate limiting to avoid API limits:

```php
// config/ares.php
'rate_limit' => [
    'requests_per_minute' => env('ARES_RATE_LIMIT', 60),
    'requests_per_hour' => env('ARES_RATE_LIMIT_HOUR', 1000),
],
```

### Custom Headers

Add custom HTTP headers:

```php
// config/ares.php
'http_headers' => [
    'User-Agent' => env('ARES_USER_AGENT', 'Laravel-ARES/1.0'),
    'Accept' => 'application/json',
],
```

## Cache Configuration

### Cache Store Configuration

Configure different cache stores for ARES:

```php
// config/cache.php
'stores' => [
    'ares' => [
        'driver' => 'redis',
        'connection' => 'cache',
        'prefix' => 'ares_cache',
    ],
],
```

```env
ARES_CACHE_STORE=ares
```

### Cache Key Strategy

The package uses the following cache key pattern:
```
ares:v1:company:{ic}
```

You can customize the prefix:

```php
// config/ares.php
'cache_key_prefix' => env('ARES_CACHE_PREFIX', 'ares:v1'),
```

## Logging Configuration

### Dedicated ARES Log Channel

Create a dedicated log channel for ARES:

```php
// config/logging.php
'channels' => [
    'ares' => [
        'driver' => 'daily',
        'path' => storage_path('logs/ares.log'),
        'level' => env('ARES_LOG_LEVEL', 'info'),
        'days' => 30,
    ],
],
```

```env
ARES_LOG_CHANNEL=ares
ARES_LOG_LEVEL=debug
```

### Log Levels

Control the verbosity of ARES logging:

```env
# Production - only errors and warnings
ARES_LOG_LEVEL=warning

# Development - include info and debug
ARES_LOG_LEVEL=debug

# Minimal logging
ARES_LOG_LEVEL=error
```

## Performance Optimization

### High Traffic Configuration

For high-traffic applications:

```env
ARES_CACHE_TTL=86400
ARES_HTTP_TIMEOUT=3.0
ARES_HTTP_CONNECT_TIMEOUT=2.0
ARES_CACHE_STORE=redis
```

### Memory Optimization

For memory-constrained environments:

```env
ARES_CACHE_TTL=1800
ARES_HTTP_TIMEOUT=2.0
ARES_HTTP_CONNECT_TIMEOUT=1.0
```

### Real-time Data Configuration

When you need the most current data:

```env
ARES_CACHE_TTL=0
ARES_HTTP_TIMEOUT=10.0
ARES_HTTP_CONNECT_TIMEOUT=5.0
```

## Troubleshooting Configuration

### Common Issues

#### 1. Cache Not Working

```php
// Clear cache
php artisan cache:clear

// Check cache configuration
php artisan config:cache

// Verify cache store
php artisan tinker
>>> cache()->store('redis')->put('test', 'value', 60);
```

#### 2. Logging Issues

```php
// Test log channel
php artisan tinker
>>> Log::channel('ares')->info('Test message');
```

#### 3. HTTP Timeout Issues

```php
// Test HTTP connectivity
php artisan tinker
>>> Http::timeout(5.0)->get('https://ares.gov.cz/ekonomicke-subjekty-v-be/rest');
```

### Debug Configuration

Enable debug mode for troubleshooting:

```php
// config/ares.php
'debug' => env('ARES_DEBUG', false),
```

```env
ARES_DEBUG=true
ARES_LOG_LEVEL=debug
```

## Configuration Validation

The package validates configuration on startup. Common validation errors:

- **Invalid URL**: `api_url` must be a valid URL
- **Invalid TTL**: `cache_ttl` must be a non-negative integer
- **Invalid Timeout**: HTTP timeouts must be positive numbers
- **Missing Channel**: `log_channel` must exist in logging configuration

---

*Previous: [Helper Functions](helpers.md) | Next: [Events](events.md)*

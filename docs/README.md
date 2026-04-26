# Laravel ARES Package Documentation

A comprehensive Laravel package for interacting with the Czech ARES (Administrativní registr ekonomických subjektů) business register API.

## Table of Contents

- [Installation](installation.md)
- [Configuration](configuration.md)
- [Usage Examples](usage.md)
- [Helper Functions](helpers.md)
- [API Reference](api.md)
- [Events](events.md)
- [Frequently Asked Questions](faq.md)

## Overview

The Laravel ARES package provides a simple and elegant way to interact with the Czech ARES business register. It includes:

- **Caching**: Built-in caching support to reduce API calls
- **Events**: Laravel events for successful and failed lookups
- **Validation**: IC (identification number) format validation
- **Helper Functions**: Global helper functions for common operations
- **Artisan Commands**: Command-line tools for testing and debugging
- **Type Safety**: Full PHP 8.2+ type safety and strict typing

## Quick Start

```bash
composer require nyoncode/laravel-ares
```

```php
// Basic usage
$company = ares()->findCompany('12345678');

// Using helper functions
if (ares_is_company_active('12345678')) {
    $address = ares_get_address('12345678');
}

// Using facade
$company = Ares::findCompanyOrFail('12345678');

// Using fluent API - most elegant way
$companies = ares()
    ->findMany(['12345678', '87654321'])
    ->active()
    ->withVat()
    ->limit(10)
    ->getFormatted();

// Or get statistics
$stats = ares()
    ->findMany(['12345678', '87654321'])
    ->get()
    ->stats();
```

## Features

### 🔍 Company Lookup
- Find companies by identification number (IC)
- Raw API data access
- Exception-based error handling

### ✅ Validation
- IC format validation with checksum verification
- Normalization of IC numbers

### 🚀 Performance
- Configurable caching
- HTTP timeout configuration
- Efficient data processing

### 📊 Data Processing
- Company statistics
- Filtering and searching capabilities
- Formatted display data

### 🎯 Helper Functions
- Global helper functions like `ares()`, `ares_is_company_active()`
- Facade-based methods
- Dependency injection support

### 🌊 Fluent API
- Method chaining for elegant queries
- Advanced filtering and pagination
- Data extraction and statistics
- Cache management

### 🔧 Laravel Integration
- Service provider registration
- Configuration file publishing
- Artisan commands
- Event system integration

## Requirements

- PHP 8.2 or higher
- Laravel 10.0, 11.0, 12.0, or 13.0
- Guzzle HTTP Client 7.0 or higher

## License

This package is open-source software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Support

For support and questions, please visit our [GitHub repository](https://github.com/nyoncode/laravel-ares).

---

*Next: [Installation Guide](installation.md)*

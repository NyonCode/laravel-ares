# Frequently Asked Questions

This document answers common questions about the Laravel ARES package.

## General Questions

### What is ARES?

ARES (Administrativní registr ekonomických subjektů) is the Czech Republic's business register containing information about all registered companies, including their identification numbers (IC), addresses, legal forms, and registration status.

### What Laravel versions are supported?

The package supports Laravel 10.0, 11.0, 12.0, and 13.0.

### What PHP version is required?

PHP 8.2 or higher is required.

### Is this package free?

Yes, the package is open-source and licensed under the MIT license.

## Installation and Setup

### How do I install the package?

```bash
composer require nyoncode/laravel-ares
```

### Do I need to publish configuration files?

No, the package works out of the box with sensible defaults. However, you can publish the configuration file to customize settings:

```bash
php artisan vendor:publish --tag="ares-config"
```

### The helper functions are not available. What should I do?

Run `composer dump-autoload` to ensure the helper files are properly autoloaded:

```bash
composer dump-autoload
```

### How do I register the service provider?

The package uses Laravel's auto-discovery, so the service provider is registered automatically. If you're not using auto-discovery, add it to `config/app.php`:

```php
'providers' => [
    // ...
    NyonCode\Ares\Providers\AresServiceProvider::class,
],
```

## Usage

### How do I find a company by IC?

```php
use NyonCode\Ares\Facades\Ares;

$company = Ares::findCompany('12345678');
if ($company) {
    echo $company->name;
}
```

### How do I use the helper functions?

```php
// Check if company is active
if (ares_is_company_active('12345678')) {
    echo "Company is active";
}

// Get company address
$address = ares_get_address('12345678');

// Validate IC format
if (ares_validate_ic('12345678')) {
    echo "Valid IC";
}
```

### What's the difference between `findCompany()` and `findCompanyOrFail()`?

- `findCompany()` returns `null` if the company is not found
- `findCompanyOrFail()` throws a `CompanyNotFoundException` if the company is not found

```php
// Safe approach
$company = Ares::findCompany('12345678');
if ($company) {
    // Use company
}

// Exception handling
try {
    $company = Ares::findCompanyOrFail('12345678');
    // Use company
} catch (CompanyNotFoundException $e) {
    // Handle not found
}
```

### How do I get raw API response data?

```php
$rawData = Ares::findCompanyRaw('12345678');
if ($rawData) {
    echo $rawData['obchodniJmeno']; // Raw API field
}
```

### How do I validate an IC without making an API call?

```php
if (ares_validate_ic('12345678')) {
    echo "IC format is valid";
}
```

## Data and Fields

### What information is available for a company?

The package provides access to:

- **Basic Info**: Name, IC, DIC (VAT number)
- **Registration**: Active status, establishment date, legal form, financial office
- **Address**: Registered office and delivery address
- **Raw Data**: Complete API response

### How do I check if a company is active?

```php
$company = Ares::findCompany('12345678');
if ($company && $company->registration->active) {
    echo "Company is active";
}

// Or using helper
if (ares_is_company_active('12345678')) {
    echo "Company is active";
}
```

### How do I get the company's address?

```php
$company = Ares::findCompany('12345678');
if ($company && $company->registeredOffice) {
    echo $company->registeredOffice->formatted;
}

// Or using helper
$address = ares_get_address('12345678');
```

### What does the `dic` field contain?

The `dic` field contains the VAT identification number (DIČ) if the company has one. It may be null if the company doesn't have VAT registration.

## Caching

### How does caching work?

The package automatically caches API responses based on your `cache_ttl` configuration. Subsequent requests for the same IC within the cache period will return cached data.

### How do I clear the cache for a specific company?

```php
Ares::forgetCompany('12345678');
```

### How do I disable caching?

Set `cache_ttl` to `0` in your configuration:

```env
ARES_CACHE_TTL=0
```

### How long should I set the cache TTL?

- **Development**: 60 seconds (1 minute)
- **Production**: 3600 seconds (1 hour)
- **High Traffic**: 86400 seconds (24 hours)
- **Real-time Data**: 0 (disabled)

## Errors and Troubleshooting

### I'm getting a "Company not found" error. What does this mean?

This means the IC number either doesn't exist in the ARES database or is invalid. Use `ares_validate_ic()` to check if the format is correct.

### Why am I getting timeout errors?

The ARES API might be slow or unavailable. Try increasing the timeout values:

```env
ARES_HTTP_TIMEOUT=10.0
ARES_HTTP_CONNECT_TIMEOUT=5.0
```

### How do I debug API issues?

1. Enable debug logging:
   ```env
   ARES_LOG_LEVEL=debug
   ARES_LOG_CHANNEL=stack
   ```

2. Check the logs:
   ```bash
   tail -f storage/logs/laravel.log
   ```

3. Test the API directly:
   ```bash
   curl -v https://ares.gov.cz/ekonomicke-subjekty-v-be/rest/ekonomicke-subjekty/12345678
   ```

### What does "Invalid IC format" mean?

The IC number doesn't match the required 8-digit format or has an invalid checksum. Use `ares_validate_ic()` to check the format.

## Performance

### How can I improve performance?

1. **Enable Caching**: Set appropriate `cache_ttl`
2. **Use Redis**: Configure Redis for better cache performance
3. **Batch Operations**: Use `validateMultipleIcs()` for multiple lookups
4. **Optimize Timeouts**: Adjust HTTP timeouts based on your network

### Is there a rate limit?

The ARES API doesn't have an official rate limit, but it's good practice to:
- Cache results appropriately
- Avoid excessive requests
- Implement retry logic for failures

### How do I handle multiple IC lookups efficiently?

```php
$ics = ['12345678', '87654321', '11223344'];
$results = ares('validateMultipleIcs', $ics);

foreach ($results as $ic => $company) {
    if ($company) {
        echo "Found: {$company->name}";
    }
}
```

## Integration

### How do I integrate with my existing database?

Use events to sync ARES data with your database:

```php
// In your EventServiceProvider
protected $listen = [
    CompanyLookupSucceeded::class => [
        SyncCompanyToLocalDatabase::class,
    ],
];
```

### Can I use this in API endpoints?

Yes, the package works well in API controllers:

```php
public function show(string $ic): JsonResponse
{
    try {
        $company = Ares::findCompanyOrFail($ic);
        return response()->json($company);
    } catch (CompanyNotFoundException $e) {
        return response()->json(['error' => 'Company not found'], 404);
    }
}
```

### How do I use this in queue jobs?

Inject the ARES client into your job:

```php
class ProcessCompanyLookup implements ShouldQueue
{
    public function __construct(
        private string $ic
    ) {}

    public function handle(AresClientInterface $ares): void
    {
        $company = $ares->findCompany($this->ic);
        // Process company data
    }
}
```

## Testing

### How do I test ARES functionality?

Use the provided test command:

```bash
php artisan ares:test 12345678
```

### How do I mock ARES in tests?

```php
use NyonCode\Ares\Contracts\AresClientInterface;

class CompanyTest extends TestCase
{
    public function test_company_lookup(): void
    {
        $mock = $this->mock(AresClientInterface::class);
        $mock->shouldReceive('findCompany')
            ->with('12345678')
            ->andReturn($this->createMockCompany());

        // Test your code
    }
}
```

### Should I use real IC numbers in tests?

No, use mock data or test IC numbers provided in the documentation. Avoid using real company data in automated tests.

## Security and Privacy

### Is the data from ARES public?

Yes, all data from the ARES register is public information. However, be mindful of:
- Data retention policies
- GDPR compliance for EU users
- Rate limiting and fair use

### Should I store ARES data in my database?

Yes, you can store public ARES data, but:
- Keep it updated (companies can change status)
- Consider data freshness requirements
- Implement proper data retention policies

### How do I handle sensitive data?

The package only accesses public company information. No sensitive personal data is retrieved from ARES.

## Support and Contributing

### Where can I get help?

- **GitHub Issues**: Report bugs and request features
- **Documentation**: Check these docs first
- **Laravel Community**: Ask in Laravel forums and communities

### How do I contribute?

1. Fork the repository
2. Create a feature branch
3. Write tests for your changes
4. Submit a pull request

### What's the roadmap?

- Enhanced filtering and search capabilities
- Additional data sources
- Performance optimizations
- More helper functions

---

*Previous: [Events](events.md) | Back to [README](README.md)*

# API Reference

This document provides a comprehensive reference for all classes, methods, and interfaces in the Laravel ARES package.

## Core Interfaces

### AresClientInterface

The main interface that defines all ARES operations.

```php
namespace NyonCode\Ares\Contracts;

interface AresClientInterface
{
    public function findCompany(string $ic): ?CompanyData;
    public function findCompanyRaw(string $ic): ?array;
    public function findCompanyOrFail(string $ic): CompanyData;
    public function forgetCompany(string $ic): bool;
    public function isValidIc(string $ic): bool;
    public function normalizeIc(string $ic): string;
}
```

## Main Classes

### AresClient

The primary implementation of the ARES client.

#### Constructor

```php
public function __construct(
    string $baseUrl,
    int $cacheTtl,
    LoggerInterface $logger,
    Dispatcher $events,
    Cache $cache,
    Http $http
)
```

#### Methods

##### findCompany(string $ic): ?CompanyData

Find a company by its identification number.

**Parameters:**
- `$ic` (string) - The company identification number

**Returns:**
- `CompanyData|null` - Company data or null if not found/invalid

**Example:**
```php
$company = $ares->findCompany('12345678');
if ($company) {
    echo $company->name;
}
```

##### findCompanyRaw(string $ic): ?array

Find a company and return raw API response data.

**Parameters:**
- `$ic` (string) - The company identification number

**Returns:**
- `array|null` - Raw API response or null if not found

**Example:**
```php
$raw = $ares->findCompanyRaw('12345678');
if ($raw) {
    echo $raw['obchodniJmeno'];
}
```

##### findCompanyOrFail(string $ic): CompanyData

Find a company or throw an exception.

**Parameters:**
- `$ic` (string) - The company identification number

**Returns:**
- `CompanyData` - Company data

**Throws:**
- `InvalidIcException` - When IC format is invalid
- `CompanyNotFoundException` - When company is not found

**Example:**
```php
try {
    $company = $ares->findCompanyOrFail('12345678');
} catch (CompanyNotFoundException $e) {
    echo "Company not found";
}
```

##### forgetCompany(string $ic): bool

Remove a company from cache.

**Parameters:**
- `$ic` (string) - The company identification number

**Returns:**
- `bool` - True if cache entry was removed

**Example:**
```php
$ares->forgetCompany('12345678');
```

##### isValidIc(string $ic): bool

Validate IC format and checksum.

**Parameters:**
- `$ic` (string) - The identification number to validate

**Returns:**
- `bool` - True if IC is valid

**Example:**
```php
if ($ares->isValidIc('12345678')) {
    echo "Valid IC";
}
```

##### normalizeIc(string $ic): string

Normalize IC to 8-digit format.

**Parameters:**
- `$ic` (string) - The identification number to normalize

**Returns:**
- `string` - Normalized 8-digit IC

**Example:**
```php
$normalized = $ares->normalizeIc('123 456 78');
echo $normalized; // '12345678'
```

## Data Classes

### CompanyData

Represents a company with all its information.

#### Properties

```php
public string $ic;
public string $name;
public ?string $dic;
public RegistrationData $registration;
public ?AddressData $registeredOffice;
public ?AddressData $deliveryAddress;
public ?array $rawData;
```

#### Methods

##### fromApiResponse(array $data): CompanyData

Create CompanyData from API response.

**Parameters:**
- `$data` (array) - Raw API response data

**Returns:**
- `CompanyData` - Company data object

### RegistrationData

Contains company registration information.

#### Properties

```php
public bool $active;
public ?string $dateOfEstablishment;
public ?string $legalForm;
public ?string $financialOffice;
public ?string $primarySource;
public ?string $businessRegisterFileMark;
```

### AddressData

Represents an address.

#### Properties

```php
public ?string $street;
public ?string $city;
public ?string $zipCode;
public ?string $country;
public ?string $formatted;
```

### DeliveryAddressData

Represents a delivery address.

#### Properties

```php
public ?string $street;
public ?string $city;
public ?string $zipCode;
public ?string $country;
public ?string $formatted;
```

## Exceptions

### InvalidIcException

Thrown when IC format is invalid.

#### Methods

##### forIc(string $ic): self

Create exception for invalid IC.

**Parameters:**
- `$ic` (string) - The invalid IC

**Returns:**
- `InvalidIcException` - Exception instance

### CompanyNotFoundException

Thrown when company is not found in ARES.

#### Methods

##### forIc(string $ic): self

Create exception for company not found.

**Parameters:**
- `$ic` (string) - The IC that was not found

**Returns:**
- `CompanyNotFoundException` - Exception instance

### InvalidApiResponseException

Thrown when API response is invalid.

#### Methods

##### missingRequiredField(string $field): self

Create exception for missing field.

**Parameters:**
- `$field` (string) - Missing field name

**Returns:**
- `InvalidApiResponseException` - Exception instance

##### invalidPayloadType(): self

Create exception for invalid payload type.

**Returns:**
- `InvalidApiResponseException` - Exception instance

## Facade

### Ares

Laravel facade for accessing ARES client.

#### Available Methods

All methods from `AresClientInterface` are available:

```php
Ares::findCompany($ic);
Ares::findCompanyRaw($ic);
Ares::findCompanyOrFail($ic);
Ares::forgetCompany($ic);
Ares::isValidIc($ic);
Ares::normalizeIc($ic);
```

## Artisan Commands

### TestAresCommand

Test ARES API communication.

#### Usage

```bash
php artisan ares:test {ic}
```

**Parameters:**
- `ic` - Company identification number

**Example:**
```bash
php artisan ares:test 12345678
```

## Events

### CompanyLookupSucceeded

Dispatched when company lookup succeeds.

#### Constructor

```php
public function __construct(CompanyData $company)
```

#### Properties

```php
public CompanyData $company;
```

### CompanyLookupFailed

Dispatched when company lookup fails.

#### Constructor

```php
public function __construct(string $ic, int $status = 0, ?Throwable $exception = null)
```

#### Properties

```php
public string $ic;
public int $status;
public ?Throwable $exception;
```

## Enums

### RegistrationSourceState

Represents registration source states.

#### Values

```php
case ACTIVE = 'ACTIVE';
case INACTIVE = 'INACTIVE';
```

## Type Hints

The package uses strict typing throughout. All methods have proper return type declarations and parameter type hints.

```php
// Example method signature
public function findCompany(string $ic): ?CompanyData
```

## Error Handling

All methods follow consistent error handling patterns:

1. **Null returns**: Methods that might not find data return `null`
2. **Exceptions**: Methods that should always succeed throw exceptions on failure
3. **Validation**: Input validation happens before API calls
4. **Logging**: Errors are automatically logged

## Performance Considerations

### Caching

- Results are cached automatically based on configuration
- Use `forgetCompany()` to clear specific cache entries
- Cache TTL is configurable

### HTTP Timeouts

- Connection timeout: 3 seconds (configurable)
- Request timeout: 5 seconds (configurable)
- Failed requests are logged and events are dispatched

### Memory Usage

- Large API responses are processed efficiently
- Only necessary data is stored in objects
- Raw data is available but optional

---

*Previous: [Usage Examples](usage.md) | Next: [Helper Functions](helpers.md)*

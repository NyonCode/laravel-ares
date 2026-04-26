# Helper Functions

This document covers all helper functions available in the Laravel ARES package, including global functions and the AresHelper class.

## Global Helper Functions

The package provides global helper functions that work like Laravel's built-in helpers (`app()`, `auth()`, etc.).

### Main ares() Function

The primary helper function that provides access to all ARES functionality.

#### Usage

```php
// Get the ARES client instance
$client = ares();

// Call helper methods dynamically
$result = ares('methodName', ...$args);
```

#### Available Methods

| Method | Parameters | Returns | Description |
|--------|------------|---------|-------------|
| `isCompanyActiveByIc` | `string $ic` | `bool` | Check if company is active |
| `getAddressByIc` | `string $ic` | `string` | Get formatted address |
| `getLegalFormByIc` | `string $ic` | `string` | Get legal form |
| `hasVatNumberByIc` | `string $ic` | `bool` | Check if company has VAT |
| `getEstablishmentDateByIc` | `string $ic, string $format = 'Y-m-d'` | `string` | Get establishment date |
| `formatCompanyByIc` | `string $ic` | `array` | Get formatted company data |
| `validateIcFormat` | `string $ic` | `bool` | Validate IC format |
| `normalizeIcFormat` | `string $ic` | `string` | Normalize IC format |
| `isCompanyActive` | `CompanyData $company` | `bool` | Check if company object is active |
| `getFullAddress` | `CompanyData $company` | `string` | Get formatted address from object |
| `getLegalForm` | `CompanyData $company` | `string` | Get legal form from object |
| `hasVatNumber` | `CompanyData $company` | `bool` | Check VAT from object |
| `getEstablishmentDate` | `CompanyData $company, string $format = 'Y-m-d'` | `string` | Get date from object |
| `formatCompanyForDisplay` | `CompanyData $company` | `array` | Format object for display |
| `validateMultipleIcs` | `array $ics` | `array` | Validate multiple ICs |
| `filterByLegalForm` | `array $companies, string $legalForm` | `array` | Filter by legal form |
| `filterActiveCompanies` | `array $companies` | `array` | Filter active companies |
| `getCompanyStatistics` | `array $companies` | `array` | Get statistics |
| `searchByName` | `array $companies, string $searchTerm, bool $caseSensitive = false` | `array` | Search by name |

#### Examples

```php
// Get client and use it directly
$client = ares();
$company = $client->findCompany('12345678');

// Use dynamic method calls
$isActive = ares('isCompanyActiveByIc', '12345678');
$address = ares('getAddressByIc', '12345678');
$date = ares('getEstablishmentDateByIc', '12345678', 'd.m.Y');

// Handle invalid method
try {
    ares('nonExistentMethod', '12345678');
} catch (InvalidArgumentException $e) {
    echo $e->getMessage(); // "Method [nonExistentMethod] does not exist on AresHelper."
}
```

### Dedicated Global Functions

These functions provide convenient shortcuts for common operations.

#### ares_is_company_active(string $ic): bool

Check if a company is active by its IC.

```php
if (ares_is_company_active('12345678')) {
    echo "Company is active and exists";
}
```

#### ares_get_address(string $ic): string

Get company's formatted address.

```php
$address = ares_get_address('12345678');
echo $address; // "Street 123, City, 123 45" or "N/A"
```

#### ares_has_vat(string $ic): bool

Check if company has VAT registration.

```php
if (ares_has_vat('12345678')) {
    echo "Company has VAT number";
}
```

#### ares_validate_ic(string $ic): bool

Validate IC format and checksum.

```php
if (ares_validate_ic('12345678')) {
    echo "IC format is valid";
}
```

#### ares_normalize_ic(string $ic): string

Normalize IC to 8-digit format.

```php
$normalized = ares_normalize_ic('123 456 78');
echo $normalized; // '12345678'
```

## AresHelper Class

The `AresHelper` class contains all utility methods used by the global helpers. You can also use it directly.

### Company Status Methods

#### isCompanyActive(CompanyData $company): bool

Check if a company is active based on registration status.

```php
use NyonCode\Ares\Helpers\AresHelper;
use NyonCode\Ares\Facades\Ares;

$company = Ares::findCompany('12345678');
if ($company && AresHelper::isCompanyActive($company)) {
    echo "Company is active";
}
```

#### isCompanyActiveByIc(string $ic): bool

Find company and check if it's active in one call.

```php
if (AresHelper::isCompanyActiveByIc('12345678')) {
    echo "Company exists and is active";
}
```

### Address Methods

#### getFullAddress(CompanyData $company): string

Get the full formatted address of a company.

```php
$address = AresHelper::getFullAddress($company);
echo $address; // "Street 123, City, 123 45" or "N/A"
```

#### getAddressByIc(string $ic): string

Find company and get its formatted address in one call.

```php
$address = AresHelper::getAddressByIc('12345678');
```

### Company Information Methods

#### getLegalForm(CompanyData $company): string

Get the company's legal form.

```php
$legalForm = AresHelper::getLegalForm($company);
echo $legalForm; // "s.r.o.", "a.s.", etc. or "N/A"
```

#### getLegalFormByIc(string $ic): string

Find company and get its legal form in one call.

```php
$legalForm = AresHelper::getLegalFormByIc('12345678');
```

#### hasVatNumber(CompanyData $company): bool

Check if a company has a VAT number.

```php
if (AresHelper::hasVatNumber($company)) {
    echo "VAT number: " . $company->dic;
}
```

#### hasVatNumberByIc(string $ic): bool

Find company and check if it has VAT number in one call.

```php
if (AresHelper::hasVatNumberByIc('12345678')) {
    echo "Company has VAT registration";
}
```

#### getEstablishmentDate(CompanyData $company, string $format = 'Y-m-d'): string

Get the company's establishment date in a formatted way.

```php
$date = AresHelper::getEstablishmentDate($company, 'd.m.Y');
echo $date; // "01.01.2020" or "N/A"
```

#### getEstablishmentDateByIc(string $ic, string $format = 'Y-m-d'): string

Find company and get its establishment date in one call.

```php
$date = AresHelper::getEstablishmentDateByIc('12345678', 'F j, Y');
echo $date; // "January 1, 2020"
```

### Data Processing Methods

#### validateMultipleIcs(array $ics): array

Validate multiple IC numbers and return the valid ones with their data.

```php
$ics = ['12345678', '87654321', 'invalid'];
$results = AresHelper::validateMultipleIcs($ics);

foreach ($results as $ic => $company) {
    if ($company) {
        echo "IC {$ic}: {$company->name}";
    } else {
        echo "IC {$ic}: Not found or invalid";
    }
}
```

#### filterByLegalForm(array $companies, string $legalForm): array

Filter companies by legal form.

```php
$companies = [/* array of CompanyData objects */];
$sroCompanies = AresHelper::filterByLegalForm($companies, 's.r.o.');
```

#### filterActiveCompanies(array $companies): array

Filter active companies from an array.

```php
$activeCompanies = AresHelper::filterActiveCompanies($companies);
```

#### searchByName(array $companies, string $searchTerm, bool $caseSensitive = false): array

Search companies by name in an array.

```php
$results = AresHelper::searchByName($companies, 'Název');
$results = AresHelper::searchByName($companies, 'NAZEV', true); // case sensitive
```

### Statistics Methods

#### getCompanyStatistics(array $companies): array

Get comprehensive statistics about companies.

```php
$stats = AresHelper::getCompanyStatistics($companies);

echo "Total: " . $stats['total'];
echo "Active: " . $stats['active'];
echo "Inactive: " . $stats['inactive'];
echo "With VAT: " . $stats['with_vat'];
echo "Active percentage: " . $stats['active_percentage'] . '%';
echo "VAT percentage: " . $stats['vat_percentage'] . '%';
```

**Returns:**
```php
[
    'total' => int,           // Total number of companies
    'active' => int,          // Number of active companies
    'inactive' => int,        // Number of inactive companies
    'with_vat' => int,        // Companies with VAT number
    'without_vat' => int,     // Companies without VAT number
    'active_percentage' => float, // Percentage of active companies
    'vat_percentage' => float,    // Percentage of companies with VAT
]
```

### Display Methods

#### formatCompanyForDisplay(CompanyData $company): array

Format company information as an array for display purposes.

```php
$display = AresHelper::formatCompanyForDisplay($company);

// $display contains:
[
    'IC' => '12345678',
    'Name' => 'Company Name',
    'DIC' => 'CZ12345678',
    'Status' => 'Active',
    'Legal Form' => 's.r.o.',
    'Establishment Date' => '2020-01-01',
    'Address' => 'Street 123, City, 123 45',
    'Financial Office' => 'Finanční úřad',
    'Primary Source' => 'RES',
]
```

#### formatCompanyByIc(string $ic): array

Find company and format it for display in one call.

```php
$display = AresHelper::formatCompanyByIc('12345678');
// Returns formatted array or empty array if not found
```

### Validation Methods

#### validateIcFormat(string $ic): bool

Validate IC format without making API call.

```php
if (AresHelper::validateIcFormat('12345678')) {
    echo "IC format is valid";
}
```

#### normalizeIcFormat(string $ic): string

Normalize IC format without making API call.

```php
$normalized = AresHelper::normalizeIcFormat('123 456 78');
echo $normalized; // '12345678'
```

## Usage Patterns

### Dependency Injection

You can inject the AresHelper into your classes:

```php
use NyonCode\Ares\Helpers\AresHelper;

class CompanyService
{
    public function __construct(
        private AresHelper $aresHelper
    ) {}

    public function getActiveCompanies(array $ics): array
    {
        $results = $this->aresHelper->validateMultipleIcs($ics);
        return $this->aresHelper->filterActiveCompanies($results);
    }
}
```

### Service Container Access

Access the helper through the service container:

```php
$helper = app('ares.helper');
$stats = $helper->getCompanyStatistics($companies);
```

### chaining Operations

Combine multiple helper methods for complex operations:

```php
// Get statistics for active companies with VAT
$companies = [/* array of companies */];
$activeCompanies = AresHelper::filterActiveCompanies($companies);
$stats = AresHelper::getCompanyStatistics($activeCompanies);

// Search for specific legal form among active companies
$sroCompanies = AresHelper::filterByLegalForm($companies, 's.r.o.');
$activeSroCompanies = AresHelper::filterActiveCompanies($sroCompanies);
```

## Performance Considerations

### Caching

Helper methods that call `findCompany()` benefit from automatic caching:
- `isCompanyActiveByIc()`
- `getAddressByIc()`
- `getLegalFormByIc()`
- `hasVatNumberByIc()`
- `getEstablishmentDateByIc()`
- `formatCompanyByIc()`

### Memory Usage

- Methods that work with existing `CompanyData` objects are memory efficient
- `validateMultipleIcs()` processes ICs in batches
- Large arrays are handled efficiently in filtering methods

### Error Handling

All helper methods follow consistent error handling:
- Invalid ICs return `null` or default values
- Missing data returns 'N/A' for strings
- Array methods return empty arrays for invalid input

## Fluent API

The package includes a powerful fluent API that allows method chaining for elegant queries.

### Getting Started

```php
// Direct fluent API - most elegant way
$companies = ares()
    ->findMany(['12345678', '87654321'])
    ->active()
    ->withVat()
    ->limit(10)
    ->get();

// Single company lookup
$company = ares()
    ->find('12345678')
    ->active()
    ->firstOrFail();
```

### Common Fluent Operations

#### Finding Companies
```php
// Single company
$company = ares()->find('12345678')->firstOrFail();

// Multiple companies
$companies = ares()->findMany($ics)->get();

// With exception handling
$company = ares()->findOrFail('12345678')->firstOrFail();
```

#### Filtering
```php
// Active companies only
$active = ares()->findMany($ics)->active()->get();

// By legal form
$sroCompanies = ares()->findMany($ics)->legalForm('s.r.o.')->get();

// With VAT number
$withVat = ares()->findMany($ics)->withVat()->get();

// Search by name
$results = ares()->findMany($ics)->search('Technology')->get();
```

#### Pagination
```php
// Limit results
$companies = ares()->findMany($ics)->limit(5)->get();

// Offset and limit
$companies = ares()->findMany($ics)->offset(10)->limit(5)->get();

// Get first result
$company = ares()->findMany($ics)->active()->first()->firstOrFail();
```

#### Data Extraction
```php
// Get formatted arrays
$formatted = ares()->findMany($ics)->active()->getFormatted();

// Get names only
$names = ares()->findMany($ics)->active()->names();

// Get addresses only
$addresses = ares()->findMany($ics)->active()->addresses();

// Key by IC
$keyed = ares()->findMany($ics)->active()->keyByIc();
```

#### Checking Results
```php
// Check if exists
if (ares()->findMany($ics)->active()->exists()) {
    echo "Found active companies";
}

// Count results
$count = ares()->findMany($ics)->active()->count();

// Check if empty
if (ares()->findMany($ics)->active()->isEmpty()) {
    echo "No active companies";
}
```

#### Statistics
```php
$stats = ares()->findMany($ics)->get()->stats();
echo "Active: {$stats['active']} ({$stats['active_percentage']}%)";
```

### Advanced Usage

#### Complex Filtering
```php
$companies = ares()
    ->findMany($ics)
    ->active()
    ->withVat()
    ->legalForm('s.r.o.')
    ->search('Technology')
    ->limit(20)
    ->getFormatted();
```

#### Cache Management
```php
// Clear cache and get fresh data
$company = ares()
    ->find('12345678')
    ->forget()
    ->firstOrFail();
```

#### Reset Builder
```php
$builder = ares();
$results1 = $builder->findMany($ics1)->active()->get();
$builder->reset();
$results2 = $builder->findMany($ics2)->withVat()->get();
```

### Fluent API Methods

| Method | Description | Example |
|--------|-------------|---------|
| `find($ic)` | Find single company | `->find('12345678')` |
| `findMany($ics)` | Find multiple companies | `->findMany($ics)` |
| `findOrFail($ic)` | Find or throw exception | `->findOrFail('12345678')` |
| `active()` | Filter active companies | `->active()` |
| `inactive()` | Filter inactive companies | `->inactive()` |
| `legalForm($form)` | Filter by legal form | `->legalForm('s.r.o.')` |
| `withVat()` | Filter companies with VAT | `->withVat()` |
| `withoutVat()` | Filter without VAT | `->withoutVat()` |
| `search($term)` | Search by name | `->search('Technology')` |
| `limit($n)` | Limit results | `->limit(10)` |
| `offset($n)` | Skip results | `->offset(5)` |
| `first()` | Get first result | `->first()` |
| `get()` | Get CompanyData objects | `->get()` |
| `getFormatted()` | Get formatted arrays | `->getFormatted()` |
| `firstOrFail()` | Get first or null | `->firstOrFail()` |
| `company()` | Get single company | `->company()` |
| `exists()` | Check if results exist | `->exists()` |
| `isEmpty()` | Check if empty | `->isEmpty()` |
| `count()` | Count results | `->count()` |
| `names()` | Get names array | `->names()` |
| `ics()` | Get ICs array | `->ics()` |
| `addresses()` | Get addresses array | `->addresses()` |
| `keyByIc()` | Get IC-keyed array | `->keyByIc()` |
| `keyByIcFormatted()` | Get formatted IC-keyed | `->keyByIcFormatted()` |
| `stats()` | Get statistics | `->stats()` |
| `forget()` | Clear cache | `->forget()` |
| `reset()` | Reset builder | `->reset()` |

## Practical Examples & Best Practices

### Common Patterns

#### Find Active Companies with VAT
```php
$activeCompaniesWithVat = ares()
    ->findMany(['12345678', '87654321', '11223344'])
    ->active()
    ->withVat()
    ->getFormatted();

foreach ($activeCompaniesWithVat as $company) {
    echo "{$company['Name']} - {$company['Address']} (VAT: {$company['DIC']})\n";
}
```

#### Search and Limit Results
```php
$limitedSroCompanies = ares()
    ->findMany($ics)
    ->legalForm('s.r.o.')
    ->active()
    ->limit(5)
    ->get();
```

#### Data Extraction
```php
// Get just names
$names = ares()
    ->findMany($ics)
    ->active()
    ->names();

// Get key-value pairs by IC
$keyed = ares()
    ->findMany($ics)
    ->active()
    ->keyByIcFormatted();
```

#### Statistics
```php
$stats = ares()
    ->findMany($ics)
    ->get()
    ->stats();

echo "Total: {$stats['total']}, Active: {$stats['active']} ({$stats['active_percentage']}%)";
```

### Performance Tips

#### Filter Early
```php
// Good: Filter early to reduce dataset
$companies = ares()
    ->findMany($ics)
    ->active()           // Filter early
    ->withVat()          // Then apply more filters
    ->limit(10)          // Finally limit results
    ->get();
```

#### Use Pagination for Large Datasets
```php
$page = 2;
$perPage = 10;

$companies = ares()
    ->findMany($ics)
    ->active()
    ->offset(($page - 1) * $perPage)
    ->limit($perPage)
    ->getFormatted();
```

### Error Handling

```php
// Invalid IC returns empty results
$companies = ares()
    ->findMany(['invalid', '12345678'])
    ->active()
    ->get();

// Check if results exist
if (ares()->find('12345678')->active()->exists()) {
    echo "Company is active";
}
```

---

*Previous: [API Reference](api.md) | Next: [Configuration](configuration.md)*

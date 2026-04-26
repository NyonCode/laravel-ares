# Usage Examples

This guide provides comprehensive examples of how to use the Laravel ARES package in your application.

## Basic Usage

### Finding a Company

The most common operation is finding a company by its identification number (IC):

```php
use NyonCode\Ares\Facades\Ares;

// Find company (returns null if not found)
$company = Ares::findCompany('12345678');

if ($company) {
    echo $company->name; // Company name
    echo $company->ic;   // Identification number
    echo $company->dic;  // VAT number (if available)
}
```

### Using Helper Functions

Global helper functions provide convenient shortcuts:

```php
// Get the ARES client
$client = ares();

// Check if company exists and is active
if (ares_is_company_active('12345678')) {
    echo "Company is active!";
}

// Get company address
$address = ares_get_address('12345678');
echo $address; // Formatted address or 'N/A'

// Check if company has VAT number
if (ares_has_vat('12345678')) {
    echo "Company has VAT registration";
}

// Validate IC format
if (ares_validate_ic('12345678')) {
    echo "IC format is valid";
}

// Normalize IC format
$normalized = ares_normalize_ic('123 456 78');
echo $normalized; // '12345678'
```

### Dependency Injection

You can inject the ARES client into your classes:

```php
use NyonCode\Ares\Contracts\AresClientInterface;

class CompanyService
{
    public function __construct(
        private AresClientInterface $ares
    ) {}

    public function getCompanyInfo(string $ic): ?array
    {
        $company = $this->ares->findCompany($ic);
        
        if (!$company) {
            return null;
        }

        return [
            'name' => $company->name,
            'ic' => $company->ic,
            'address' => $company->registeredOffice?->formatted,
            'active' => $company->registration->active,
        ];
    }
}
```

## Advanced Usage

### Exception Handling

Use `findCompanyOrFail()` for automatic exception handling:

```php
use NyonCode\Ares\Facades\Ares;
use NyonCode\Ares\Exceptions\CompanyNotFoundException;
use NyonCode\Ares\Exceptions\InvalidIcException;

try {
    $company = Ares::findCompanyOrFail('12345678');
    echo $company->name;
} catch (InvalidIcException $e) {
    echo "Invalid IC format: " . $e->getMessage();
} catch (CompanyNotFoundException $e) {
    echo "Company not found: " . $e->getMessage();
}
```

### Raw API Data

Access the raw API response data:

```php
$rawData = Ares::findCompanyRaw('12345678');

if ($rawData) {
    // Access raw API fields
    echo $rawData['obchodniJmeno'] ?? 'N/A';
    echo $rawData['ico'] ?? 'N/A';
}
```

### Working with Company Data

The `CompanyData` object provides structured access to company information:

```php
$company = Ares::findCompany('12345678');

if ($company) {
    // Basic information
    echo $company->name;
    echo $company->ic;
    echo $company->dic;
    
    // Registration information
    echo $company->registration->active ? 'Active' : 'Inactive';
    echo $company->registration->dateOfEstablishment;
    echo $company->registration->legalForm;
    echo $company->registration->financialOffice;
    
    // Address information
    echo $company->registeredOffice->street;
    echo $company->registeredOffice->city;
    echo $company->registeredOffice->zipCode;
    echo $company->registeredOffice->formatted; // Full formatted address
    
    // Delivery address (if available)
    if ($company->deliveryAddress) {
        echo $company->deliveryAddress->formatted;
    }
}
```

## Batch Operations

### Validating Multiple ICs

```php
$ics = ['12345678', '87654321', '11223344'];

$results = ares('validateMultipleIcs', $ics);

foreach ($results as $ic => $company) {
    if ($company) {
        echo "IC {$ic}: {$company->name}";
    } else {
        echo "IC {$ic}: Not found or invalid";
    }
}
```

### Filtering Companies

```php
// Assume you have an array of companies
$companies = [
    Ares::findCompany('12345678'),
    Ares::findCompany('87654321'),
    // ... more companies
];

// Filter active companies only
$activeCompanies = ares('filterActiveCompanies', $companies);

// Filter by legal form
$companiesWithSpecificForm = ares('filterByLegalForm', $companies, 's.r.o.');

// Search companies by name
$searchResults = ares('searchByName', $companies, 'Název firmy');
```

### Company Statistics

```php
$companies = [
    Ares::findCompany('12345678'),
    Ares::findCompany('87654321'),
    // ... more companies
];

$stats = ares('getCompanyStatistics', $companies);

echo "Total companies: " . $stats['total'];
echo "Active companies: " . $stats['active'];
echo "Inactive companies: " . $stats['inactive'];
echo "With VAT: " . $stats['with_vat'];
echo "Active percentage: " . $stats['active_percentage'] . '%';
```

## Display Formatting

### Format Company for Display

```php
$company = Ares::findCompany('12345678');

$displayData = ares('formatCompanyByIc', '12345678');

// Or format existing company object
$displayData = ares('formatCompanyForDisplay', $company);

// $displayData contains:
// [
//     'IC' => '12345678',
//     'Name' => 'Company Name',
//     'DIC' => 'CZ12345678',
//     'Status' => 'Active',
//     'Legal Form' => 's.r.o.',
//     'Establishment Date' => '2020-01-01',
//     'Address' => 'Street 123, City, 123 45',
//     'Financial Office' => 'Finanční úřad',
//     'Primary Source' => 'RES'
// ]
```

### Custom Date Formatting

```php
// Get establishment date in custom format
$date = ares('getEstablishmentDateByIc', '12345678', 'd.m.Y');
echo $date; // '01.01.2020'

// Or from company object
$date = ares('getEstablishmentDate', $company, 'F j, Y');
echo $date; // 'January 1, 2020'
```

## Controller Examples

### API Controller

```php
namespace App\Http\Controllers;

use NyonCode\Ares\Facades\Ares;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CompanyApiController extends Controller
{
    public function show(string $ic): JsonResponse
    {
        try {
            $company = Ares::findCompanyOrFail($ic);
            
            return response()->json([
                'success' => true,
                'data' => ares('formatCompanyForDisplay', $company)
            ]);
        } catch (InvalidIcException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid IC format'
            ], 400);
        } catch (CompanyNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Company not found'
            ], 404);
        }
    }
    
    public function validate(Request $request): JsonResponse
    {
        $request->validate(['ic' => 'required|string']);
        
        $isValid = ares_validate_ic($request->ic);
        $normalized = ares_normalize_ic($request->ic);
        
        return response()->json([
            'valid' => $isValid,
            'normalized' => $normalized
        ]);
    }
}
```

### Web Controller

```php
namespace App\Http\Controllers;

use NyonCode\Ares\Facades\Ares;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function search(Request $request)
    {
        $request->validate(['ic' => 'required|string']);
        
        $company = Ares::findCompany($request->ic);
        
        if (!$company) {
            return back()->with('error', 'Company not found');
        }
        
        return view('company.show', [
            'company' => $company,
            'isActive' => ares_is_company_active($request->ic),
            'address' => ares_get_address($request->ic)
        ]);
    }
}
```

## Artisan Command Usage

### Testing ARES Connection

```bash
# Test with a specific IC
php artisan ares:test 12345678

# The command will display:
# Company found:
# +-------------------------+---------------------------+
# | Property                | Value                     |
# +-------------------------+---------------------------+
# | IC                      | 12345678                  |
# | Name                    | Company Name              |
# | DIC                     | CZ12345678                |
# | Primary Source          | RES                       |
# | Date of Establishment   | 2020-01-01                |
# | Financial Office        | Finanční úřad             |
# | Address                 | Street 123, City, 123 45  |
# | Delivery Address        | N/A                       |
# | Legal Form              | s.r.o.                    |
# | Business Register File  | N/A                       |
# +-------------------------+---------------------------+
```

## Caching

The package automatically caches results to reduce API calls. You can control caching behavior:

### Clear Company Cache

```php
// Clear specific company from cache
Ares::forgetCompany('12345678');

// Or using helper
ares()->forgetCompany('12345678');
```

### Cache TTL Configuration

Set cache duration in your configuration:

```php
// config/ares.php
return [
    'cache_ttl' => 3600, // 1 hour
    // or
    'cache_ttl' => 86400, // 24 hours
];
```

---

*Previous: [Installation Guide](installation.md) | Next: [Helper Functions](helpers.md)*

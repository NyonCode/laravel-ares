# Subject Indexing & Autocomplete

The package can index looked-up subjects into a local database table (`ares_subjects`) for fast autocomplete search. This is useful for building typeahead/autocomplete inputs in your application.

## How It Works

1. Subjects are stored in a minimal `ares_subjects` table with only the fields needed for autocomplete: `ic`, `name`, `city`, and `indexed_at`
2. Subjects can be indexed automatically (on every successful `findCompany()` call) or manually via artisan command
3. Search queries run against the local database, not the ARES API, making them fast and reliable

## Setup

Run the migration to create the `ares_subjects` table:

```bash
php artisan migrate
```

The table schema:

| Column | Type | Description |
| --- | --- | --- |
| `ic` | `CHAR(8)` PRIMARY KEY | Company identification number |
| `name` | `VARCHAR(255)` | Company name |
| `city` | `VARCHAR(100)` nullable | City (for disambiguation in autocomplete) |
| `indexed_at` | `TIMESTAMP` | When the record was last indexed |

## Configuration

```php
// config/ares.php
'indexing' => [
    'enabled'    => env('ARES_INDEXING_ENABLED', true),
    'auto_index' => env('ARES_AUTO_INDEX', true),
    'stale_days' => env('ARES_STALE_DAYS', 30),
],
```

| Key | Default | Description |
| --- | --- | --- |
| `indexing.enabled` | `true` | Enable the indexing feature and search |
| `indexing.auto_index` | `true` | Automatically index subjects on successful `findCompany()` calls |
| `indexing.stale_days` | `30` | Number of days before a record is considered stale |

To disable indexing entirely:

```env
ARES_INDEXING_ENABLED=false
```

## Searching

### Using the Facade

```php
use NyonCode\Ares\Facades\Ares;

// Search by company name (substring match)
$results = Ares::search('Asseco');

// Search by IC prefix
$results = Ares::search('2707');

// Limit the number of results
$results = Ares::search('Skoda', 5);
```

### Using the Helper Function

```php
$results = ares_search('Asseco');
$results = ares_search('2707', 5);
```

### Using Dependency Injection

```php
use NyonCode\Ares\Contracts\AresClientInterface;

class AutocompleteController
{
    public function __construct(
        private readonly AresClientInterface $ares,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $results = $this->ares->search(
            $request->string('q'),
            10,
        );

        return response()->json($results);
    }
}
```

### Search Result Format

Each result is a `SubjectData` DTO:

```php
NyonCode\Ares\Data\SubjectData {
    public readonly string $ic;     // '27074358'
    public readonly string $name;   // 'Asseco Central Europe, a.s.'
    public readonly ?string $city;  // 'Praha'
}
```

### Search Behavior

- If the query contains only digits, it searches by IC prefix (`LIKE '2707%'`)
- Otherwise, it searches by name substring (`LIKE '%Asseco%'`)
- Results are ordered by `ic` (for IC search) or `name` (for name search)
- Empty queries return an empty collection

## Auto-indexing

When `indexing.auto_index` is enabled (default), every successful `findCompany()` call dispatches a queued `IndexAresSubject` job. This means your index grows organically as your application looks up companies.

The job runs on your default queue. To process it:

```bash
php artisan queue:work
```

If you use the `sync` queue driver, indexing happens synchronously within the same request.

## Manual Indexing via Artisan

### Index Specific Subjects

```bash
php artisan ares:index 27074358 25596641
```

Each IC is looked up via the ARES API and indexed. The command reports how many were indexed and how many failed.

### Show Indexing Statistics

```bash
php artisan ares:index
```

Displays the total number of indexed subjects and stale record count.

### Refresh Stale Records

```bash
# Use configured stale_days (default: 30)
php artisan ares:index --refresh-stale

# Custom stale threshold
php artisan ares:index --refresh-stale --stale-days=14

# Limit the number of records to refresh per run
php artisan ares:index --refresh-stale --limit=200
```

### Scheduling

Add the refresh command to your application's scheduler for automatic maintenance:

```php
// app/Console/Kernel.php or routes/console.php
$schedule->command('ares:index --refresh-stale')->daily();
```

## Using in API Endpoints

A typical autocomplete endpoint:

```php
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use NyonCode\Ares\Facades\Ares;

Route::get('/api/companies/search', function (Request $request): JsonResponse {
    $request->validate([
        'q' => 'required|string|min:2',
        'limit' => 'integer|min:1|max:50',
    ]);

    $results = Ares::search(
        $request->string('q'),
        $request->integer('limit', 10),
    );

    return response()->json($results);
});
```

Example response:

```json
[
    {"ic": "27074358", "name": "Asseco Central Europe, a.s.", "city": "Praha"},
    {"ic": "27082440", "name": "Asseco Solutions, a.s.", "city": "Praha"}
]
```

## Disabling Indexing

If you don't need autocomplete, disable indexing entirely:

```env
ARES_INDEXING_ENABLED=false
```

When disabled:
- No `IndexAresSubject` jobs are dispatched
- `search()` returns an empty collection
- The `ares:index` command still works for manual operations
- The migration can be skipped

---

*Previous: [Usage Examples](usage.md) | Next: [Helper Functions](helpers.md)*

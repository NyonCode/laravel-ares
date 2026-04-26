# Events Documentation

The Laravel ARES package dispatches events for various operations, allowing you to hook into the lookup process and implement custom logic.

## Available Events

### CompanyLookupSucceeded

Dispatched when a company lookup is successful.

#### Event Class

```php
namespace NyonCode\Ares\Events;

class CompanyLookupSucceeded
{
    public function __construct(
        public CompanyData $company
    ) {}
}
```

#### Properties

- `$company` (`CompanyData`) - The successfully retrieved company data

#### Usage Example

```php
use NyonCode\Ares\Events\CompanyLookupSucceeded;
use Illuminate\Support\Facades\Log;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        CompanyLookupSucceeded::class => [
            CompanyLookupSuccessListener::class,
        ],
    ];
}

class CompanyLookupSuccessListener
{
    public function handle(CompanyLookupSucceeded $event): void
    {
        $company = $event->company;
        
        Log::info("Company lookup successful", [
            'ic' => $company->ic,
            'name' => $company->name,
            'active' => $company->registration->active,
        ]);
        
        // Update local database
        LocalCompany::updateOrCreate(
            ['ic' => $company->ic],
            [
                'name' => $company->name,
                'address' => $company->registeredOffice?->formatted,
                'active' => $company->registration->active,
                'updated_at' => now(),
            ]
        );
    }
}
```

#### Closure Listener

```php
use NyonCode\Ares\Events\CompanyLookupSucceeded;

Event::listen(CompanyLookupSucceeded::class, function ($event) {
    $company = $event->company;
    
    // Send notification
    if (!$company->registration->active) {
        Notification::route('slack', config('services.slack.webhook'))
            ->notify(new InactiveCompanyFound($company));
    }
});
```

### CompanyLookupFailed

Dispatched when a company lookup fails.

#### Event Class

```php
namespace NyonCode\Ares\Events;

class CompanyLookupFailed
{
    public function __construct(
        public string $ic,
        public int $status = 0,
        public ?Throwable $exception = null
    ) {}
}
```

#### Properties

- `$ic` (`string`) - The identification number that was looked up
- `$status` (`int`) - HTTP status code (0 for exceptions)
- `$exception` (`Throwable|null`) - The exception that caused the failure

#### Usage Example

```php
use NyonCode\Ares\Events\CompanyLookupFailed;
use Illuminate\Support\Facades\Log;

class CompanyLookupFailedListener
{
    public function handle(CompanyLookupFailed $event): void
    {
        Log::error("Company lookup failed", [
            'ic' => $event->ic,
            'status' => $event->status,
            'exception' => $event->exception?->getMessage(),
        ]);
        
        // Track failed lookups for monitoring
        FailedLookup::create([
            'ic' => $event->ic,
            'status' => $event->status,
            'error_message' => $event->exception?->getMessage(),
            'occurred_at' => now(),
        ]);
        
        // Send alert for critical failures
        if ($event->status >= 500 || $event->exception) {
            Notification::route('mail', config('alerts.email'))
                ->notify(new AresServiceDown($event));
        }
    }
}
```

#### Handling Specific Failure Types

```php
Event::listen(CompanyLookupFailed::class, function ($event) {
    // Handle HTTP errors
    if ($event->status >= 400) {
        $this->handleHttpError($event);
    }
    
    // Handle exceptions
    if ($event->exception) {
        $this->handleException($event);
    }
    
    // Handle specific status codes
    match ($event->status) {
        404 => $this->handleNotFound($event),
        429 => $this->handleRateLimit($event),
        500 => $this->handleServerError($event),
        default => $this->handleGenericFailure($event),
    };
});
```

## Event Registration

### Service Provider Registration

Register event listeners in your `EventServiceProvider`:

```php
// app/Providers/EventServiceProvider.php

protected $listen = [
    CompanyLookupSucceeded::class => [
        Listeners\LogSuccessfulLookup::class,
        Listeners\UpdateLocalDatabase::class,
    ],
    CompanyLookupFailed::class => [
        Listeners\LogFailedLookup::class,
        Listeners\TrackFailures::class,
        Listeners\SendAlerts::class,
    ],
];
```

### Manual Registration

Register listeners manually:

```php
// app/Providers/AppServiceProvider.php

public function boot(): void
{
    Event::listen(CompanyLookupSucceeded::class, function ($event) {
        // Handle successful lookup
    });
    
    Event::listen(CompanyLookupFailed::class, function ($event) {
        // Handle failed lookup
    });
}
```

### Subscriber Pattern

Use event subscribers for complex logic:

```php
class AresEventSubscriber
{
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            CompanyLookupSucceeded::class,
            [self::class, 'handleSuccessfulLookup']
        );
        
        $events->listen(
            CompanyLookupFailed::class,
            [self::class, 'handleFailedLookup']
        );
    }
    
    public function handleSuccessfulLookup(CompanyLookupSucceeded $event): void
    {
        // Handle success
    }
    
    public function handleFailedLookup(CompanyLookupFailed $event): void
    {
        // Handle failure
    }
}
```

Register the subscriber:

```php
// app/Providers/EventServiceProvider.php

protected $subscribe = [
    AresEventSubscriber::class,
];
```

## Common Use Cases

### Database Synchronization

Keep your local database synchronized with ARES:

```php
class SyncCompanyToLocalDatabase
{
    public function handle(CompanyLookupSucceeded $event): void
    {
        $company = $event->company;
        
        Company::updateOrCreate(
            ['ic' => $company->ic],
            [
                'name' => $company->name,
                'dic' => $company->dic,
                'legal_form' => $company->registration->legalForm,
                'address' => $company->registeredOffice?->formatted,
                'active' => $company->registration->active,
                'established_at' => $company->registration->dateOfEstablishment,
                'financial_office' => $company->registration->financialOffice,
                'raw_data' => $company->rawData,
                'synced_at' => now(),
            ]
        );
    }
}
```

### Monitoring and Alerting

Monitor lookup performance and failures:

```php
class AresMonitoringListener
{
    public function handleSuccessfulLookup(CompanyLookupSucceeded $event): void
    {
        // Record successful lookup metrics
        Metrics::increment('ares.lookups.success');
        Metrics::histogram('ares.lookup.duration', $this->getLookupDuration());
        
        // Check for inactive companies
        if (!$event->company->registration->active) {
            Metrics::increment('ares.companies.inactive');
        }
    }
    
    public function handleFailedLookup(CompanyLookupFailed $event): void
    {
        // Record failure metrics
        Metrics::increment('ares.lookups.failed');
        Metrics::increment('ares.lookups.failed.by_status', ['status' => $event->status]);
        
        // Alert on high failure rates
        if ($this->getFailureRate() > 0.1) { // 10% failure rate
            Notification::route('slack', config('monitoring.slack'))
                ->notify(new HighFailureRateAlert());
        }
    }
}
```

### Caching Strategy

Implement custom caching logic:

```php
class CustomCacheListener
{
    public function handleSuccessfulLookup(CompanyLookupSucceeded $event): void
    {
        $company = $event->company;
        
        // Cache for different durations based on company status
        $ttl = $company->registration->active ? 3600 : 7200; // 1h vs 2h
        
        Cache::put(
            "company:{$company->ic}:formatted",
            $this->formatCompany($company),
            $ttl
        );
    }
    
    public function handleFailedLookup(CompanyLookupFailed $event): void
    {
        // Cache negative lookups to prevent repeated API calls
        if ($event->status === 404) {
            Cache::put("company:{$event->ic}:not_found", true, 1800); // 30 minutes
        }
    }
}
```

### Audit Logging

Maintain audit trails of all lookups:

```php
class AresAuditLogger
{
    public function handleSuccessfulLookup(CompanyLookupSucceeded $event): void
    {
        AuditLog::create([
            'action' => 'company_lookup',
            'status' => 'success',
            'ic' => $event->company->ic,
            'company_name' => $event->company->name,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'occurred_at' => now(),
        ]);
    }
    
    public function handleFailedLookup(CompanyLookupFailed $event): void
    {
        AuditLog::create([
            'action' => 'company_lookup',
            'status' => 'failed',
            'ic' => $event->ic,
            'error_code' => $event->status,
            'error_message' => $event->exception?->getMessage(),
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'occurred_at' => now(),
        ]);
    }
}
```

## Performance Considerations

### Event Queueing

For high-traffic applications, queue event handlers:

```php
class QueueableAresListener implements ShouldQueue
{
    use InteractsWithQueue;
    
    public function handle(CompanyLookupSucceeded $event): void
    {
        // Heavy processing here
        $this->processCompanyData($event->company);
    }
    
    public function failed(CompanyLookupSucceeded $event, Throwable $exception): void
    {
        Log::error("Event processing failed", [
            'ic' => $event->company->ic,
            'exception' => $exception->getMessage(),
        ]);
    }
}
```

### Conditional Event Handling

Only process events under certain conditions:

```php
class ConditionalAresListener
{
    public function handle(CompanyLookupSucceeded $event): void
    {
        // Only process certain legal forms
        if (!in_array($event->company->registration->legalForm, ['s.r.o.', 'a.s.'])) {
            return;
        }
        
        // Only process active companies
        if (!$event->company->registration->active) {
            return;
        }
        
        $this->processCompany($event->company);
    }
}
```

### Batch Processing

Collect events and process them in batches:

```php
class BatchAresProcessor
{
    private array $companies = [];
    
    public function handle(CompanyLookupSucceeded $event): void
    {
        $this->companies[] = $event->company;
        
        if (count($this->companies) >= 100) {
            $this->processBatch();
        }
    }
    
    private function processBatch(): void
    {
        DB::transaction(function () {
            foreach ($this->companies as $company) {
                // Batch insert/update
            }
        });
        
        $this->companies = [];
    }
}
```

## Testing Event Handlers

### Unit Testing

```php
class AresEventListenerTest extends TestCase
{
    public function test_successful_lookup_event(): void
    {
        Event::fake();
        
        $company = CompanyData::fromApiResponse($this->getSampleData());
        event(new CompanyLookupSucceeded($company));
        
        Event::assertDispatched(CompanyLookupSucceeded::class, function ($event) use ($company) {
            return $event->company->ic === $company->ic;
        });
    }
    
    public function test_failed_lookup_event(): void
    {
        Event::fake();
        
        event(new CompanyLookupFailed('12345678', 404));
        
        Event::assertDispatched(CompanyLookupFailed::class, function ($event) {
            return $event->ic === '12345678' && $event->status === 404;
        });
    }
}
```

### Feature Testing

```php
class AresIntegrationTest extends TestCase
{
    public function test_event_fires_on_successful_lookup(): void
    {
        Event::fake();
        
        // Perform lookup
        $company = Ares::findCompany('12345678');
        
        if ($company) {
            Event::assertDispatched(CompanyLookupSucceeded::class);
        }
    }
}
```

---

*Previous: [Configuration](configuration.md) | Next: [FAQ](faq.md)*

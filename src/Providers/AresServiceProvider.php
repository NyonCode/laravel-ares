<?php

declare(strict_types=1);

namespace NyonCode\Ares\Providers;

use Exception;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Log\LogManager;
use NyonCode\Ares\Commands\TestAresCommand;
use NyonCode\Ares\Contracts\AresClientInterface;
use NyonCode\Ares\Services\AresClient;
use NyonCode\LaravelPackageToolkit\Contracts\Packable;
use NyonCode\LaravelPackageToolkit\Exceptions\InvalidLanguageDirectoryException;
use NyonCode\LaravelPackageToolkit\Packager;
use NyonCode\LaravelPackageToolkit\PackageServiceProvider;

final class AresServiceProvider extends PackageServiceProvider implements Packable
{
    /**
     * Configure the package.
     *
     * @throws InvalidLanguageDirectoryException
     * @throws Exception
     */
    public function configure(Packager $packager): void
    {
        $packager
            ->name('laravel-ares')
            ->hasConfig()
            ->hasCommands([
                TestAresCommand::class,
            ])
            ->hasTranslations('resources/lang');
    }

    public function register(): void
    {
        parent::register();

        $this->app->singleton(AresClientInterface::class, function (Application $app): AresClient {
            return new AresClient(
                baseUrl: $this->configString('ares.api_url'),
                cacheTtl: $this->configInt('ares.cache_ttl'),
                logger: $app->make(LogManager::class)->channel($this->configString('ares.log_channel')),
                events: $app->make(Dispatcher::class),
                cache: $app->make(CacheFactory::class)->store(),
                http: $app->make(Http::class),
            );
        });

        $this->app->bind('ares', fn (Application $app) => $app->make(AresClientInterface::class));
    }

    /**
     * @return array<string, string>
     */
    public function aboutData(): array
    {
        return [
            'Client contract' => AresClientInterface::class,
            'Facade alias' => 'Ares',
            'Cache support' => 'enabled',
        ];
    }

    private function configString(string $key): string
    {
        $value = config($key);

        return is_string($value) ? $value : '';
    }

    private function configInt(string $key): int
    {
        $value = config($key);

        return is_int($value) ? $value : (is_numeric($value) ? (int) $value : 0);
    }
}

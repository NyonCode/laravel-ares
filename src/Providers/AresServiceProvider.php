<?php

declare(strict_types=1);

namespace NyonCode\Ares\Providers;

use Exception;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Log\LogManager;
use NyonCode\Ares\Commands\TestAresCommand;
use NyonCode\Ares\Contracts\AresClientInterface;
use NyonCode\Ares\Helpers\AresHelper;
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
            ->hasTranslations('resources/lang')
            ->registeredPackage(function ($packager) {
                $this->app->bind(AresClientInterface::class, function (Application $app): AresClient {
                    return new AresClient(
                        baseUrl: $this->configString('ares.api_url'),
                        cacheTtl: $this->configInt('ares.cache_ttl'),
                        logger: $app->make(LogManager::class)->channel($this->configString('ares.log_channel')),
                        cache: $app->make(CacheFactory::class)->store(),
                        httpTimeout: $this->configFloat('ares.http_options.timeout'),
                        httpConnectTimeout: $this->configFloat('ares.http_options.connect_timeout'),
                    );
                });

                $this->app->bind('ares', fn (Application $app) => $app->make(AresClientInterface::class));
                $this->app->singleton(AresHelper::class, fn () => new AresHelper);
                $this->app->alias(AresHelper::class, 'ares.helper');
            });
    }

    /**
     * Get package information for the about command.
     *
     * @return array<string, string>
     */
    public function aboutData(): array
    {
        return [
            'Author' => 'Ondřej Nyklíček',
            'Client contract' => AresClientInterface::class,
            'Facade alias' => 'Ares',
            'Cache support' => 'enabled',
        ];
    }

    /**
     * Get a string value from configuration.
     *
     * @param  string  $key  The configuration key
     * @return string The configuration value or empty string if not found
     */
    private function configString(string $key): string
    {
        $value = config($key);

        return is_string($value) ? $value : '';
    }

    /**
     * Get an integer value from configuration.
     *
     * @param  string  $key  The configuration key
     * @return int The configuration value or 0 if not found/invalid
     */
    private function configInt(string $key): int
    {
        $value = config($key);

        return is_int($value) ? $value : (is_numeric($value) ? (int) $value : 0);
    }

    /**
     * Get a float value from configuration.
     *
     * @param  string  $key  The configuration key
     * @return float The configuration value or 0.0 if not found/invalid
     */
    private function configFloat(string $key): float
    {
        $value = config($key);

        return is_float($value) || is_int($value) ? (float) $value : (is_numeric($value) ? (float) $value : 0.0);
    }
}

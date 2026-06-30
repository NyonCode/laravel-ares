<?php

declare(strict_types=1);

namespace NyonCode\Ares\Providers;

use Exception;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Log\LogManager;
use NyonCode\Ares\Commands\IndexAresCommand;
use NyonCode\Ares\Commands\TestAresCommand;
use NyonCode\Ares\Contracts\AresClientInterface;
use NyonCode\Ares\Helpers\AresHelper;
use NyonCode\Ares\Services\AresClient;
use NyonCode\Ares\Services\SubjectSearchService;
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
            ->hasMigrations()
            ->hasCommands([
                TestAresCommand::class,
                IndexAresCommand::class,
            ])
            ->hasAbout()
            ->hasTranslations('resources/lang')
            ->registeredPackage(function ($packager) {
                $this->app->singleton(SubjectSearchService::class, fn () => new SubjectSearchService);

                $this->app->bind(AresClientInterface::class, function (Application $app): AresClient {
                    $config = $app->make('config');
                    $indexingEnabled = $config->boolean('ares.indexing.enabled');
                    $cacheEnabled = $config->boolean('ares.cache.enabled');
                    $cacheStore = $config->get('ares.cache.store');

                    return new AresClient(
                        baseUrl: $config->string('ares.api_url'),
                        cacheTtl: $cacheEnabled ? $config->integer('ares.cache.ttl') : 0,
                        logger: $app->make(LogManager::class)->channel($config->string('ares.log_channel')),
                        cache: $app->make(CacheFactory::class)->store(is_string($cacheStore) && $cacheStore !== '' ? $cacheStore : null),
                        httpTimeout: $config->float('ares.http_options.timeout'),
                        httpConnectTimeout: $config->float('ares.http_options.connect_timeout'),
                        autoIndex: $indexingEnabled && $config->boolean('ares.indexing.auto_index'),
                        searchService: $indexingEnabled ? $app->make(SubjectSearchService::class) : null,
                        cachePrefix: $config->string('ares.cache.prefix'),
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
            'Cache support' => $this->app->make('config')->boolean('ares.cache.enabled') ? 'enabled' : 'disabled',
        ];
    }
}

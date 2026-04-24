<?php

declare(strict_types=1);

namespace NyonCode\Ares\Tests;

use NyonCode\Ares\Providers\AresServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [AresServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('app.locale', 'cs');
        $app['config']->set('app.fallback_locale', 'en');
        $app['config']->set('cache.default', 'array');
        $app['config']->set('ares.api_url', 'https://ares.gov.cz/ekonomicke-subjekty-v-be/rest');
        $app['config']->set('ares.cache_ttl', 3600);
        $app['config']->set('ares.log_channel', 'stack');
        $app['config']->set('ares.http_options.timeout', 5.0);
        $app['config']->set('ares.http_options.connect_timeout', 3.0);
    }
}

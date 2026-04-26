<?php

declare(strict_types=1);

use NyonCode\Ares\Contracts\AresClientInterface;
use NyonCode\Ares\Helpers\AresHelper;
use NyonCode\Ares\Services\AresClient;

it('registers the client contract and facade binding', function () {
    expect(app(AresClientInterface::class))->toBeInstanceOf(AresClient::class)
        ->and(app('ares'))->toBeInstanceOf(AresClientInterface::class)
        ->and(app(AresHelper::class))->toBeInstanceOf(AresHelper::class)
        ->and(app('ares.helper'))->toBeInstanceOf(AresHelper::class);
});

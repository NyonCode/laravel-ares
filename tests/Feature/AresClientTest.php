<?php

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use NyonCode\Ares\Contracts\AresClientInterface;
use NyonCode\Ares\Enums\RegistrationSourceState;
use NyonCode\Ares\Events\CompanyLookupFailed;
use NyonCode\Ares\Events\CompanyLookupSucceeded;
use NyonCode\Ares\Exceptions\CompanyNotFoundException;
use NyonCode\Ares\Exceptions\InvalidApiResponseException;
use NyonCode\Ares\Exceptions\InvalidIcException;

it('returns mapped company data and dispatches a success event', function () {
    Event::fake();

    Http::fake([
        'https://ares.gov.cz/*' => Http::response([
            'ico' => '27074358',
            'obchodniJmeno' => 'Asseco Central Europe, a.s.',
            'dic' => 'CZ27074358',
            'pravniForma' => '121',
            'financniUrad' => '004',
            'datumVzniku' => '2003-08-06',
            'datumAktualizace' => '2026-03-08',
            'primarniZdroj' => 'ros',
            'sidlo' => [
                'kodStatu' => 'CZ',
                'nazevStatu' => 'Ceska republika',
                'nazevUlice' => 'Budejovicka',
                'cisloDomovni' => 778,
                'cisloOrientacni' => 3,
                'cisloOrientacniPismeno' => 'a',
                'nazevCastiObce' => 'Michle',
                'obec' => 'Praha',
                'nazevObce' => 'Praha',
                'psc' => 14000,
                'textovaAdresa' => 'Budejovicka 778/3a, Michle, 14000 Praha 4',
            ],
            'adresaDorucovaci' => [
                'radekAdresy1' => 'Budejovicka 778/3a',
                'radekAdresy2' => 'Michle',
                'radekAdresy3' => '14000 Praha 4',
            ],
            'czNace' => ['62', '63'],
            'czNace2008' => ['620', '63'],
            'seznamRegistraci' => [
                'stavZdrojeRos' => 'AKTIVNI',
                'stavZdrojeVr' => 'AKTIVNI',
            ],
            'dalsiUdaje' => [
                [
                    'datovyZdroj' => 'vr',
                    'spisovaZnacka' => 'B 8525/MSPH',
                ],
            ],
        ]),
    ]);

    $company = app(AresClientInterface::class)->findCompany('27074358');

    expect($company)
        ->not->toBeNull()
        ->and($company->ic)->toBe('27074358')
        ->and($company->name)->toBe('Asseco Central Europe, a.s.')
        ->and($company->dic)->toBe('CZ27074358')
        ->and($company->registeredOffice?->formatted)->toBe('Budejovicka 778/3a, Michle, 14000 Praha 4')
        ->and($company->registeredOffice?->houseNumber)->toBe('778/3a')
        ->and($company->deliveryAddress?->lines)->toBe([
            'Budejovicka 778/3a',
            'Michle',
            '14000 Praha 4',
        ])
        ->and($company->registration->financialOffice)->toBe('004')
        ->and($company->registration->dateOfEstablishment)->toBe('2003-08-06')
        ->and($company->registration->primarySource)->toBe('ros')
        ->and($company->registration->businessRegisterFileMark)->toBe('B 8525/MSPH')
        ->and($company->registration->naceCodes)->toBe(['62', '63'])
        ->and($company->registration->sourceStatus('ros')?->status)->toBe(RegistrationSourceState::Active);

    Event::assertDispatched(CompanyLookupSucceeded::class, fn (CompanyLookupSucceeded $event) => $event->company->ic === '27074358');
    Event::assertNotDispatched(CompanyLookupFailed::class);
});

it('caches successful company lookups', function () {
    $requestCount = 0;

    Http::fake(function () use (&$requestCount) {
        $requestCount++;

        return Http::response([
            'ico' => '27074358',
            'obchodniJmeno' => 'Asseco Central Europe, a.s.',
            'sidlo' => [
                'nazevObce' => 'Praha',
            ],
        ]);
    });

    $client = app(AresClientInterface::class);

    $first = $client->findCompany('27074358');
    $second = $client->findCompany('27074358');

    expect($first?->ic)->toBe('27074358')
        ->and($second?->ic)->toBe('27074358')
        ->and($requestCount)->toBe(1);
});

it('dispatches a failed event when the API returns an error response', function () {
    Event::fake();

    Http::fake([
        'https://ares.gov.cz/*' => Http::response([], 404),
    ]);

    $company = app(AresClientInterface::class)->findCompany('27074358');

    expect($company)->toBeNull();

    Event::assertDispatched(
        CompanyLookupFailed::class,
        fn (CompanyLookupFailed $event) => $event->ic === '27074358'
            && $event->httpStatus === 404
            && $event->exception === null
    );
});

it('returns raw api data through the convenience method', function () {
    Http::fake([
        'https://ares.gov.cz/*' => Http::response([
            'ico' => '27074358',
            'obchodniJmeno' => 'Asseco Central Europe, a.s.',
            'dic' => 'CZ27074358',
        ]),
    ]);

    $raw = app(AresClientInterface::class)->findCompanyRaw('27074358');

    expect($raw)->toMatchArray([
        'ico' => '27074358',
        'obchodniJmeno' => 'Asseco Central Europe, a.s.',
        'dic' => 'CZ27074358',
    ]);
});

it('can forget a cached company lookup across normalized ic values', function () {
    $requestCount = 0;

    Http::fake(function () use (&$requestCount) {
        $requestCount++;

        return Http::response([
            'ico' => '27074358',
            'obchodniJmeno' => 'Asseco Central Europe, a.s.',
        ]);
    });

    $client = app(AresClientInterface::class);

    $client->findCompany('27 074 358');
    $client->forgetCompany('27074358');
    $client->findCompany('27074358');

    expect($requestCount)->toBe(2);
});

it('throws a domain exception for invalid ic in the fail-fast method', function () {
    expect(fn () => app(AresClientInterface::class)->findCompanyOrFail('123'))
        ->toThrow(InvalidIcException::class, 'Invalid IC format: 00000123');
});

it('throws a domain exception when a company is not found in the fail-fast method', function () {
    Http::fake([
        'https://ares.gov.cz/*' => Http::response([], 404),
    ]);

    expect(fn () => app(AresClientInterface::class)->findCompanyOrFail('27074358'))
        ->toThrow(CompanyNotFoundException::class, 'Company with IC [27074358] was not found in ARES.');
});

it('handles transport exceptions and dispatches a failed event', function () {
    Event::fake();

    Http::fake(fn () => throw new ConnectionException('Connection refused.'));

    $company = app(AresClientInterface::class)->findCompany('27074358');

    expect($company)->toBeNull();

    Event::assertDispatched(
        CompanyLookupFailed::class,
        fn (CompanyLookupFailed $event) => $event->ic === '27074358'
            && $event->httpStatus === 0
            && $event->exception instanceof ConnectionException
    );
});

it('treats malformed payloads as failed lookups', function () {
    Event::fake();

    Http::fake([
        'https://ares.gov.cz/*' => Http::response([
            'ico' => '27074358',
        ]),
    ]);

    $company = app(AresClientInterface::class)->findCompany('27074358');

    expect($company)->toBeNull();

    Event::assertDispatched(
        CompanyLookupFailed::class,
        fn (CompanyLookupFailed $event) => $event->ic === '27074358'
            && $event->exception instanceof InvalidApiResponseException
    );
});

it('rejects invalid IC values before making an HTTP request', function () {
    Http::fake();

    $company = app(AresClientInterface::class)->findCompany('123');

    expect($company)->toBeNull();
    Http::assertNothingSent();
});

it('validates normalized IC values correctly', function () {
    $client = app(AresClientInterface::class);

    expect($client->isValidIc('27074358'))->toBeTrue()
        ->and($client->isValidIc(' 27 074 358 '))->toBeTrue()
        ->and($client->isValidIc('12345678'))->toBeFalse();
});

it('normalizes ic values for consistent lookups', function () {
    $client = app(AresClientInterface::class);

    expect($client->normalizeIc('27 074 358'))->toBe('27074358')
        ->and($client->normalizeIc('123'))->toBe('00000123');
});

it('recovers from a corrupted cached payload by flushing and refetching it', function () {
    $requestCount = 0;

    Cache::put('ares:v1:company:27074358', 'broken-payload', 3600);

    Http::fake(function () use (&$requestCount) {
        $requestCount++;

        return Http::response([
            'ico' => '27074358',
            'obchodniJmeno' => 'Asseco Central Europe, a.s.',
            'sidlo' => [
                'nazevObce' => 'Praha',
            ],
        ]);
    });

    $company = app(AresClientInterface::class)->findCompany('27074358');

    expect($company?->ic)->toBe('27074358')
        ->and($requestCount)->toBe(1)
        ->and(Cache::get('ares:v1:company:27074358'))->toBeArray();
});

it('recovers from a malformed cached array payload by refetching a fresh response', function () {
    $requestCount = 0;

    Cache::put('ares:v1:company:27074358', [
        'ico' => '27074358',
    ], 3600);

    Http::fake(function () use (&$requestCount) {
        $requestCount++;

        return Http::response([
            'ico' => '27074358',
            'obchodniJmeno' => 'Asseco Central Europe, a.s.',
            'sidlo' => [
                'nazevObce' => 'Praha',
            ],
        ]);
    });

    $company = app(AresClientInterface::class)->findCompany('27074358');

    expect($company?->ic)->toBe('27074358')
        ->and($company?->name)->toBe('Asseco Central Europe, a.s.')
        ->and($requestCount)->toBe(1)
        ->and(Cache::get('ares:v1:company:27074358'))->toMatchArray([
            'ico' => '27074358',
            'obchodniJmeno' => 'Asseco Central Europe, a.s.',
        ]);
});

it('serves raw payload lookups from cache without making an extra request', function () {
    $requestCount = 0;

    Http::fake(function () use (&$requestCount) {
        $requestCount++;

        return Http::response([
            'ico' => '27074358',
            'obchodniJmeno' => 'Asseco Central Europe, a.s.',
            'dic' => 'CZ27074358',
        ]);
    });

    $client = app(AresClientInterface::class);

    $client->findCompany('27074358');
    $raw = $client->findCompanyRaw('27074358');

    expect($raw)->toMatchArray([
        'ico' => '27074358',
        'obchodniJmeno' => 'Asseco Central Europe, a.s.',
        'dic' => 'CZ27074358',
    ])->and($requestCount)->toBe(1);
});

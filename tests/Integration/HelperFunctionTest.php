<?php

declare(strict_types=1);

use NyonCode\Ares\Contracts\AresClientInterface;
use NyonCode\Ares\Data\AddressData;
use NyonCode\Ares\Data\CompanyData;
use NyonCode\Ares\Data\RegistrationData;
use NyonCode\Ares\Data\RegistrationStatusData;
use NyonCode\Ares\Enums\RegistrationSourceState;
use NyonCode\Ares\Helpers\AresFluentBuilder;
use NyonCode\Ares\Helpers\AresHelper;
use NyonCode\Ares\Tests\Fakes\FakeAresClient;

require_once dirname(__DIR__, 2).'/src/helpers.php';

beforeEach(function () {
    $this->fakeAresClient = new FakeAresClient;
    $this->activeCompany = makeCompany();

    app()->instance(AresClientInterface::class, $this->fakeAresClient);
});

it('returns a fluent builder when ares is called without arguments', function () {
    expect(ares())->toBeInstanceOf(AresFluentBuilder::class);
});

it('returns the configured client when ares client is requested', function () {
    expect(ares('client'))->toBe($this->fakeAresClient);
});

it('dispatches helper methods through the ares helper', function () {
    expect(ares('isCompanyActive', $this->activeCompany))->toBeTrue()
        ->and(ares('getLegalForm', $this->activeCompany))->toBe('s.r.o.');
});

it('dispatches client methods through the ares helper', function () {
    $this->fakeAresClient->normalizeMap['123 456 78'] = '12345678';

    expect(ares('normalizeIc', '123 456 78'))->toBe('12345678')
        ->and($this->fakeAresClient->normalizeCalls)->toBe(['123 456 78']);
});

it('throws for unknown helper methods', function () {
    expect(fn () => ares('invalidMethod'))
        ->toThrow(InvalidArgumentException::class, 'Method [invalidMethod] does not exist on AresHelper.');
});

it('proxies direct client calls from the fluent builder', function () {
    $this->fakeAresClient->companiesByIc['12345678'] = $this->activeCompany;

    expect(ares()->findCompany('12345678'))->toBe($this->activeCompany)
        ->and($this->fakeAresClient->findCalls)->toBe(['12345678']);
});

it('supports fluent single company lookups', function () {
    $this->fakeAresClient->companiesByIc['12345678'] = $this->activeCompany;

    $companies = ares()
        ->find('12345678')
        ->active()
        ->get();

    expect($companies)->toHaveCount(1)
        ->and($companies[0])->toBe($this->activeCompany)
        ->and($this->fakeAresClient->findCalls)->toBe(['12345678']);
});

it('supports fluent multi company filtering and formatting', function () {
    $this->fakeAresClient->companiesByIc = [
        '12345678' => $this->activeCompany,
        '87654321' => makeCompany(
            ic: '87654321',
            name: 'Tech Solutions s.r.o.',
            dic: 'CZ87654321',
            legalForm: 's.r.o.',
        ),
        '11223344' => makeCompany(
            ic: '11223344',
            name: 'Legacy Services a.s.',
            dic: null,
            active: false,
            legalForm: 'a.s.',
        ),
    ];

    $companies = ares()
        ->findMany(['12345678', '87654321', '11223344'])
        ->active()
        ->withVat()
        ->legalForm('s.r.o.')
        ->search('Tech')
        ->limit(10)
        ->getFormatted();

    expect($companies)->toHaveCount(1)
        ->and($companies[0]['Name'])->toBe('Tech Solutions s.r.o.')
        ->and($this->fakeAresClient->findCalls)->toBe(['12345678', '87654321', '11223344']);
});

it('supports fluent stats, key extraction and reset', function () {
    $this->fakeAresClient->companiesByIc = [
        '12345678' => $this->activeCompany,
        '87654321' => makeCompany(
            ic: '87654321',
            name: 'Inactive Company',
            active: false,
            dic: null,
        ),
    ];

    $builder = ares()->findMany(['12345678', '87654321']);

    expect($builder->stats())->toMatchArray([
        'total' => 2,
        'active' => 1,
        'inactive' => 1,
        'with_vat' => 1,
        'without_vat' => 1,
    ])->and($builder->names())->toBe(['Test Company', 'Inactive Company'])
        ->and($builder->ics())->toBe(['12345678', '87654321'])
        ->and($builder->keyByIc())->toHaveKeys(['12345678', '87654321']);

    $builder->reset();

    expect($builder->count())->toBe(0)
        ->and($builder->isEmpty())->toBeTrue();
});

it('can forget cached companies through the builder', function () {
    $this->fakeAresClient->companiesByIc['12345678'] = $this->activeCompany;

    ares()->find('12345678')->forget();

    expect($this->fakeAresClient->forgottenIcs)->toBe(['12345678'])
        ->and($this->fakeAresClient->companiesByIc)->not->toHaveKey('12345678');
});

it('exposes helper convenience functions for found companies', function () {
    $this->fakeAresClient->companiesByIc['12345678'] = $this->activeCompany;

    expect(ares_is_company_active('12345678'))->toBeTrue()
        ->and(ares_get_address('12345678'))->toBe('Test Street 123, Test District, 12345 Test City')
        ->and(ares_get_legal_form('12345678'))->toBe('s.r.o.')
        ->and(ares_has_vat('12345678'))->toBeTrue()
        ->and(ares_get_establishment_date('12345678'))->toBe('2020-01-01')
        ->and(ares_format_company('12345678'))->toMatchArray([
            'IC' => '12345678',
            'Name' => 'Test Company',
            'Status' => 'Active',
        ]);
});

it('exposes helper convenience functions with safe defaults for missing companies', function () {
    expect(ares_is_company_active('12345678'))->toBeFalse()
        ->and(ares_get_address('12345678'))->toBe('N/A')
        ->and(ares_get_legal_form('12345678'))->toBe('N/A')
        ->and(ares_has_vat('12345678'))->toBeFalse()
        ->and(ares_get_establishment_date('12345678'))->toBe('N/A')
        ->and(ares_format_company('12345678'))->toBe([]);
});

it('aggregates statistics through the global helper', function () {
    $this->fakeAresClient->companiesByIc = [
        '12345678' => $this->activeCompany,
        '87654321' => makeCompany(
            ic: '87654321',
            name: 'Inactive Company',
            dic: null,
            active: false,
        ),
    ];

    expect(ares_get_company_statistics(['12345678', '87654321']))->toMatchArray([
        'total' => 2,
        'active' => 1,
        'inactive' => 1,
        'with_vat' => 1,
        'without_vat' => 1,
    ]);
});

it('detects active companies from the primary registration source when available', function () {
    $company = makeCompany(sourceStatuses: [
        new RegistrationStatusData(
            source: 'ros',
            rawStatus: 'AKTIVNI',
            status: RegistrationSourceState::Active,
        ),
        new RegistrationStatusData(
            source: 'vr',
            rawStatus: 'HISTORICKY',
            status: RegistrationSourceState::Historical,
        ),
    ]);

    expect(AresHelper::isCompanyActive($company))->toBeTrue();
});

it('falls back to any active source status when primary status is missing', function () {
    $company = makeCompany(
        primarySource: 'missing',
        sourceStatuses: [
            new RegistrationStatusData(
                source: 'vr',
                rawStatus: 'AKTIVNI',
                status: RegistrationSourceState::Active,
            ),
        ],
    );

    expect(AresHelper::isCompanyActive($company))->toBeTrue();
});

it('treats companies as inactive when no source status is active', function () {
    $company = makeCompany(active: false);

    expect(AresHelper::isCompanyActive($company))->toBeFalse();
});

it('resolves the helper from the service container alias', function () {
    expect(app('ares.helper'))->toBeInstanceOf(AresHelper::class)
        ->and(app(AresHelper::class))->toBeInstanceOf(AresHelper::class);
});

function makeCompany(
    string $ic = '12345678',
    string $name = 'Test Company',
    ?string $dic = 'CZ12345678',
    bool $active = true,
    string $legalForm = 's.r.o.',
    ?string $primarySource = 'ros',
    ?array $sourceStatuses = null,
): CompanyData {
    return new CompanyData(
        ic: $ic,
        name: $name,
        dic: $dic,
        dicSkDph: null,
        registeredOffice: new AddressData(
            formatted: 'Test Street 123, Test District, 12345 Test City',
            street: 'Test Street',
            houseNumber: '123',
            district: 'Test District',
            city: 'Test City',
            postalCode: '12345',
            countryCode: 'CZ',
            country: 'Czech Republic',
        ),
        deliveryAddress: null,
        registration: new RegistrationData(
            legalForm: $legalForm,
            financialOffice: 'Financni urad pro Test City',
            dateOfEstablishment: '2020-01-01',
            dateOfLastUpdate: '2026-04-26',
            primarySource: $primarySource,
            businessRegisterFileMark: 'C 12345/MSPH',
            naceCodes: ['62'],
            nace2008Codes: ['620'],
            sourceStatuses: $sourceStatuses ?? [
                new RegistrationStatusData(
                    source: 'ros',
                    rawStatus: $active ? 'AKTIVNI' : 'HISTORICKY',
                    status: $active ? RegistrationSourceState::Active : RegistrationSourceState::Historical,
                ),
            ],
        ),
        rawData: [],
    );
}

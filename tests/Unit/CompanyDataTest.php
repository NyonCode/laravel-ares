<?php

declare(strict_types=1);

use NyonCode\Ares\Data\CompanyData;
use NyonCode\Ares\Enums\RegistrationSourceState;
use NyonCode\Ares\Exceptions\InvalidApiResponseException;

it('maps the api response into structured company data objects', function () {
    $company = CompanyData::fromApiResponse([
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
    ]);

    expect($company->ic)->toBe('27074358')
        ->and($company->name)->toBe('Asseco Central Europe, a.s.')
        ->and($company->dic)->toBe('CZ27074358')
        ->and($company->registeredOffice?->formatted)->toBe('Budejovicka 778/3a, Michle, 14000 Praha 4')
        ->and($company->registeredOffice?->street)->toBe('Budejovicka')
        ->and($company->registeredOffice?->houseNumber)->toBe('778/3a')
        ->and($company->registeredOffice?->district)->toBe('Michle')
        ->and($company->registeredOffice?->city)->toBe('Praha')
        ->and($company->registeredOffice?->postalCode)->toBe('14000')
        ->and($company->deliveryAddress?->lines)->toBe([
            'Budejovicka 778/3a',
            'Michle',
            '14000 Praha 4',
        ])
        ->and($company->registration->legalForm)->toBe('121')
        ->and($company->registration->financialOffice)->toBe('004')
        ->and($company->registration->dateOfEstablishment)->toBe('2003-08-06')
        ->and($company->registration->dateOfLastUpdate)->toBe('2026-03-08')
        ->and($company->registration->primarySource)->toBe('ros')
        ->and($company->registration->businessRegisterFileMark)->toBe('B 8525/MSPH')
        ->and($company->registration->naceCodes)->toBe(['62', '63'])
        ->and($company->registration->nace2008Codes)->toBe(['620', '63'])
        ->and($company->registration->sourceStatus('ros')?->status)->toBe(RegistrationSourceState::Active)
        ->and($company->registration->sourceStatus('vr')?->rawStatus)->toBe('AKTIVNI');
});

it('keeps nullable nested data safe when the api response is partial', function () {
    $company = CompanyData::fromApiResponse([
        'ico' => '27074358',
        'obchodniJmeno' => 'Example Company s.r.o.',
    ]);

    expect($company->registeredOffice)->toBeNull()
        ->and($company->deliveryAddress)->toBeNull()
        ->and($company->dic)->toBeNull()
        ->and($company->registration->naceCodes)->toBe([])
        ->and($company->registration->nace2008Codes)->toBe([])
        ->and($company->registration->sourceStatuses)->toBe([])
        ->and($company->registration->businessRegisterFileMark)->toBeNull()
        ->and($company->registration->legalForm)->toBeNull();
});

it('builds a formatted address when text address is missing', function () {
    $company = CompanyData::fromApiResponse([
        'ico' => '27074358',
        'obchodniJmeno' => 'Example Company s.r.o.',
        'sidlo' => [
            'nazevUlice' => 'Budejovicka',
            'cisloDomovni' => 778,
            'cisloOrientacni' => 3,
            'cisloOrientacniPismeno' => 'a',
            'nazevCastiObce' => 'Michle',
            'nazevObce' => 'Praha',
            'psc' => 14000,
        ],
    ]);

    expect($company->registeredOffice?->formatted)->toBe('Budejovicka 778/3a, Michle, 14000 Praha');
});

it('fails fast on malformed api responses missing required fields', function () {
    expect(fn () => CompanyData::fromApiResponse([
        'ico' => '27074358',
    ]))->toThrow(InvalidApiResponseException::class, 'ARES response is missing required field [obchodniJmeno].');
});

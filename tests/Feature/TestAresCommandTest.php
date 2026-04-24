<?php

declare(strict_types=1);

use NyonCode\Ares\Contracts\AresClientInterface;
use NyonCode\Ares\Data\AddressData;
use NyonCode\Ares\Data\CompanyData;
use NyonCode\Ares\Data\DeliveryAddressData;
use NyonCode\Ares\Data\RegistrationData;
use NyonCode\Ares\Exceptions\CompanyNotFoundException;
use NyonCode\Ares\Exceptions\InvalidIcException;

it('renders company details in the artisan command output', function () {
    $this->app->instance(AresClientInterface::class, new class implements AresClientInterface
    {
        public function findCompany(string $ic): ?CompanyData
        {
            return new CompanyData(
                ic: '27074358',
                name: 'Asseco Central Europe, a.s.',
                dic: 'CZ27074358',
                dicSkDph: null,
                registeredOffice: new AddressData(
                    formatted: 'Budejovicka 778/3a, Michle, 14000 Praha 4',
                    street: 'Budejovicka',
                    houseNumber: '778/3a',
                    district: 'Michle',
                    city: 'Praha',
                    postalCode: '14000',
                    countryCode: 'CZ',
                    country: 'Ceska republika',
                ),
                deliveryAddress: new DeliveryAddressData(
                    lines: [
                        'Budejovicka 778/3a',
                        'Michle',
                        '14000 Praha 4',
                    ],
                    formatted: 'Budejovicka 778/3a, Michle, 14000 Praha 4',
                ),
                registration: new RegistrationData(
                    legalForm: '121',
                    financialOffice: '004',
                    dateOfEstablishment: '2003-08-06',
                    dateOfLastUpdate: '2026-03-08',
                    primarySource: 'ros',
                    businessRegisterFileMark: 'B 8525/MSPH',
                    naceCodes: ['62', '63'],
                    nace2008Codes: ['620', '63'],
                    sourceStatuses: [],
                ),
                rawData: []
            );
        }

        public function findCompanyRaw(string $ic): ?array
        {
            return $this->findCompany($ic)?->rawData;
        }

        public function findCompanyOrFail(string $ic): CompanyData
        {
            return $this->findCompany($ic) ?? throw CompanyNotFoundException::forIc($ic);
        }

        public function forgetCompany(string $ic): bool
        {
            return true;
        }

        public function isValidIc(string $ic): bool
        {
            return true;
        }

        public function normalizeIc(string $ic): string
        {
            return $ic;
        }
    });

    $this->artisan('ares:test', ['ic' => '27074358'])
        ->expectsOutputToContain('Company found:')
        ->expectsTable(
            ['Property', 'Value'],
            [
                ['IC', '27074358'],
                ['Name', 'Asseco Central Europe, a.s.'],
                ['DIC', 'CZ27074358'],
                ['Primary Source', 'ros'],
                ['Date of Establishment', '2003-08-06'],
                ['Financial Office', '004'],
                ['Address', 'Budejovicka 778/3a, Michle, 14000 Praha 4'],
                ['Delivery Address', 'Budejovicka 778/3a, Michle, 14000 Praha 4'],
                ['Legal Form', '121'],
                ['Business Register File Mark', 'B 8525/MSPH'],
            ]
        )
        ->assertExitCode(0);
});

it('renders a translated error when the company is not found', function () {
    $this->app->instance(AresClientInterface::class, new class implements AresClientInterface
    {
        public function findCompany(string $ic): ?CompanyData
        {
            return null;
        }

        public function findCompanyRaw(string $ic): ?array
        {
            return null;
        }

        public function findCompanyOrFail(string $ic): CompanyData
        {
            throw InvalidIcException::forIc($ic);
        }

        public function forgetCompany(string $ic): bool
        {
            return true;
        }

        public function isValidIc(string $ic): bool
        {
            return false;
        }

        public function normalizeIc(string $ic): string
        {
            return $ic;
        }
    });

    $this->artisan('ares:test', ['ic' => '00000000'])
        ->expectsOutputToContain("Subjekt s I\u{010C}O 00000000 nebyl nalezen.")
        ->assertExitCode(1);
});

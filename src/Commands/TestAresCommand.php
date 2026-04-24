<?php

declare(strict_types=1);

namespace NyonCode\Ares\Commands;

use Illuminate\Console\Command;
use NyonCode\Ares\Facades\Ares;

final class TestAresCommand extends Command
{
    protected $signature = 'ares:test {ic : ICO spolecnosti}';

    protected $description = 'Test ARES API communication for the given IC';

    public function handle(): int
    {
        $ic = $this->icArgument();
        $company = Ares::findCompany($ic);

        if (! $company) {
            $this->error(__('laravel-ares::ares.errors.not_found', ['ic' => $ic]));

            return self::FAILURE;
        }

        $this->info('Company found:');
        $this->table(
            ['Property', 'Value'],
            [
                ['IC', $company->ic],
                ['Name', $company->name],
                ['DIC', $company->dic ?? 'N/A'],
                ['Primary Source', $company->registration->primarySource ?? 'N/A'],
                ['Date of Establishment', $company->registration->dateOfEstablishment ?? 'N/A'],
                ['Financial Office', $company->registration->financialOffice ?? 'N/A'],
                ['Address', $company->registeredOffice === null ? 'N/A' : ($company->registeredOffice->formatted ?? 'N/A')],
                ['Delivery Address', $company->deliveryAddress === null ? 'N/A' : ($company->deliveryAddress->formatted ?? 'N/A')],
                ['Legal Form', $company->registration->legalForm ?? 'N/A'],
                ['Business Register File Mark', $company->registration->businessRegisterFileMark ?? 'N/A'],
            ]
        );

        return self::SUCCESS;
    }

    private function icArgument(): string
    {
        $value = $this->argument('ic');

        return is_scalar($value) ? (string) $value : '';
    }
}

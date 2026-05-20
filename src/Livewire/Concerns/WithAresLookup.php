<?php

declare(strict_types=1);

namespace NyonCode\Ares\Livewire\Concerns;

use NyonCode\Ares\Contracts\AresClientInterface;

/**
 * Adds ARES company lookup by IC to a Livewire component.
 *
 * Properties: $aresIc, $aresError, $aresCompany
 * Events:     ares-company-loaded, ares-company-cleared
 *
 * Usage in Blade:
 *   <input wire:model.live.debounce.500ms="aresIc" />
 *   <button wire:click="lookupAres">Vyhledat</button>
 *
 *   @if($aresCompany)
 *       {{ $aresCompany['name'] }} - {{ $aresCompany['ic'] }}
 *
 *   @endif
 */
trait WithAresLookup
{
    public string $aresIc = '';

    public ?string $aresError = null;

    /**
     * @var array{ic: string, name: string, dic: string|null, address: string|null, city: string|null, postalCode: string|null, street: string|null, houseNumber: string|null}|null
     */
    public ?array $aresCompany = null;

    public function lookupAres(): void
    {
        $this->aresError = null;
        $this->aresCompany = null;

        $ic = trim($this->aresIc);

        if ($ic === '') {
            return;
        }

        /** @var AresClientInterface $client */
        $client = app(AresClientInterface::class);

        if (! $client->isValidIc($ic)) {
            $this->aresError = __('laravel-ares::ares.errors.invalid_ic');

            return;
        }

        $company = $client->findCompany($ic);

        if ($company === null) {
            $this->aresError = __('laravel-ares::ares.livewire.company_not_found');

            return;
        }

        $this->aresCompany = [
            'ic' => $company->ic,
            'name' => $company->name,
            'dic' => $company->dic,
            'address' => $company->registeredOffice?->formatted,
            'city' => $company->registeredOffice?->city,
            'postalCode' => $company->registeredOffice?->postalCode,
            'street' => $company->registeredOffice?->street,
            'houseNumber' => $company->registeredOffice?->houseNumber,
        ];

        $this->dispatch('ares-company-loaded', company: $this->aresCompany);
    }

    public function clearAresLookup(): void
    {
        $this->aresIc = '';
        $this->aresError = null;
        $this->aresCompany = null;

        $this->dispatch('ares-company-cleared');
    }
}

<?php

declare(strict_types=1);

namespace NyonCode\Ares\Livewire\Concerns;

use NyonCode\Ares\Contracts\AresClientInterface;
use NyonCode\Ares\Data\SubjectData;

/**
 * Adds ARES subject search (autocomplete) to a Livewire component.
 *
 * Properties: $aresQuery, $aresResults
 * Methods:    selectAresSubject(), clearAresSearch()
 * Events:     ares-subject-selected, ares-subject-cleared
 */
trait WithAresSearch
{
    public string $aresQuery = '';

    public int $aresMinChars = 2;

    public int $aresLimit = 10;

    /**
     * @var array<int, array{ic: string, name: string, city: string|null}>
     */
    public array $aresResults = [];

    public function updatedAresQuery(): void
    {
        $query = trim($this->aresQuery);

        if (mb_strlen($query) < $this->aresMinChars) {
            $this->aresResults = [];

            return;
        }

        /** @var AresClientInterface $client */
        $client = app(AresClientInterface::class);

        $this->aresResults = $client->search($query, $this->aresLimit)
            ->map(fn (SubjectData $s): array => ['ic' => $s->ic, 'name' => $s->name, 'city' => $s->city])
            ->all();
    }

    public function selectAresSubject(string $ic): void
    {
        foreach ($this->aresResults as $subject) {
            if ($subject['ic'] === $ic) {
                $this->aresQuery = $subject['name'];
                $this->aresResults = [];

                $this->dispatch('ares-subject-selected', ic: $subject['ic'], name: $subject['name'], city: $subject['city']);

                return;
            }
        }
    }

    public function clearAresSearch(): void
    {
        $this->aresQuery = '';
        $this->aresResults = [];

        $this->dispatch('ares-subject-cleared');
    }
}

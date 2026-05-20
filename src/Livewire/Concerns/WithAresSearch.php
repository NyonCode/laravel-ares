<?php

declare(strict_types=1);

namespace NyonCode\Ares\Livewire\Concerns;

use Livewire\Attributes\Computed;
use NyonCode\Ares\Contracts\AresClientInterface;
use NyonCode\Ares\Data\SubjectData;

/**
 * Adds ARES subject search (autocomplete) to a Livewire component.
 *
 * Properties: $aresQuery, $aresOpen
 * Computed:   $this->aresResults
 * Events:     ares-subject-selected, ares-subject-cleared
 *
 * Usage in Blade:
 *   <input wire:model.live.debounce.300ms="aresQuery" />
 *
 *   @foreach($this->aresResults as $subject)
 *       <button wire:click="selectAresSubject('{{ $subject->ic }}')">
 *           {{ $subject->name }} ({{ $subject->ic }})
 *       </button>
 *
 *   @endforeach
 */
trait WithAresSearch
{
    public string $aresQuery = '';

    public bool $aresOpen = false;

    public int $aresMinChars = 2;

    public int $aresLimit = 10;

    /**
     * @return array<int, SubjectData>
     */
    #[Computed]
    public function aresResults(): array
    {
        $query = trim($this->aresQuery);

        if (mb_strlen($query) < $this->aresMinChars) {
            return [];
        }

        /** @var AresClientInterface $client */
        $client = app(AresClientInterface::class);

        return $client->search($query, $this->aresLimit)->all();
    }

    public function updatedAresQuery(): void
    {
        $this->aresOpen = mb_strlen(trim($this->aresQuery)) >= $this->aresMinChars;
    }

    public function selectAresSubject(string $ic): void
    {
        foreach ($this->aresResults as $subject) {
            if ($subject->ic === $ic) {
                $this->aresQuery = $subject->name;
                $this->aresOpen = false;

                $this->dispatch('ares-subject-selected', ic: $subject->ic, name: $subject->name, city: $subject->city);

                return;
            }
        }
    }

    public function clearAresSearch(): void
    {
        $this->aresQuery = '';
        $this->aresOpen = false;

        $this->dispatch('ares-subject-cleared');
    }
}

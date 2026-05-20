<?php

declare(strict_types=1);

namespace NyonCode\Ares\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use NyonCode\Ares\Livewire\Concerns\WithAresSearch;

class AresSearch extends Component
{
    use WithAresSearch;

    public function render(): View
    {
        return view('laravel-ares::livewire.ares-search');
    }
}

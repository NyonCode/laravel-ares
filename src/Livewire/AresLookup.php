<?php

declare(strict_types=1);

namespace NyonCode\Ares\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use NyonCode\Ares\Livewire\Concerns\WithAresLookup;

class AresLookup extends Component
{
    use WithAresLookup;

    public function render(): View
    {
        return view('laravel-ares::livewire.ares-lookup');
    }
}

<?php

namespace App\Livewire;

use App\Models\Service;
use Livewire\Attributes\Layout;
use Livewire\Component;

class ServicesComponent extends Component
{
    public $services = [];

    public function mount()
    {
        $this->services = Service::all();
    }

    #[Layout('layouts.app')] 
    public function render()
    {
        return view('livewire.services-component');
    }
}

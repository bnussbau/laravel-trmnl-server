<?php

namespace App\Livewire;

use Livewire\Component;

class DeviceDashboard extends Component
{
    public function render()
    {
        return view('livewire.device-dashboard', ['devices' => auth()->user()->devices()->paginate(10)]);
    }
}

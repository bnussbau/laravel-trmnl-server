<?php

namespace App\Livewire\Actions;

use Livewire\Component;

class DeviceAutoJoin extends Component
{
    public bool $deviceAutojoin = false;

    public function mount()
    {
        $this->deviceAutojoin = auth()->user()->assign_new_devices;
    }

    public function updating($name, $value)
    {
        $this->validate([
            'deviceAutojoin' => 'boolean'
        ]);
        
        if ($name === 'deviceAutojoin') {
            auth()->user()->update([
                'assign_new_devices' => $value
            ]);
        }
    }

    public function render()
    {
        return view('livewire.actions.device-auto-join');
    }
}

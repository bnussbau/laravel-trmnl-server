<?php

namespace App\Livewire;

use App\Models\Device;
use Livewire\Component;
use Livewire\WithPagination;

class DeviceManager extends Component
{
    use WithPagination;

    public $showDeviceForm = false;

    public $name;

    public $mac_address;

    public $api_key;

    public $default_refresh_interval = 900;

    public $friendly_id;

    protected $rules = [
        'mac_address' => 'required',
        'api_key' => 'required',
        'default_refresh_interval' => 'required|integer',
    ];

    public function render()
    {
        return view('livewire.device-manager', [
            'devices' => auth()->user()->devices()->paginate(10),
        ]);
    }

    public function createDevice(): void
    {
        $this->validate();

        Device::factory([
            'name' => $this->name,
            'mac_address' => $this->mac_address,
            'api_key' => $this->api_key,
            'default_refresh_interval' => $this->default_refresh_interval,
            'friendly_id' => $this->friendly_id,
            'user_id' => auth()->id(),
        ])->create();

        $this->reset();
        \Flux::modal('create-device')->close();
        session()->flash('message', 'Device created successfully.');
    }
}

<?php

use Livewire\Volt\Component;

new class extends Component {

    public $device;

    public $name;
    public $api_key;
    public $friendly_id;
    public $mac_address;
    public $default_refresh_interval;

    public function mount(\App\Models\Device $device)
    {
        abort_unless(auth()->user()->devices->contains($device), 403);

        $current_image_uuid = $device->current_screen_image;
        $current_image_path = 'images/generated/' . $current_image_uuid . '.png';

        $this->name = $device->name;
        $this->api_key = $device->api_key;
        $this->friendly_id = $device->friendly_id;
        $this->mac_address = $device->mac_address;
        $this->default_refresh_interval = $device->default_refresh_interval;

        return view('livewire.devices.configure', [
            'image' => ($current_image_uuid) ? url($current_image_path) : null,
        ]);
    }

    public function deleteDevice(\App\Models\Device $device)
    {
        abort_unless(auth()->user()->devices->contains($device), 403);
        $device->delete();

        redirect()->route('devices');
    }

    public function updateDevice()
    {
        abort_unless(auth()->user()->devices->contains($this->device), 403);

        $this->validate([
            'name' => 'required|string|max:255',
            'friendly_id' => 'required|string|max:255',
            'mac_address' => 'required|string|max:255',
            'default_refresh_interval' => 'required|integer|min:1',
        ]);

        $this->device->update([
            'name' => $this->name,
            'friendly_id' => $this->friendly_id,
            'mac_address' => $this->mac_address,
            'default_refresh_interval' => $this->default_refresh_interval,
        ]);

        Flux::modal('edit-device')->close();
    }
}
?>

<div class="bg-muted flex flex-col items-center justify-center gap-6 p-6 md:p-10">
    <div class="flex flex-col gap-6">
        <div
            class="rounded-xl border bg-white dark:bg-stone-950 dark:border-stone-800 text-stone-800 shadow-xs">
            <div class="px-10 py-8 min-w-lg">
                @php
                    $current_image_uuid =$device->current_screen_image;
                    if($current_image_uuid) {
                        file_exists('storage/images/generated/' . $current_image_uuid . '.png') ? $file_extension = 'png' : $file_extension = 'bmp';
                        $current_image_path = 'storage/images/generated/' . $current_image_uuid . '.' . $file_extension;
                    } else {
                        $current_image_path = 'storage/images/setup-logo.bmp';
                    }
                @endphp

                <div class="flex items-center justify-between">
                    <flux:tooltip content="Friendly ID: {{$device->friendly_id}}" position="bottom">
                        <h1 class="text-xl font-medium dark:text-zinc-200">{{ $device->name }}</h1>
                    </flux:tooltip>
                    <div class="flex gap-2">
                        <flux:tooltip content="Last update" position="bottom">
                            <span class="dark:text-zinc-200">{{$device->updated_at->diffForHumans()}}</span>
                        </flux:tooltip>
                        <flux:separator vertical/>
                        <flux:tooltip content="MAC Address" position="bottom">
                            <span class="dark:text-zinc-200">{{$device->mac_address}}</span>
                        </flux:tooltip>
                        @if($device->last_firmware_version)
                            <flux:separator vertical/>
                            <flux:tooltip content="Firmware Version" position="bottom">
                                <span class="dark:text-zinc-200">{{$device->last_firmware_version}}</span>
                            </flux:tooltip>
                        @endif
                        @if($device->wifiStrengh)
                            <flux:separator vertical/>
                            <x-responsive-icons.wifi :strength="$device->wifiStrengh" :rssi="$device->last_rssi_level"
                                                     class="dark:text-zinc-200"/>
                        @endif
                        @if($device->batteryPercent)
                            <flux:separator vertical/>
                            <x-responsive-icons.battery :percent="$device->batteryPercent"/>
                        @endif
                    </div>
                    <div>
                        <flux:modal.trigger name="edit-device">
                            <flux:button icon="pencil-square" variant="subtle"/>
                        </flux:modal.trigger>
                        <flux:modal.trigger name="delete-device">
                            <flux:button icon="trash" variant="danger"/>
                        </flux:modal.trigger>
                    </div>
                </div>


                <flux:modal name="edit-device" class="md:w-96">
                    <div class="space-y-6">
                        <div>
                            <flux:heading size="lg">Edit TRMNL</flux:heading>
                            <flux:subheading></flux:subheading>
                        </div>
                        <!-- @dump($device) -->
                        <flux:input label="Name" wire:model="name"/>

                        <flux:input label="API Key" icon="key" value="{{ $device->api_key }}" type="password"
                                    viewable class="max-w-xs" readonly/>

                        <flux:input label="Friendly ID" wire:model="friendly_id"/>
                        <flux:input label="MAC Address" wire:model="mac_address"/>
                        <flux:input label="Default Refresh Interval (seconds)" wire:model="default_refresh_interval" type="number"/>

                        <div class="flex">
                            <flux:spacer/>

                            <flux:button type="submit" wire:click="updateDevice" variant="primary">Save changes
                            </flux:button>
                        </div>
                    </div>
                </flux:modal>

                <flux:modal name="delete-device" class="min-w-[22rem] space-y-6">
                    <div>
                        <flux:heading size="lg">Delete {{$device->name}}?</flux:heading>
                    </div>

                    <div class="flex gap-2">
                        <flux:spacer/>

                        <flux:modal.close>
                            <flux:button variant="ghost">Cancel</flux:button>
                        </flux:modal.close>
                        <flux:button wire:click="deleteDevice({{ $device->id }})" variant="danger">Delete device
                        </flux:button>
                    </div>
                </flux:modal>


                @if($current_image_path)
                    <flux:separator class="mt-6 mb-6" text="Next Screen"/>
                    <img src="{{ asset($current_image_path) }}" alt="Next Image"/>
                @endif
            </div>
        </div>
    </div>
</div>


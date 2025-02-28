<x-layouts.app>
    <div class="bg-muted flex flex-col items-center justify-center gap-6 p-6 md:p-10">
        <div class="flex flex-col gap-6">
            <div
                class="rounded-xl border bg-white dark:bg-stone-950 dark:border-stone-800 text-stone-800 shadow-xs">
                <div class="px-10 py-8">
                    @php
                        $current_image_uuid =$device->current_screen_image;
                        file_exists('storage/images/generated/' . $current_image_uuid . '.png') ? $file_extension = 'png' : $file_extension = 'bmp';
                        $current_image_path = 'storage/images/generated/' . $current_image_uuid . '.' . $file_extension;
                    @endphp

                    <div class="flex items-center justify-between">
                        <flux:tooltip content="Friendly ID: {{$device->friendly_id}}" position="bottom">
                            <h1 class="text-xl font-medium dark:text-zinc-200">{{ $device->name }}</h1>
                        </flux:tooltip>
                        <div class="flex gap-2">
                            <flux:tooltip content="Last update" position="bottom">
                                <span>{{$device->updated_at->diffForHumans()}}</span>
                            </flux:tooltip>
                            <flux:tooltip content="MAC Address" position="bottom">
                                <span>{{$device->mac_address}}</span>
                            </flux:tooltip>
                            <flux:tooltip content="Firmware Version" position="bottom">
                                <span>{{$device->last_firmware_version}}</span>
                            </flux:tooltip>
                            <x-wifi-icon :strength="$device->wifiStrengh" :rssi="$device->last_rssi_level"/>
                            <x-battery-icon :percent="$device->batteryPercent"/>
                        </div>
                    </div>
                    <flux:input.group class="mt-4 mb-2">
                        <flux:input.group.prefix>API</flux:input.group.prefix>
                        <flux:input icon="key" value="{{ $device->api_key }}" type="password" viewable
                                    class="max-w-xs"/>
                    </flux:input.group>
                    @if($current_image_uuid)
                        <flux:separator class="mt-6 mb-6" text="Next Screen"/>
                        <img src="{{ asset($current_image_path) }}" alt="Next Image"/>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>

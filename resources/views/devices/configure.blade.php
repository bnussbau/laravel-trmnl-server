<x-layouts.app>
    <div class="bg-muted flex flex-col items-center justify-center gap-6 p-6 md:p-10">
        <div class="flex flex-col gap-6">
            <div
                class="rounded-xl border bg-white dark:bg-stone-950 dark:border-stone-800 text-stone-800 shadow-xs">
                <div class="px-10 py-8">
                    @php
                        $current_image_uuid =$device->current_screen_image;
                        $current_image_path = 'images/generated/' . $current_image_uuid . '.png';
                    @endphp

                    <h1 class="text-xl font-medium dark:text-zinc-200">{{ $device->name }}</h1>
                    <p class="text-sm dark:text-zinc-400">{{$device->mac_address}}</p>
                    <p class="text-sm dark:text-zinc-400">Friendly Id: {{$device->friendly_id}}</p>
                    <p class="text-sm dark:text-zinc-400">Refresh Interval: {{$device->default_refresh_interval}}</p>
                    <p class="text-sm dark:text-zinc-400">Battery Voltage: {{$device->last_battery_voltage}}</p>
                    <p class="text-sm dark:text-zinc-400">Wifi RSSI Level: {{$device->last_rssi_level}}</p>
                    <p class="text-sm dark:text-zinc-400">Firmware Version: {{$device->last_firmware_version}}</p>
                    <flux:input.group class="mt-4 mb-2">
                        <flux:input.group.prefix>API Key</flux:input.group.prefix>
                        <flux:input icon="key" value="{{ $device->api_key }}" type="password" viewable  class="max-w-xs"/>
                    </flux:input.group>
                    @if($current_image_uuid)
                        <flux:separator class="mt-6 mb-6"  text="Current Screen" />
                        <img src="{{ asset($current_image_path) }}" alt="Current Image"/>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>


{{--<x-layouts.app>--}}
{{--    <x-slot name="header">--}}
{{--        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">--}}
{{--            {{ __('Device Details: ') }} {{ $device->name }}--}}
{{--        </h2>--}}
{{--    </x-slot>--}}

{{--    <div class="py-12">--}}
{{--        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">--}}
{{--            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">--}}
{{--                <div class="mb-4">--}}
{{--                    <p class="dark:text-gray-100"><strong>Name</strong> {{ $device->name }}</p>--}}
{{--                    <p class="dark:text-gray-100"><strong>Friendly ID</strong> {{ $device->friendly_id }}</p>--}}
{{--                    <p class="dark:text-gray-100"><strong>Mac Address</strong> {{ $device->mac_address }}</p>--}}
{{--                     <p><strong>API Key</strong> <flux:input value="{{ $device->api_key }}" type="password" viewable></flux:input></p>--}}
{{--                    <p class="dark:text-gray-100"><strong>Refresh--}}
{{--                            Interval</strong> {{ $device->default_refresh_interval }}</p>--}}
{{--                    <p class="dark:text-gray-100"><strong>Battery Voltage</strong> {{ $device->last_battery_voltage }}--}}
{{--                    </p>--}}
{{--                    <p class="dark:text-gray-100"><strong>Wifi RSSI Level</strong> {{ $device->last_rssi_level }}</p>--}}
{{--                    <p class="dark:text-gray-100"><strong>Firmware Version</strong> {{ $device->last_firmware_version }}--}}
{{--                    </p>--}}
{{--                </div>--}}
{{--                @if($image)--}}
{{--                    <img src="{{$image}}"/>--}}
{{--                @endif--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    </div>--}}
{{--</x-layouts.app>--}}

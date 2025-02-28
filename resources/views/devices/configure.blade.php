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
                        <div>
                            <flux:modal.trigger name="edit-device">
                                <flux:button icon="key" variant="subtle"/>
                            </flux:modal.trigger>
{{--                            <flux:modal.trigger name="delete-device">--}}
{{--                                <flux:button icon="trash" variant="subtle"/>--}}
{{--                            </flux:modal.trigger>--}}
                        </div>
                        <div class="flex gap-2">
                            <flux:tooltip content="Last update" position="bottom">
                                <span class="dark:text-zinc-200">{{$device->updated_at->diffForHumans()}}</span>
                            </flux:tooltip>
                            <flux:separator vertical/>
                            <flux:tooltip content="MAC Address" position="bottom">
                                <span class="dark:text-zinc-200">{{$device->mac_address}}</span>
                            </flux:tooltip>
                            <flux:separator vertical/>
                            <flux:tooltip content="Firmware Version" position="bottom">
                                <span class="dark:text-zinc-200">{{$device->last_firmware_version}}</span>
                            </flux:tooltip>
                            <flux:separator vertical/>
                            <x-responsive-icons.wifi :strength="$device->wifiStrengh" :rssi="$device->last_rssi_level" class="dark:text-zinc-200"/>
                            <flux:separator vertical/>
                            <x-responsive-icons.battery :percent="$device->batteryPercent"/>
                        </div>
                    </div>


                    <flux:modal name="edit-device" class="md:w-96">
                        <div class="space-y-6">
                            <div>
                                {{--                                <flux:heading size="lg">Edit TRMNL</flux:heading>--}}
                                {{--                                <flux:subheading></flux:subheading>--}}
                            </div>

                            {{--                            <flux:input label="Name" value="{{ $device->name }}"/>--}}

                            <flux:input label="API Key" icon="key" value="{{ $device->api_key }}" type="password"
                                        viewable class="max-w-xs"/>


                            <div class="flex">
                                <flux:spacer/>

                                {{--                                <flux:button type="submit" variant="primary">Save changes</flux:button>--}}
                            </div>
                        </div>
                    </flux:modal>

                    <flux:modal name="delete-device" class="min-w-[22rem] space-y-6">
                        <div>
                            <flux:heading size="lg">Delete {{$device->name}}?</flux:heading>
                        </div>

                        <div class="flex gap-2">
                            <flux:spacer />

                            <flux:modal.close>
                                <flux:button variant="ghost">Cancel</flux:button>
                            </flux:modal.close>

                            <flux:button type="submit" variant="danger">Delete device</flux:button>
                        </div>
                    </flux:modal>


                    @if($current_image_uuid)
                        <flux:separator class="mt-6 mb-6" text="Next Screen"/>
                        <img src="{{ asset($current_image_path) }}" alt="Next Image"/>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>

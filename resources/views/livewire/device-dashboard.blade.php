<div>
    <div class="flex w-full max-w-3xl flex-col gap-6">
        @if($devices->isEmpty())
            <div class="flex flex-col gap-6">
                <div
                    class="rounded-xl border bg-white dark:bg-stone-950 dark:border-stone-800 text-stone-800 shadow-xs">
                    <div class="px-10 py-8">
                        <h1 class="text-xl font-medium dark:text-zinc-200">Add your first device</h1>
                        <flux:button href="{{ route('devices') }}" class="mt-4" icon="plus-circle" variant="primary"
                                     class="w-full mt-4">Add Device
                        </flux:button>
                    </div>
                </div>
            </div>
        @endif

        @foreach($devices as $device)
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
                        @if($current_image_uuid)
                            <flux:separator class="mt-2 mb-4"/>
                            <img src="{{ asset($current_image_path) }}" alt="Current Image"/>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{--    @php--}}
    {{--        $current_image_uuid = auth()->user()?->devices()?->first()?->current_screen_image;--}}
    {{--        $current_image_path = 'images/generated/' . $current_image_uuid . '.png';--}}
    {{--    @endphp--}}
    {{--    @if($current_image_uuid)--}}
    {{--        <h1 class="text-xl font-medium dark:text-zinc-200">TRMNL Giveaway</h1>--}}
    {{--        <p class="text-sm dark:text-zinc-400">D8:3B:DA:F3:C1:DC</p>--}}
    {{--        <flux:separator class="mt-2 mb-4"/>--}}
    {{--        <img src="{{ asset($current_image_path) }}" alt="Current Image"/>--}}
    {{--    @else--}}
    {{--        <h1 class="text-xl font-medium dark:text-zinc-200">Add your first device</h1>--}}
    {{--        <flux:button href="{{ route('devices') }}" class="mt-4"  icon="plus-circle" variant="primary">Add Device</flux:button>--}}
    {{--    @endif--}}
</div>

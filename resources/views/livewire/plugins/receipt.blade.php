<?php

use App\Models\Plugin;
use Livewire\Volt\Component;

new class extends Component {
    public Plugin $plugin;
    public string|null $blade_code;
    public string|null $view_content;

    public string $name;
    public int $data_stale_minutes;
    public string $data_strategy;
    public string $polling_url;
    public string $polling_verb;
    public string|null $polling_header;
    public $data_payload;
    public array $checked_devices = [];
    public string $playlist_name = '';
    public array $selected_weekdays = [];
    public string $active_from = '';
    public string $active_until = '';
    public string $selected_playlist = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->plugins->contains($this->plugin), 403);
        $this->blade_code = $this->plugin->render_markup;

        if ($this->plugin->render_markup_view) {
            try {
                $viewPath = resource_path('views/' . str_replace('.', '/', $this->plugin->render_markup_view) . '.blade.php');
                $this->view_content = file_get_contents($viewPath);
            } catch (\Exception $e) {
                $this->view_content = null;
            }
        }

        $this->fillformFields();
    }

    public function fillFormFields(): void
    {
        $this->name = $this->plugin->name;
        $this->data_stale_minutes = $this->plugin->data_stale_minutes;
        $this->data_strategy = $this->plugin->data_strategy;
        $this->polling_url = $this->plugin->polling_url;
        $this->polling_verb = $this->plugin->polling_verb;
        $this->polling_header = $this->plugin->polling_header;
        $this->data_payload = json_encode($this->plugin->data_payload);
    }

    public function saveMarkup(): void
    {
        abort_unless(auth()->user()->plugins->contains($this->plugin), 403);
        $this->validate();
        $this->plugin->update(['render_markup' => $this->blade_code]);
    }

    protected array $rules = [
        'name' => 'required|string|max:255',
        'data_stale_minutes' => 'required|integer|min:1',
        'data_strategy' => 'required|string|in:polling,webhook',
        'polling_url' => 'required|url',
        'polling_verb' => 'required|string|in:get,post',
        'polling_header' => 'nullable|string|max:255',
        'blade_code' => 'nullable|string',
        'checked_devices' => 'array',
        'playlist_name' => 'required_if:selected_playlist,new|string|max:255',
        'selected_weekdays' => 'array',
        'active_from' => 'nullable|date_format:H:i',
        'active_until' => 'nullable|date_format:H:i',
        'selected_playlist' => 'nullable|string',
    ];

    public function editSettings()
    {
        abort_unless(auth()->user()->plugins->contains($this->plugin), 403);
        $validated = $this->validate();
        $this->plugin->update($validated);
    }

    public function updateData()
    {
        if ($this->plugin->data_strategy === 'polling') {
            $response = Http::get($this->plugin->polling_url)->json();
            $this->plugin->update(['data_payload' => $response]);

            $this->data_payload = json_encode($response);
        }
    }

    public function addToPlaylist()
    {
        $this->validate([
            'checked_devices' => 'required|array|min:1',
            'selected_playlist' => 'required|string',
        ]);

        foreach ($this->checked_devices as $deviceId) {
            $playlist = null;

            if ($this->selected_playlist === 'new') {
                // Create new playlist
                $this->validate([
                    'playlist_name' => 'required|string|max:255',
                ]);

                $playlist = \App\Models\Playlist::create([
                    'device_id' => $deviceId,
                    'name' => $this->playlist_name,
                    'weekdays' => !empty($this->selected_weekdays) ? $this->selected_weekdays : null,
                    'active_from' => $this->active_from ?: null,
                    'active_until' => $this->active_until ?: null,
                ]);
            } else {
                $playlist = \App\Models\Playlist::findOrFail($this->selected_playlist);
            }

            // Add plugin to playlist
            $maxOrder = $playlist->items()->max('order') ?? 0;
            $playlist->items()->create([
                'plugin_id' => $this->plugin->id,
                'order' => $maxOrder + 1,
            ]);
        }

        $this->reset(['checked_devices', 'playlist_name', 'selected_weekdays', 'active_from', 'active_until', 'selected_playlist']);
        Flux::modal('add-plugin')->close();
    }

    public function getDevicePlaylists($deviceId)
    {
        return \App\Models\Playlist::where('device_id', $deviceId)->get();
    }

    public function renderExample(string $example)
    {
        switch ($example) {
            case 'layoutTitle':
                $markup = $this->renderLayoutWithTitleBar();
                break;
            case 'layout':
                $markup = $this->renderLayoutBlank();
                break;
            default:
                $markup = '<h1>Hello World!</h1>';
                break;
        }
        $this->blade_code = $markup;
    }

    public function renderLayoutWithTitleBar(): string
    {
        return <<<HTML
<x-trmnl::view>
    <x-trmnl::layout>
        <!-- ADD YOUR CONTENT HERE-->
    </x-trmnl::layout>
    <x-trmnl::title-bar/>
</x-trmnl::view>
HTML;
    }

    public function renderLayoutBlank(): string
    {
        return <<<HTML
<x-trmnl::view>
    <x-trmnl::layout>
        <!-- ADD YOUR CONTENT HERE-->
    </x-trmnl::layout>
</x-trmnl::view>
HTML;
    }
}

?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold dark:text-gray-100">{{$plugin->name}}</h2>
            <flux:modal.trigger name="add-plugin">
                <flux:button icon="play" variant="primary">Add to Playlist</flux:button>
            </flux:modal.trigger>
        </div>

        <flux:modal name="add-plugin" class="md:w-96">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Add to Playlist</flux:heading>
                </div>

                <form wire:submit="addToPlaylist">
                    <div class="mb-4">
                        <flux:checkbox.group wire:model.live="checked_devices" label="Select Devices">
                            @foreach(auth()->user()->devices as $device)
                                <flux:checkbox label="{{ $device->name }}" value="{{ $device->id }}"/>
                            @endforeach
                        </flux:checkbox.group>
                    </div>

                    @if(count($checked_devices) === 1)
                        <div class="mb-4">
                            <flux:radio.group wire:model="selected_playlist" label="Select Playlist"
                                              variant="segmented">
                                <flux:radio value="new" label="Create New"/>
                                @foreach($this->getDevicePlaylists($checked_devices[0]) as $playlist)
                                    <flux:radio value="{{ $playlist->id }}" label="{{ $playlist->name }}"/>
                                @endforeach
                            </flux:radio.group>
                        </div>

                        @if($selected_playlist === 'new')
                            <div class="mb-4">
                                <flux:input label="Playlist Name" wire:model="playlist_name"/>
                            </div>

                            <div class="mb-4">
                                <flux:checkbox.group wire:model="selected_weekdays" label="Active Days">
                                    <flux:checkbox label="Monday" value="1"/>
                                    <flux:checkbox label="Tuesday" value="2"/>
                                    <flux:checkbox label="Wednesday" value="3"/>
                                    <flux:checkbox label="Thursday" value="4"/>
                                    <flux:checkbox label="Friday" value="5"/>
                                    <flux:checkbox label="Saturday" value="6"/>
                                    <flux:checkbox label="Sunday" value="0"/>
                                </flux:checkbox.group>
                            </div>

                            <div class="mb-4">
                                <flux:input type="time" label="Active From" wire:model="active_from"/>
                            </div>

                            <div class="mb-4">
                                <flux:input type="time" label="Active Until" wire:model="active_until"/>
                            </div>
                        @endif
                    @endif

                    <div class="flex">
                        <flux:spacer/>
                        <flux:button type="submit" variant="primary">Add to Playlist</flux:button>
                    </div>
                </form>
            </div>
        </flux:modal>

        <div class="mt-5 mb-5">
            <h3 class="text-xl font-semibold dark:text-gray-100">Settings</h3>
        </div>
        <div class="grid lg:grid-cols-2 lg:gap-8">
            <div>
                <form wire:submit="editSettings" class="mb-6">
                    <div class="mb-4">
                        <flux:input label="Name" wire:model="name" id="name" class="block mt-1 w-full" type="text"
                                    name="name" autofocus/>
                    </div>

                    <div class="mb-4">
                        <flux:input label="Data is stale after minutes" wire:model="data_stale_minutes"
                                    id="data_stale_minutes"
                                    class="block mt-1 w-full" type="number" name="data_stale_minutes" autofocus/>
                    </div>

                    <div class="mb-4">
                        <flux:radio.group wire:model="data_strategy" label="Data Strategy" variant="segmented">
                            <flux:radio value="polling" label="Polling"/>
                            <flux:radio value="webhook" label="Webhook" disabled/>
                        </flux:radio.group>
                    </div>

                    <div class="mb-4">
                        <flux:input label="Polling URL" wire:model="polling_url" id="polling_url"
                                    placeholder="https://example.com/api"
                                    class="block mt-1 w-full" type="text" name="polling_url" autofocus>
                            <x-slot name="iconTrailing">
                                <flux:button size="sm" variant="subtle" icon="cloud-arrow-down" wire:click="updateData"
                                             tooltip="Fetch data now" class="-mr-1"/>
                            </x-slot>
                        </flux:input>
                    </div>

                    <div class="mb-4">
                        <flux:radio.group wire:model="polling_verb" label="Polling Verb" variant="segmented">
                            <flux:radio value="get" label="GET"/>
                            <flux:radio value="post" label="POST"/>
                        </flux:radio.group>
                    </div>

                    <div class="mb-4">
                        <flux:input label="Polling Header" wire:model="polling_header" id="polling_header"
                                    class="block mt-1 w-full" type="text" name="polling_header" autofocus/>
                    </div>

                    <div class="flex">
                        <flux:spacer/>
                        <flux:button type="submit" variant="primary">Save</flux:button>
                    </div>
                </form>
            </div>
            <div>
                <flux:textarea label="Data Payload" wire:model="data_payload" id="data_payload"
                               class="block mt-1 w-full font-mono" type="text" name="data_payload"
                               readonly rows="24"/>
            </div>
        </div>
        <flux:separator/>
        <div class="mt-5 mb-5 ">
            <h3 class="text-xl font-semibold dark:text-gray-100">Markup</h3>
            @if($plugin->render_markup_view)
                <div>
                    Edit view
                    <span class="font-mono text-accent mb-4">{{ $plugin->render_markup_view }}</span> to update.
                </div>
                <div class="mb-4 mt-4">
                    <flux:textarea
                        label="File Content"
                        class="font-mono"
                        wire:model="view_content"
                        id="view_content"
                        name="view_content"
                        rows="15"
                        readonly
                    />
                </div>
            @else
                <div class="text-accent">
                    <a href="#" wire:click="renderExample('layoutTitle')" class="text-xl">Layout with Title Bar</a> |
                    <a href="#" wire:click="renderExample('layout')" class="text-xl">Blank Layout</a>
                </div>
            @endif
        </div>
        @if(!$plugin->render_markup_view)
            <form wire:submit="saveMarkup">
                <div class="mb-4">
                    <flux:textarea
                        label="Blade Code"
                        class="font-mono"
                        wire:model="blade_code"
                        id="blade_code"
                        name="blade_code"
                        rows="15"
                        placeholder="Enter your blade code here..."
                    />
                </div>

                <div class="flex">
                    <flux:button type="submit" variant="primary">
                        Save
                    </flux:button>
                </div>
            </form>
        @endif
    </div>
</div>

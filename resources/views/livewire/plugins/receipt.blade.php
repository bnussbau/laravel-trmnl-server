<?php

use App\Models\Plugin;
use Livewire\Volt\Component;

new class extends Component {
    public Plugin $plugin;
    public string|null $blade_code;

    public string $name;
    public int $data_stale_minutes;
    public string $data_strategy;
    public string $polling_url;
    public string $polling_verb;
    public string|null $polling_header;
    public $data_payload;

    public function mount(): void
    {
        abort_unless(auth()->user()->plugins->contains($this->plugin), 403);
        $this->blade_code = $this->plugin->render_markup;

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
        'blade_code' => 'string',
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
            <div class="text-accent">
                <a href="#" wire:click="renderExample('layoutTitle')" class="text-xl">Layout with Title Bar</a> |
                <a href="#" wire:click="renderExample('layout')" class="text-xl">Blank Layout</a>
            </div>
        </div>
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
    </div>
</div>

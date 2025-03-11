<?php

use Livewire\Volt\Component;

new class extends Component {

    public string $name;
    public int $data_stale_minutes = 60;
    public string $data_strategy = "polling";
    public string $polling_url;
    public string $polling_verb ="get";
    public $polling_header;
    public array $plugins;

    public array $native_plugins = [
        'markup' =>
            ['name' => 'Markup', 'flux_icon_name' => 'code-bracket', 'detail_view_route' => 'plugins.markup'],
        'api' =>
            ['name' => 'API', 'flux_icon_name' => 'braces', 'detail_view_route' => 'plugins.api'],
    ];

    protected $rules = [
        'name' => 'required|string|max:255',
        'data_stale_minutes' => 'required|integer|min:1',
        'data_strategy' => 'required|string|in:polling,webhook',
        'polling_url' => 'required|url',
        'polling_verb' => 'required|string|in:get,post',
        'polling_header' => 'nullable|string|max:255',
    ];

    public function addPlugin(): void
    {
        abort_unless(auth()->user() !== null, 403);
        $this->validate();

        \App\Models\Plugin::create([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'user_id' => auth()->id(),
            'name' => $this->name,
            'data_stale_minutes' => $this->data_stale_minutes,
            'data_strategy' => $this->data_strategy,
            'polling_url' => $this->polling_url,
            'polling_verb' => $this->polling_verb,
            'polling_header' => $this->polling_header,
        ]);

        $this->reset(['name', 'data_stale_minutes', 'data_strategy', 'polling_url', 'polling_verb', 'polling_header']);
        Flux::modal('add-plugin')->close();
    }



    public function mount(): void
    {
        $userPlugins =auth()->user()->plugins->map(function ($plugin) {
            return $plugin->toArray();
        })->toArray();

        $this->plugins = array_merge($this->native_plugins, $userPlugins);
    }

};
?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold dark:text-gray-100">Plugins</h2>
            <flux:modal.trigger name="add-plugin">
                <flux:button icon="plus" variant="primary">Add Plugin</flux:button>
            </flux:modal.trigger>
        </div>

        <flux:modal name="add-plugin" class="md:w-96">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Add Plugin</flux:heading>
                </div>

                <form wire:submit="addPlugin">
                    <div class="mb-4">
                        <flux:input label="Name" wire:model="name" id="name" class="block mt-1 w-full" type="text"
                                    name="name" autofocus/>
                    </div>

                    <div class="mb-4">
                        <flux:input label="Data is stale after minutes" wire:model="data_stale_minutes" id="data_stale_minutes"
                                    class="block mt-1 w-full" type="number" name="data_stale_minutes" autofocus/>
                    </div>

                    <div class="mb-4">
                        <flux:radio.group wire:model="data_strategy" label="Data Strategy" variant="segmented">
                            <flux:radio value="polling" label="Polling"/>
                            <flux:radio value="webhook" label="Webhook" />
                        </flux:radio.group>
                    </div>

                    <div class="mb-4">
                        <flux:input label="Polling URL" wire:model="polling_url" id="polling_url" placeholder="https://example.com/api"
                                    class="block mt-1 w-full" type="text" name="polling_url" autofocus/>
                    </div>

                    <div class="mb-4">
                        <flux:radio.group wire:model="polling_verb" label="Polling Verb"  variant="segmented">
                            <flux:radio value="get" label="GET"/>
                            <flux:radio value="post" label="POST" />
                        </flux:radio.group>
                    </div>

                    <div class="mb-4">
                        <flux:input label="Polling Header" wire:model="polling_header" id="polling_header"
                                    class="block mt-1 w-full" type="text" name="polling_header" autofocus/>
                    </div>

                    <div class="flex">
                        <flux:spacer/>
                        <flux:button type="submit" variant="primary">Create Plugin</flux:button>
                    </div>
                </form>
            </div>
        </flux:modal>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            @foreach($plugins as $plugin)
                <div
                    class="rounded-xl border bg-white dark:bg-stone-950 dark:border-stone-800 text-stone-800 shadow-xs">
                    <a href="{{ ($plugin['detail_view_route']) ? route($plugin['detail_view_route']) : route('plugins.receipt', ['plugin' => $plugin['id']]) }}" class="block">
                        <div class="flex items-center space-x-4 px-10 py-8">
                            <flux:icon name="{{$plugin['flux_icon_name'] ?? 'puzzle-piece'}}" class="text-4xl text-accent"/>
                            <h3 class="text-xl font-medium dark:text-zinc-200">{{$plugin['name']}}</h3>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</div>

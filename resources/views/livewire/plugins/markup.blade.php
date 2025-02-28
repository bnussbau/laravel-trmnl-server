<?php

use App\Jobs\GenerateScreenJob;
use Livewire\Volt\Component;

new class extends Component {

    public string $blade_code = '';
    public bool $isLoading = false;


    public function submit()
    {
        $this->isLoading = true;

        $this->validate([
            'blade_code' => 'required|string'
        ]);

        try {
            $rendered = Blade::render($this->blade_code);
            GenerateScreenJob::dispatchSync(auth()->user()->devices()->first()->id, $rendered);

        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'An error occurred while submitting the code.'
            ]);
        }

        $this->isLoading = false;
    }

    public function renderExample(string $example)
    {
        switch ($example) {
            case 'quote':
                $markup = $this->renderQuote();
                break;
            case 'trainMonitor':
                $markup = $this->renderTrainMonitor();
                break;
            case 'homeAssistant':
                $markup = $this->renderHomeAssistant();
                break;
            default:
                $markup = '<h1>Hello World!</h1>';
                break;
        }
        $this->blade_code = $markup;
    }

    public function renderQuote(): string
    {
        return <<<HTML
<x-trmnl::view>
    <x-trmnl::layout>
        <x-trmnl::markdown gapSize="large">
            <x-trmnl::title>Motivational Quote</x-trmnl::title>
            <x-trmnl::content>“I love inside jokes. I hope to be a part of one someday.”</x-trmnl::content>
            <x-trmnl::label variant="underline">Michael Scott</x-trmnl::label>
        </x-trmnl::markdown>
    </x-trmnl::layout>
    <x-trmnl::title-bar/>
</x-trmnl::view>
HTML;
    }

    public function renderTrainMonitor()
    {
        return <<<HTML
<x-trmnl::view>
    <x-trmnl::layout>
        <x-trmnl::table>
            <thead>
            <tr>
                <th><x-trmnl::title>Abfahrt</x-trmnl::title></th>
                <th><x-trmnl::title>Aktuell</x-trmnl::title></th>
                <th><x-trmnl::title>Zug</x-trmnl::title></th>
                <th><x-trmnl::title>Ziel</x-trmnl::title></th>
                <th><x-trmnl::title>Steig</x-trmnl::title></th>
            </tr>
            </thead>
            <tbody>
                <tr>
                  <td><x-trmnl::label>08:51</x-trmnl::label></td>
                  <td><x-trmnl::label>08:52</x-trmnl::label></td>
                  <td><x-trmnl::label>REX 1</x-trmnl::label></td>
                  <td><x-trmnl::label>Vienna Main Station</x-trmnl::label></td>
                  <td><x-trmnl::label>3</x-trmnl::label></td>
                </tr>
            </tbody>
        </x-trmnl::table>
    </x-trmnl::layout>
    <x-trmnl::title-bar title="Train Monitor"/>
</x-trmnl::view>
HTML;

    }

    public function renderHomeAssistant()
    {
        return <<<HTML
<x-trmnl::view>
    <x-trmnl::layout class="layout--col gap--space-between">
        <x-trmnl::grid cols="4">
            <x-trmnl::col position="center">
                <x-trmnl::item>
                    <x-trmnl::meta/>
                    <x-trmnl::content>
                        <x-trmnl::value size="large">23.3°</x-trmnl::value>
                        <x-trmnl::label class="w--full flex">
                            <flux:icon icon="droplet"/>
                            47.52 %
                        </x-trmnl::label>
                        <x-trmnl::label class="w--full flex">Sensor 1</x-trmnl::label>
                    </x-trmnl::content>
                </x-trmnl::item>
            </x-trmnl::col>
        </x-trmnl::grid>
    </x-trmnl::layout>
    <x-trmnl::title-bar title="Home Assistant"/>
</x-trmnl::view>
HTML;

    }


};
?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <h2 class="text-2xl font-semibold dark:text-gray-100">Markup</h2>

        {{--        <div class="flex justify-between items-center mb-6">--}}

        <div class="mt-5 mb-5 ">
            <span>Examples</span>
            <div class="text-accent">
                <a href="#" wire:click="renderExample('quote')" class="text-xl">Quote</a> |
                <a href="#" wire:click="renderExample('trainMonitor')" class="text-xl">Train Monitor</a> |
                <a href="#" wire:click="renderExample('homeAssistant')" class="text-xl">Temperature Sensors</a>
            </div>
        </div>
        <form wire:submit="submit">
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
                <flux:spacer/>
                <flux:button type="submit" variant="primary">
                    Generate Screen
                </flux:button>
            </div>
        </form>

        {{--        </div>--}}
    </div>
</div>

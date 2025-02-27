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

    public function renderExample()
    {
        $this->blade_code = <<<HTML
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

};
?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <h2 class="text-2xl font-semibold dark:text-gray-100">Markup</h2>

        {{--        <div class="flex justify-between items-center mb-6">--}}
        <div class="mt-5 mb-5 text-accent">
            <a href="#" wire:click="renderExample" class="text-xl">Quote Example</a>
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

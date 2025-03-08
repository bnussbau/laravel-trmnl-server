<?php

use Livewire\Volt\Component;

new class extends Component {
    public $token;

    public function mount(): void
    {
        $token = Auth::user()?->tokens()?->first();
        if ($token === null) {
            $token = Auth::user()->createToken('api-token', ['update-screen']);
        }
        $this->token = $token->plainTextToken;
    }

    public function regenerateToken()
    {
        Auth::user()->tokens()?->first()?->delete();
        $token = Auth::user()->createToken('api-token', ['update-screen']);
        $this->token = $token->plainTextToken;
    }
};
?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold dark:text-gray-100">API</h2>

        </div>
        <div>
            <p>
                <flux:badge>POST</flux:badge>
                <span class="ml-2 font-mono">{{route('display.update')}}?device_id=</span>
            </p>
            <div class="mt-4">
                <h3 class="text-lg">Headers</h3>
                <div>Authorization <span class="ml-2 font-mono">Bearer {{$token ?? '**********'}}</span>
                    <flux:button variant="subtle" size="xs" class="mt-2" wire:click="regenerateToken()">
                        Regenerate Token
                    </flux:button>
                </div>
            </div>

            <div class="mt-4">
                <h3 class="text-lg">Body</h3>
                <div class="font-mono">
                    <pre>
{&#x22;markup&#x22;:&#x22;&#x3C;h1&#x3E;Hello World&#x3C;/h1&#x3E;&#x22;}
                    </pre>
                </div>
            </div>
        </div>
    </div>
</div>

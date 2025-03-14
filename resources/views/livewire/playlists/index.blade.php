<?php

use App\Models\Device;
use App\Models\Playlist;
use App\Models\PlaylistItem;
use Livewire\Volt\Component;

new class extends Component {
    public $devices;
    public $playlists;

    // Playlist form properties
    public $playlist_name;
    public $selected_weekdays = [];
    public $active_from;
    public $active_until;

    public function mount()
    {
        $this->devices = auth()->user()->devices()->with(['playlists.items.plugin'])->get();
        return view('livewire.playlists.index');
    }

    public function togglePlaylistActive(Playlist $playlist)
    {
        abort_unless(auth()->user()->devices->contains($playlist->device), 403);
        $playlist->update(['is_active' => !$playlist->is_active]);
        $this->devices = auth()->user()->devices()->with(['playlists.items.plugin'])->get();
    }

    public function movePlaylistItemUp(PlaylistItem $item)
    {
        abort_unless(auth()->user()->devices->contains($item->playlist->device), 403);
        $previousItem = $item->playlist->items()
            ->where('order', '<', $item->order)
            ->orderBy('order', 'desc')
            ->first();

        if ($previousItem) {
            $tempOrder = $previousItem->order;
            $previousItem->update(['order' => $item->order]);
            $item->update(['order' => $tempOrder]);
            $this->devices = auth()->user()->devices()->with(['playlists.items.plugin'])->get();
        }
    }

    public function movePlaylistItemDown(PlaylistItem $item)
    {
        abort_unless(auth()->user()->devices->contains($item->playlist->device), 403);
        $nextItem = $item->playlist->items()
            ->where('order', '>', $item->order)
            ->orderBy('order')
            ->first();

        if ($nextItem) {
            $tempOrder = $nextItem->order;
            $nextItem->update(['order' => $item->order]);
            $item->update(['order' => $tempOrder]);
            $this->devices = auth()->user()->devices()->with(['playlists.items.plugin'])->get();
        }
    }

    public function togglePlaylistItemActive(PlaylistItem $item)
    {
        abort_unless(auth()->user()->devices->contains($item->playlist->device), 403);
        $item->update(['is_active' => !$item->is_active]);
        $this->devices = auth()->user()->devices()->with(['playlists.items.plugin'])->get();
    }

    public function deletePlaylist(Playlist $playlist)
    {
        abort_unless(auth()->user()->devices->contains($playlist->device), 403);
        $playlist->delete();
        $this->devices = auth()->user()->devices()->with(['playlists.items.plugin'])->get();
        Flux::modal('delete-playlist-' . $playlist->id)->close();
    }

    public function deletePlaylistItem(PlaylistItem $item)
    {
        abort_unless(auth()->user()->devices->contains($item->playlist->device), 403);
        $item->delete();
        $this->devices = auth()->user()->devices()->with(['playlists.items.plugin'])->get();
        Flux::modal('delete-playlist-item-' . $item->id)->close();
    }

    public function editPlaylist(Playlist $playlist)
    {
        abort_unless(auth()->user()->devices->contains($playlist->device), 403);

        $this->validate([
            'playlist_name' => 'required|string|max:255',
            'selected_weekdays' => 'array',
            'active_from' => 'nullable|date_format:H:i',
            'active_until' => 'nullable|date_format:H:i',
        ]);

        $playlist->update([
            'name' => $this->playlist_name,
            'weekdays' => $this->selected_weekdays,
            'active_from' => $this->active_from,
            'active_until' => $this->active_until,
        ]);

        $this->devices = auth()->user()->devices()->with(['playlists.items.plugin'])->get();
        $this->reset(['playlist_name', 'selected_weekdays', 'active_from', 'active_until']);
        Flux::modal('edit-playlist-' . $playlist->id)->close();
    }

    public function preparePlaylistEdit(Playlist $playlist)
    {
        $this->playlist_name = $playlist->name;
        $this->selected_weekdays = $playlist->weekdays ?? [];
        $this->active_from = optional($playlist->active_from)->format('H:i');
        $this->active_until = optional($playlist->active_until)->format('H:i');
    }
}; ?>

<div>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-semibold dark:text-gray-100">Playlists</h2>
            </div>

            @foreach($devices as $device)
                @if($device->playlists->isNotEmpty())
                    <div class="mb-8">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium dark:text-zinc-200">{{ $device->name }}</h3>
                            <flux:button href="{{ route('devices.configure', $device) }}" wire:navigate icon="eye">
                            </flux:button>
                        </div>

                        <div class="grid gap-6">
                            @foreach($device->playlists as $playlist)
                                <div class="rounded-lg border dark:border-zinc-700 p-4">
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center gap-4">
                                            <h4 class="text-lg font-medium dark:text-zinc-200">{{ $playlist->name }}</h4>
                                            <flux:switch wire:model.live="playlist.is_active"
                                                         wire:click="togglePlaylistActive({{ $playlist->id }})"
                                                         :checked="$playlist->is_active"/>
                                        </div>
                                        <div class="flex items-center gap-4">
                                            <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                                                @if($playlist->weekdays)
                                                    <span>{{ implode(', ', collect($playlist->weekdays)->map(fn($day) => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'][$day])->toArray()) }}</span>
                                                @endif
                                                @if($playlist->active_from && $playlist->active_until)
                                                    <flux:separator vertical/>
                                                    <span>{{ $playlist->active_from->format('H:i') }} - {{ $playlist->active_until->format('H:i') }}</span>
                                                @endif
                                            </div>
                                            <div class="flex gap-2">
                                                <flux:modal.trigger name="edit-playlist-{{ $playlist->id }}">
                                                    <flux:button icon="pencil-square" variant="subtle" size="sm" wire:click="preparePlaylistEdit({{ $playlist->id }})"/>
                                                </flux:modal.trigger>
                                                <flux:modal.trigger name="delete-playlist-{{ $playlist->id }}">
                                                    <flux:button icon="trash" size="sm"/>
                                                </flux:modal.trigger>
                                            </div>
                                        </div>
                                    </div>

                                    <table class="w-full" data-flux-table>
                                        <thead data-flux-columns>
                                            <tr>
                                                <th class="py-3 px-3 first:pl-0 last:pr-0 text-left text-sm font-medium text-zinc-800 dark:text-white"
                                                    data-flux-column>
                                                    <div class="whitespace-nowrap flex">Plugin</div>
                                                </th>
                                                <th class="py-3 px-3 first:pl-0 last:pr-0 text-left text-sm font-medium text-zinc-800 dark:text-white"
                                                    data-flux-column>
                                                    <div class="whitespace-nowrap flex">Status</div>
                                                </th>
                                                <th class="py-3 px-3 first:pl-0 last:pr-0 text-right text-sm font-medium text-zinc-800 dark:text-white"
                                                    data-flux-column>
                                                    <div class="whitespace-nowrap flex justify-end">Actions</div>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-zinc-800/10 dark:divide-white/20" data-flux-rows>
                                            @foreach($playlist->items->sortBy('order') as $item)
                                                <tr data-flux-row>
                                                    <td class="py-3 px-3 first:pl-0 last:pr-0 text-sm whitespace-nowrap text-zinc-500 dark:text-zinc-300">
                                                        {{ $item->plugin->name }}
                                                    </td>
                                                    <td class="py-3 px-3 first:pl-0 last:pr-0 text-sm whitespace-nowrap text-zinc-500 dark:text-zinc-300">
                                                        <flux:switch wire:model.live="item.is_active"
                                                                     wire:click="togglePlaylistItemActive({{ $item->id }})"
                                                                     :checked="$item->is_active"/>
                                                    </td>
                                                    <td class="py-3 px-3 first:pl-0 last:pr-0 text-sm whitespace-nowrap text-right">
                                                        <div class="flex items-center justify-end gap-2">
                                                            @if($playlist->items->count() > 1)
                                                                @if(!$loop->first)
                                                                    <flux:button wire:click="movePlaylistItemUp({{ $item->id }})" icon="arrow-up" variant="ghost" size="xs"/>
                                                                @endif
                                                                @if(!$loop->last)
                                                                    <flux:button wire:click="movePlaylistItemDown({{ $item->id }})" icon="arrow-down" variant="ghost" size="xs"/>
                                                                @endif
                                                            @endif
                                                            <flux:modal.trigger name="delete-playlist-item-{{ $item->id }}">
                                                                <flux:button icon="trash" variant="ghost" size="xs"/>
                                                            </flux:modal.trigger>
                                                        </div>

                                                        <flux:modal name="delete-playlist-item-{{ $item->id }}" class="min-w-[22rem] space-y-6">
                                                            <div>
                                                                <flux:heading size="lg">Delete {{ $item->plugin->name }}?</flux:heading>
                                                                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">This will remove this item from the playlist.</p>
                                                            </div>

                                                            <div class="flex gap-2">
                                                                <flux:spacer/>
                                                                <flux:modal.close>
                                                                    <flux:button variant="ghost">Cancel</flux:button>
                                                                </flux:modal.close>
                                                                <flux:button wire:click="deletePlaylistItem({{ $item->id }})" variant="danger">Delete item</flux:button>
                                                            </div>
                                                        </flux:modal>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <flux:modal name="edit-playlist-{{ $playlist->id }}" class="md:w-96">
                                    <div class="space-y-6">
                                        <div>
                                            <flux:heading size="lg">Edit Playlist</flux:heading>
                                        </div>

                                        <form wire:submit="editPlaylist({{ $playlist->id }})">
                                            <div class="mb-4">
                                                <flux:input label="Playlist Name" wire:model="playlist_name" required/>
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

                                            <div class="flex">
                                                <flux:spacer/>
                                                <flux:button type="submit" variant="primary">Save Changes</flux:button>
                                            </div>
                                        </form>
                                    </div>
                                </flux:modal>

                                <flux:modal name="delete-playlist-{{ $playlist->id }}" class="min-w-[22rem] space-y-6">
                                    <div>
                                        <flux:heading size="lg">Delete {{ $playlist->name }}?</flux:heading>
                                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">This will permanently delete this playlist and all its items.</p>
                                    </div>

                                    <div class="flex gap-2">
                                        <flux:spacer/>
                                        <flux:modal.close>
                                            <flux:button variant="ghost">Cancel</flux:button>
                                        </flux:modal.close>
                                        <flux:button wire:click="deletePlaylist({{ $playlist->id }})" variant="danger">Delete playlist</flux:button>
                                    </div>
                                </flux:modal>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach

            @if($devices->isEmpty() || $devices->every(fn($device) => $device->playlists->isEmpty()))
                <div class="rounded-xl border bg-white dark:bg-stone-950 dark:border-stone-800 text-stone-800 shadow-xs">
                    <div class="px-10 py-8">
                        <h1 class="text-xl font-medium dark:text-zinc-200">No playlists found</h1>
                        <p class="text-sm dark:text-zinc-400 mt-2">Add playlists to your devices to see them here.</p>
                        @if($devices->isNotEmpty())
                            <flux:button href="{{ route('devices') }}" wire:navigate icon="square-chart-gantt" class="mt-4">
                                Go to Devices
                            </flux:button>
                        @else
                            <flux:button href="{{ route('devices') }}" wire:navigate icon="plus-circle" variant="primary" class="mt-4">
                                Add Device
                            </flux:button>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

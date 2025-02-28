@props(['strength', 'rssi'])
<flux:tooltip content="Wi-Fi RSSI Level: {{ $rssi }} db" position="bottom">
    @if ($strength === 3)
        <flux:icon.wifi class="dark:text-zinc-200"/>
    @elseif ($strength === 2)
        <flux:icon.wifi-high class="dark:text-zinc-200"/>
    @elseif ($strength === 1)
        <flux:icon.wifi-low class="dark:text-zinc-200"/>
    @else
        <flux:icon.wifi-zero class="dark:text-zinc-200"/>
    @endif
</flux:tooltip>

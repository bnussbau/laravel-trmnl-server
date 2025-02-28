@props(['strength', 'rssi'])
<flux:tooltip content="Wi-Fi RSSI Level: {{ $strength }} db" position="bottom">
    @if ($strength == 3)
        <flux:icon.wifi/>
    @elseif ($strength == 2)
        <flux:icon.wifi-high/>
    @elseif ($strength == 1)
        <flux:icon.wifi-low/>
    @else
        <flux:icon.wifi-zero/>
    @endif
</flux:tooltip>

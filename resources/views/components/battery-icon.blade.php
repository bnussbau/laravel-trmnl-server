@props(['percent'])
<flux:tooltip content="Battery Percent: {{ $percent }}%" position="bottom">
    @if ($percent > 60)
        <flux:icon.battery-full/>
    @elseif ($percent < 20)
        <flux:icon.battery-low/>
    @else
        <flux:icon.battery-medium/>
    @endif
</flux:tooltip>

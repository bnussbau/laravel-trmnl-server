@props(['percent'])
<flux:tooltip content="Battery Percent: {{ $percent }}%" position="bottom">
    @if ($percent > 60)
        <flux:icon.battery-full class="dark:text-zinc-200"/>
    @elseif ($percent < 20)
        <flux:icon.battery-low class="dark:text-zinc-200"/>
    @else
        <flux:icon.battery-medium class="dark:text-zinc-200"/>
    @endif
</flux:tooltip>

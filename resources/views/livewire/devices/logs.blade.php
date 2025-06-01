<?php

use App\Models\Device;
use App\Models\DeviceLog;
use Livewire\Volt\Component;

new class extends Component {
    public Device $device;
    public $logs;

    public function mount(Device $device)
    {
        abort_unless(auth()->user()->devices->contains($device), 403);
        $this->device = $device;
        $this->logs = $device->logs()->latest()->get();
    }
}

?>

<div>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-semibold dark:text-gray-100">Device Logs - {{ $device->name }}</h2>
            </div>

            <table class="min-w-full table-fixed text-zinc-800 divide-y divide-zinc-800/10 dark:divide-white/20 text-zinc-800" data-flux-table="">
                <thead data-flux-columns="">
                    <tr>
                        <th class="py-3 px-3 first:pl-0 last:pr-0 text-left text-sm font-medium text-zinc-800 dark:text-white" data-flux-column="">
                            <div class="whitespace-nowrap flex group-[]/right-align:justify-end">Device Time</div>
                        </th>
                        <th class="py-3 px-3 first:pl-0 last:pr-0 text-left text-sm font-medium text-zinc-800 dark:text-white" data-flux-column="">
                            <div class="whitespace-nowrap flex group-[]/right-align:justify-end">Log Level</div>
                        </th>
                        <th class="py-3 px-3 first:pl-0 last:pr-0 text-left text-sm font-medium text-zinc-800 dark:text-white" data-flux-column="">
                            <div class="whitespace-nowrap flex group-[]/right-align:justify-end">Device Status</div>
                        </th>
                        <th class="py-3 px-3 first:pl-0 last:pr-0 text-left text-sm font-medium text-zinc-800 dark:text-white" data-flux-column="">
                            <div class="whitespace-nowrap flex group-[]/right-align:justify-end">Message</div>
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-zinc-800/10 dark:divide-white/20" data-flux-rows="">
                    @foreach ($logs as $log)
                        <tr data-flux-row="">
                            <td class="py-3 px-3 first:pl-0 last:pr-0 text-sm whitespace-nowrap text-zinc-500 dark:text-zinc-300">
                                {{ \Carbon\Carbon::createFromTimestamp($log->log_entry['creation_timestamp'])->format('Y-m-d H:i:s') }}
                            </td>
                            <td class="py-3 px-3 first:pl-0 last:pr-0 text-sm whitespace-nowrap text-zinc-500 dark:text-zinc-300">
                                <div class="inline-flex items-center font-medium whitespace-nowrap -mt-1 -mb-1 text-xs py-1 px-2 rounded-md
                                    @if(str_contains(strtolower($log->log_entry['log_message']), 'error'))
                                        bg-red-400/15 text-red-700 dark:bg-red-400/40 dark:text-red-200
                                    @elseif(str_contains(strtolower($log->log_entry['log_message']), 'warning'))
                                        bg-yellow-400/15 text-yellow-700 dark:bg-yellow-400/40 dark:text-yellow-200
                                    @else
                                        bg-green-400/15 text-green-700 dark:bg-green-400/40 dark:text-green-200
                                    @endif">
                                    {{ str_contains(strtolower($log->log_entry['log_message']), 'error') ? 'Error' : 
                                       (str_contains(strtolower($log->log_entry['log_message']), 'warning') ? 'Warning' : 'Info') }}
                                </div>
                            </td>
                            <td class="py-3 px-3 first:pl-0 last:pr-0 text-sm whitespace-nowrap text-zinc-500 dark:text-zinc-300">
                                <div class="inline-flex items-center font-medium whitespace-nowrap -mt-1 -mb-1 text-xs py-1 px-2 rounded-md bg-zinc-400/15 text-zinc-700 dark:bg-zinc-400/40 dark:text-zinc-200">
                                    {{ $log->log_entry['device_status_stamp']['wifi_status'] ?? 'Unknown' }}
                                    @if(isset($log->log_entry['device_status_stamp']['wifi_rssi_level']))
                                        ({{ $log->log_entry['device_status_stamp']['wifi_rssi_level'] }}dBm)
                                    @endif
                                </div>
                            </td>
                            <td class="py-3 px-3 first:pl-0 last:pr-0 text-sm whitespace-nowrap text-zinc-500 dark:text-zinc-300">
                                {{ $log->log_entry['log_message'] }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div> 
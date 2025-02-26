<?php

namespace App\Jobs;

use App\Models\Device;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class FetchProxyCloudResponses implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $baseUrl = Config::get('services.trmnl.base_url');
        
        Device::where('proxy_cloud', true)->each(function ($device) use ($baseUrl) {
            try {
                $response = Http::withHeaders([
                    'id' => $device->mac_address,
                    'access-token' => $device->api_key,
                ])->get($baseUrl . '/api/display');

                $device->update([
                    'proxy_cloud_response' => $response->body(),
                ]);

                Log::info("Successfully updated proxy cloud response for device: {$device->mac_address}");
            } catch (\Exception $e) {
                Log::error("Failed to fetch proxy cloud response for device: {$device->mac_address}", [
                    'error' => $e->getMessage()
                ]);
            }
        });
    }
}

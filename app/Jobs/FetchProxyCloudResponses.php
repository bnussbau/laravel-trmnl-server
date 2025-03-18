<?php

namespace App\Jobs;

use App\Models\Device;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FetchProxyCloudResponses implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Device::where('proxy_cloud', true)->each(function ($device) {
            if (!$device->getNextPlaylistItem()) {
                try {
                    $response = Http::withHeaders([
                        'id' => $device->mac_address,
                        'access-token' => $device->api_key,
                        'width' => 800,
                        'height' => 480,
                        'rssi' => $device->last_rssi_level,
                        'battery_voltage' => $device->last_battery_voltage,
                        'refresh-rate' => $device->default_refresh_interval,
                        'fw-version' => $device->last_firmware_version,
                        'accept-encoding' => 'identity;q=1,chunked;q=0.1,*;q=0',
                        'user-agent' => 'ESP32HTTPClient',
                    ])->get(config('services.trmnl.proxy_base_url') . '/api/display');

                    $device->update([
                        'proxy_cloud_response' => $response->json(),
                    ]);

                    $imageUrl = $response->json('image_url');
                    $filename = $response->json('filename');

                    \Log::info('Response data: ' . $imageUrl);
                    if (isset($imageUrl)) {
                        try {
                            $imageContents = Http::get($imageUrl)->body();
                            if (!Storage::disk('public')->exists("images/generated/{$filename}.bmp")) {
                                Storage::disk('public')->put(
                                    "images/generated/{$filename}.bmp",
                                    $imageContents
                                );
                            }

                            $device->update([
                                'current_screen_image' => $filename,
                            ]);
                        } catch (\Exception $e) {
                            Log::error("Failed to download and save image for device: {$device->mac_address}", [
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                    Log::info("Successfully updated proxy cloud response for device: {$device->mac_address}");

                    if ($device->last_log_request) {
                        Http::withHeaders([
                            'id' => $device->mac_address,
                            'access-token' => $device->api_key,
                            'width' => 800,
                            'height' => 480,
                            'rssi' => $device->last_rssi_level,
                            'battery_voltage' => $device->last_battery_voltage,
                            'refresh-rate' => $device->default_refresh_interval,
                            'fw-version' => $device->last_firmware_version,
                            'accept-encoding' => 'identity;q=1,chunked;q=0.1,*;q=0',
                            'user-agent' => 'ESP32HTTPClient',
                        ])->post(config('services.trmnl.proxy_base_url') . '/api/log', $device->last_log_request);

                        $device->update([
                            'last_log_request' => null,
                        ]);
                    }

                } catch (\Exception $e) {
                    Log::error("Failed to fetch proxy cloud response for device: {$device->mac_address}", [
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                Log::info("Skipping device: {$device->mac_address} as it has a pending playlist item.");
            }
        });
    }
}

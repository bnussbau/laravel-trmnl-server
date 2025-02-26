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
            try {
                $response = Http::withHeaders([
                    'id' => $device->mac_address,
                    'access-token' => $device->api_key,
                ])->get(config('services.trmnl.proxy_base_url').'/api/display');

                $device->update([
                    'proxy_cloud_response' => $response->json(),
                ]);

                $imageUrl = $response->json('image_url');
                $filename = $response->json('filename');

                \Log::info('Response data: '.$imageUrl);
                if (isset($imageUrl)) {
                    try {
                        $imageContents = Http::get($imageUrl)->body();
                        if (! Storage::disk('public')->exists("images/generated/{$filename}.bmp")) {
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
            } catch (\Exception $e) {
                Log::error("Failed to fetch proxy cloud response for device: {$device->mac_address}", [
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }
}

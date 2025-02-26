<?php

namespace App\Jobs;

use App\Models\Device;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
                ])->get($baseUrl.'/api/display');

                $device->update([
                    'proxy_cloud_response' => $response->json(),
                ]);

                $responseData = $response->json('image_url');
                \Log::info('Response data: '.$responseData);
                if (isset($responseData)) {
                    try {
                        $imageUuid = \Illuminate\Support\Str::uuid();
                        $imageContents = Http::get($responseData)->body();
                        \Illuminate\Support\Facades\Storage::disk('public')->put(
                            "images/generated/{$imageUuid}.bmp",
                            $imageContents
                        );

                        $device->update([
                            'current_screen_image' => $imageUuid,
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

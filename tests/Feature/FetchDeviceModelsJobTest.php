<?php

declare(strict_types=1);

use App\Jobs\FetchDeviceModelsJob;
use App\Models\DeviceModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

beforeEach(function () {
    Log::spy();
});

test('job fetches and creates device models from api', function () {
    $apiResponse = [
        'data' => [
            [
                'name' => 'og_png',
                'label' => 'TRMNL (1-bit)',
                'description' => 'TRMNL (1-bit)',
                'width' => 800,
                'height' => 480,
                'colors' => 2,
                'bit_depth' => 1,
                'scale_factor' => 1,
                'rotation' => 0,
                'mime_type' => 'image/png',
                'offset_x' => 0,
                'offset_y' => 0,
                'published_at' => '2024-01-01T00:00:00.000Z',
            ],
            [
                'name' => 'test_model',
                'label' => 'Test Model',
                'description' => 'Test Description',
                'width' => 1024,
                'height' => 768,
                'colors' => 256,
                'bit_depth' => 8,
                'scale_factor' => 2,
                'rotation' => 90,
                'mime_type' => 'image/jpeg',
                'offset_x' => 10,
                'offset_y' => 20,
                'published_at' => '2024-01-02T00:00:00.000Z',
            ],
        ],
    ];

    Http::fake([
        'https://usetrmnl.com/api/models' => Http::response($apiResponse, 200),
    ]);

    $job = new FetchDeviceModelsJob();
    $job->handle();

    $this->assertDatabaseCount('device_models', 2);
    $this->assertDatabaseHas('device_models', [
        'name' => 'og_png',
        'label' => 'TRMNL (1-bit)',
        'width' => 800,
        'height' => 480,
        'colors' => 2,
        'bit_depth' => 1,
        'scale_factor' => 1,
        'rotation' => 0,
        'mime_type' => 'image/png',
        'offset_x' => 0,
        'offset_y' => 0,
    ]);

    $this->assertDatabaseHas('device_models', [
        'name' => 'test_model',
        'label' => 'Test Model',
        'width' => 1024,
        'height' => 768,
        'colors' => 256,
        'bit_depth' => 8,
        'scale_factor' => 2,
        'rotation' => 90,
        'mime_type' => 'image/jpeg',
        'offset_x' => 10,
        'offset_y' => 20,
    ]);

    Log::shouldHaveReceived('info')->with('Successfully fetched and updated device models', ['count' => 2]);
});

test('job updates existing device models by name', function () {
    // Create an existing device model
    DeviceModel::factory()->create([
        'name' => 'og_png',
        'label' => 'Old Label',
        'width' => 400,
        'height' => 300,
    ]);

    $apiResponse = [
        'data' => [
            [
                'name' => 'og_png',
                'label' => 'TRMNL (1-bit)',
                'description' => 'TRMNL (1-bit)',
                'width' => 800,
                'height' => 480,
                'colors' => 2,
                'bit_depth' => 1,
                'scale_factor' => 1,
                'rotation' => 0,
                'mime_type' => 'image/png',
                'offset_x' => 0,
                'offset_y' => 0,
                'published_at' => '2024-01-01T00:00:00.000Z',
            ],
        ],
    ];

    Http::fake([
        'https://usetrmnl.com/api/models' => Http::response($apiResponse, 200),
    ]);

    $job = new FetchDeviceModelsJob();
    $job->handle();

    $this->assertDatabaseCount('device_models', 1);
    $this->assertDatabaseHas('device_models', [
        'name' => 'og_png',
        'label' => 'TRMNL (1-bit)',
        'width' => 800,
        'height' => 480,
    ]);

    // Verify the old values were updated
    $this->assertDatabaseMissing('device_models', [
        'name' => 'og_png',
        'label' => 'Old Label',
        'width' => 400,
        'height' => 300,
    ]);
});

test('job handles api failure gracefully', function () {
    Http::fake([
        'https://usetrmnl.com/api/models' => Http::response([], 500),
    ]);

    $job = new FetchDeviceModelsJob();
    $job->handle();

    $this->assertDatabaseCount('device_models', 0);
    Log::shouldHaveReceived('error')->with('Failed to fetch device models from API', Mockery::any());
});

test('job handles invalid response format gracefully', function () {
    Http::fake([
        'https://usetrmnl.com/api/models' => Http::response(['data' => 'not_an_array'], 200),
    ]);

    $job = new FetchDeviceModelsJob();
    $job->handle();

    $this->assertDatabaseCount('device_models', 0);
    Log::shouldHaveReceived('error')->with('Invalid response format from device models API', Mockery::any());
});

test('job handles missing name field gracefully', function () {
    $apiResponse = [
        'data' => [
            [
                'label' => 'TRMNL (1-bit)',
                'width' => 800,
                'height' => 480,
                // Missing 'name' field
            ],
        ],
    ];

    Http::fake([
        'https://usetrmnl.com/api/models' => Http::response($apiResponse, 200),
    ]);

    $job = new FetchDeviceModelsJob();
    $job->handle();

    $this->assertDatabaseCount('device_models', 0);
    Log::shouldHaveReceived('warning')->with('Device model data missing name field', Mockery::any());
});

test('job handles network exceptions gracefully', function () {
    Http::fake([
        'https://usetrmnl.com/api/models' => function () {
            throw new Exception('Network error');
        },
    ]);

    $job = new FetchDeviceModelsJob();
    $job->handle();

    $this->assertDatabaseCount('device_models', 0);
    Log::shouldHaveReceived('error')->with('Exception occurred while fetching device models', Mockery::any());
});

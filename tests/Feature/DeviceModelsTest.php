<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\DeviceModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_device_models_page(): void
    {
        $user = User::factory()->create();
        $deviceModels = DeviceModel::factory()->count(3)->create();

        $response = $this->actingAs($user)->get('/device-models');

        $response->assertOk();
        $response->assertSee('Device Models');
        $response->assertSee('Add Device Model');
        
        foreach ($deviceModels as $deviceModel) {
            $response->assertSee($deviceModel->label);
            $response->assertSee((string) $deviceModel->width);
            $response->assertSee((string) $deviceModel->height);
            $response->assertSee((string) $deviceModel->bit_depth);
        }
    }

    public function test_user_can_create_device_model(): void
    {
        $user = User::factory()->create();

        $deviceModelData = [
            'name' => 'test-model',
            'label' => 'Test Model',
            'description' => 'A test device model',
            'width' => 800,
            'height' => 600,
            'colors' => 256,
            'bit_depth' => 8,
            'scale_factor' => 1.0,
            'rotation' => 0,
            'mime_type' => 'image/png',
            'offset_x' => 0,
            'offset_y' => 0,
        ];

        $deviceModel = DeviceModel::create($deviceModelData);

        $this->assertDatabaseHas('device_models', $deviceModelData);
        $this->assertEquals($deviceModelData['name'], $deviceModel->name);
    }

    public function test_user_can_update_device_model(): void
    {
        $user = User::factory()->create();
        $deviceModel = DeviceModel::factory()->create();

        $updatedData = [
            'name' => 'updated-model',
            'label' => 'Updated Model',
            'description' => 'An updated device model',
            'width' => 1024,
            'height' => 768,
            'colors' => 65536,
            'bit_depth' => 16,
            'scale_factor' => 1.5,
            'rotation' => 90,
            'mime_type' => 'image/jpeg',
            'offset_x' => 10,
            'offset_y' => 20,
        ];

        $deviceModel->update($updatedData);

        $this->assertDatabaseHas('device_models', $updatedData);
        $this->assertEquals($updatedData['name'], $deviceModel->fresh()->name);
    }

    public function test_user_can_delete_device_model(): void
    {
        $user = User::factory()->create();
        $deviceModel = DeviceModel::factory()->create();

        $deviceModelId = $deviceModel->id;
        $deviceModel->delete();

        $this->assertDatabaseMissing('device_models', ['id' => $deviceModelId]);
    }

    public function test_unauthenticated_user_cannot_access_device_models_page(): void
    {
        $response = $this->get('/device-models');

        $response->assertRedirect('/login');
    }
} 
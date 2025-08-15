<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\DeviceModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeviceModelsEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_fetch_device_models(): void
    {
        $user = User::factory()->create();
        $deviceModels = DeviceModel::factory()->count(2)->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/device-models');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'label',
                        'description',
                        'width',
                        'height',
                        'bit_depth',
                    ],
                ],
            ])
            ->assertJsonCount(2, 'data');

        // Verify the first device model's data
        $response->assertJson([
            'data' => [
                [
                    'id' => $deviceModels[0]->id,
                    'name' => $deviceModels[0]->name,
                    'label' => $deviceModels[0]->label,
                    'description' => $deviceModels[0]->description,
                    'width' => $deviceModels[0]->width,
                    'height' => $deviceModels[0]->height,
                    'bit_depth' => $deviceModels[0]->bit_depth,
                ],
            ],
        ]);
    }

    public function test_unauthenticated_user_cannot_access_device_models(): void
    {
        $response = $this->getJson('/api/device-models');

        $response->assertUnauthorized();
    }
} 
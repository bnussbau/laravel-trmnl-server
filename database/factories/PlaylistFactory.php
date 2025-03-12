<?php

namespace Database\Factories;

use App\Models\Device;
use App\Models\Playlist;
use App\Models\Plugin;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class PlaylistFactory extends Factory
{
    protected $model = Playlist::class;

    public function definition(): array
    {
        return [
            'order' => $this->faker->randomNumber(),
            'is_active' => $this->faker->boolean(),
            'last_displayed_at' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'device_id' => Device::factory(),
            'plugin_id' => Plugin::factory(),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Device;
use App\Models\Playlist;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class PlaylistFactory extends Factory
{
    protected $model = Playlist::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'is_active' => $this->faker->boolean(80), // 80% chance of being active
            'weekdays' => $this->faker->randomElements(range(0, 6), $this->faker->numberBetween(1, 7)),
            'active_from' => $this->faker->time('H:i:s'),
            'active_until' => $this->faker->time('H:i:s'),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'device_id' => Device::factory(),
        ];
    }
}

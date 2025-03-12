<?php

namespace Database\Factories;

use App\Models\Playlist;
use App\Models\PlaylistItem;
use App\Models\Plugin;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class PlaylistItemFactory extends Factory
{
    protected $model = PlaylistItem::class;

    public function definition(): array
    {
        return [
            'order' => $this->faker->randomNumber(),
            'is_active' => $this->faker->boolean(),
            'last_displayed_at' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'playlist_id' => Playlist::factory(),
            'plugin_id' => Plugin::factory(),
        ];
    }
}

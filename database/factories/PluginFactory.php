<?php

namespace Database\Factories;

use App\Models\Plugin;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class PluginFactory extends Factory
{
    protected $model = Plugin::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'user_id' => '1',
            'name' => $this->faker->name(),
            'data_payload' => ['foo' => 'bar'],
            'data_stale_minutes' => $this->faker->numberBetween(5, 300),
            'data_strategy' => $this->faker->randomElement(['polling', 'webhook']),
            'polling_url' => $this->faker->url(),
            'polling_verb' => $this->faker->randomElement(['get', 'post']),
            'polling_header' => null,
            'markup' => null,
            'blade_view' => null,
            'icon_url' => null,
            'author_name' => $this->faker->name(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}

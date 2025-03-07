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
            'user_id' => $this->faker->randomNumber(),
            'name' => $this->faker->name(),
            'data_payload' => $this->faker->word(),
            'data_stale_minutes' => $this->faker->randomNumber(),
            'data_strategy' => $this->faker->word(),
            'polling_url' => $this->faker->url(),
            'polling_verb' => $this->faker->word(),
            'polling_header' => $this->faker->word(),
            'markup' => $this->faker->word(),
            'blade_view' => $this->faker->word(),
            'icon_url' => $this->faker->url(),
            'author_name' => $this->faker->name(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}

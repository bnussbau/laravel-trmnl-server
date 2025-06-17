<?php

use App\Models\Plugin;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('plugin has required attributes', function () {
    $plugin = Plugin::factory()->create([
        'name' => 'Test Plugin',
        'data_payload' => ['key' => 'value'],
    ]);

    expect($plugin)
        ->name->toBe('Test Plugin')
        ->data_payload->toBe(['key' => 'value'])
        ->uuid->toBeString()
        ->uuid->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');
});

test('plugin automatically generates uuid on creation', function () {
    $plugin = Plugin::factory()->create();

    expect($plugin->uuid)
        ->toBeString()
        ->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');
});

test('plugin can have custom uuid', function () {
    $uuid = Illuminate\Support\Str::uuid();
    $plugin = Plugin::factory()->create(['uuid' => $uuid]);

    expect($plugin->uuid)->toBe($uuid);
});

test('plugin data_payload is cast to array', function () {
    $data = ['key' => 'value'];
    $plugin = Plugin::factory()->create(['data_payload' => $data]);

    expect($plugin->data_payload)
        ->toBeArray()
        ->toBe($data);
});

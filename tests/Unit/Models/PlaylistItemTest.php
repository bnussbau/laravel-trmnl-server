<?php

use App\Models\Playlist;
use App\Models\PlaylistItem;
use App\Models\Plugin;


test('playlist item belongs to playlist', function () {
    $playlist = Playlist::factory()->create();
    $playlistItem = PlaylistItem::factory()->create(['playlist_id' => $playlist->id]);

    expect($playlistItem->playlist)
        ->toBeInstanceOf(Playlist::class)
        ->id->toBe($playlist->id);
});

test('playlist item belongs to plugin', function () {
    $plugin = Plugin::factory()->create();
    $playlistItem = PlaylistItem::factory()->create(['plugin_id' => $plugin->id]);

    expect($playlistItem->plugin)
        ->toBeInstanceOf(Plugin::class)
        ->id->toBe($plugin->id);
});

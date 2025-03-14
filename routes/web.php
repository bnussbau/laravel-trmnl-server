<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');
    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');

    Volt::route('/dashboard', 'device-dashboard')->name('dashboard');

    Volt::route('/devices', 'devices.manage')->name('devices');
    Volt::route('/devices/{device}/configure', 'devices.configure')->name('devices.configure');

    Volt::route('plugins', 'plugins.index')->name('plugins.index');

    Volt::route('plugins/receipt/{plugin}', 'plugins.receipt')->name('plugins.receipt');
    Volt::route('plugins/markup', 'plugins.markup')->name('plugins.markup');
    Volt::route('plugins/api', 'plugins.api')->name('plugins.api');
    Volt::route('playlists', 'playlists.index')->name('playlists.index');
});

require __DIR__.'/auth.php';

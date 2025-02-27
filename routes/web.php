<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');

    Route::get('/devices', function () {
        return view('devices');
    })->name('devices');

    Route::get('/devices/{device}/configure', function (App\Models\Device $device) {
        $current_image_uuid = auth()->user()->devices()->find($device->id)->current_screen_image;
        $current_image_path = 'images/generated/'.$current_image_uuid.'.png';

        return view('devices.configure', compact('device'), [
            'image' => ($current_image_uuid) ? url($current_image_path) : null,
        ]);
    })->name('devices.configure');

    //    Volt::route('plugins', 'plugins.index')->name('plugins.index');
    Route::redirect('plugins', 'plugins/markup')->name('plugins.index');
    Volt::route('plugins/markup', 'plugins.markup')->name('plugins.markup');
});

require __DIR__.'/auth.php';

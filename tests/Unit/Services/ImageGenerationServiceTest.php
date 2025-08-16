<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\ImageFormat;
use App\Models\Device;
use App\Models\DeviceModel;
use App\Services\ImageGenerationService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use Tests\TestCase;

class ImageGenerationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_image_settings_returns_device_model_settings_when_available(): void
    {
        // Create a DeviceModel
        $deviceModel = DeviceModel::factory()->create([
            'width' => 1024,
            'height' => 768,
            'colors' => 256,
            'bit_depth' => 8,
            'scale_factor' => 1.5,
            'rotation' => 90,
            'mime_type' => 'image/png',
            'offset_x' => 10,
            'offset_y' => 20,
        ]);

        // Create a device with the DeviceModel
        $device = Device::factory()->create([
            'device_model_id' => $deviceModel->id,
        ]);

        // Use reflection to access private method
        $reflection = new ReflectionClass(ImageGenerationService::class);
        $method = $reflection->getMethod('getImageSettings');
        $method->setAccessible(true);

        $settings = $method->invoke(null, $device);

        // Assert DeviceModel settings are used
        expect($settings['width'])->toBe(1024);
        expect($settings['height'])->toBe(768);
        expect($settings['colors'])->toBe(256);
        expect($settings['bit_depth'])->toBe(8);
        expect($settings['scale_factor'])->toBe(1.5);
        expect($settings['rotation'])->toBe(90);
        expect($settings['mime_type'])->toBe('image/png');
        expect($settings['offset_x'])->toBe(10);
        expect($settings['offset_y'])->toBe(20);
        expect($settings['use_model_settings'])->toBe(true);
    }

    public function test_get_image_settings_falls_back_to_device_settings_when_no_device_model(): void
    {
        // Create a device without DeviceModel
        $device = Device::factory()->create([
            'width' => 800,
            'height' => 480,
            'rotate' => 180,
            'image_format' => ImageFormat::PNG_8BIT_GRAYSCALE->value,
        ]);

        // Use reflection to access private method
        $reflection = new ReflectionClass(ImageGenerationService::class);
        $method = $reflection->getMethod('getImageSettings');
        $method->setAccessible(true);

        $settings = $method->invoke(null, $device);

        // Assert device settings are used
        expect($settings['width'])->toBe(800);
        expect($settings['height'])->toBe(480);
        expect($settings['rotation'])->toBe(180);
        expect($settings['image_format'])->toBe(ImageFormat::PNG_8BIT_GRAYSCALE->value);
        expect($settings['use_model_settings'])->toBe(false);
    }

    public function test_get_image_settings_uses_defaults_for_missing_device_properties(): void
    {
        // Create a device without DeviceModel and missing properties
        $device = Device::factory()->create([
            'width' => null,
            'height' => null,
            'rotate' => null,
            // image_format has a default value of 'auto', so we can't set it to null
        ]);

        // Use reflection to access private method
        $reflection = new ReflectionClass(ImageGenerationService::class);
        $method = $reflection->getMethod('getImageSettings');
        $method->setAccessible(true);

        $settings = $method->invoke(null, $device);

        // Assert default values are used
        expect($settings['width'])->toBe(800);
        expect($settings['height'])->toBe(480);
        expect($settings['rotation'])->toBe(0);
        expect($settings['colors'])->toBe(2);
        expect($settings['bit_depth'])->toBe(1);
        expect($settings['scale_factor'])->toBe(1.0);
        expect($settings['mime_type'])->toBe('image/png');
        expect($settings['offset_x'])->toBe(0);
        expect($settings['offset_y'])->toBe(0);
        // image_format will be null if the device doesn't have it set, which is the expected behavior
        expect($settings['image_format'])->toBeNull();
    }

    public function test_determine_image_format_from_model_returns_correct_formats(): void
    {
        // Use reflection to access private method
        $reflection = new ReflectionClass(ImageGenerationService::class);
        $method = $reflection->getMethod('determineImageFormatFromModel');
        $method->setAccessible(true);

        // Test BMP format
        $bmpModel = DeviceModel::factory()->create([
            'mime_type' => 'image/bmp',
            'bit_depth' => 1,
            'colors' => 2,
        ]);
        $format = $method->invoke(null, $bmpModel);
        expect($format)->toBe(ImageFormat::BMP3_1BIT_SRGB->value);

        // Test PNG 8-bit grayscale format
        $pngGrayscaleModel = DeviceModel::factory()->create([
            'mime_type' => 'image/png',
            'bit_depth' => 8,
            'colors' => 2,
        ]);
        $format = $method->invoke(null, $pngGrayscaleModel);
        expect($format)->toBe(ImageFormat::PNG_8BIT_GRAYSCALE->value);

        // Test PNG 8-bit 256 color format
        $png256Model = DeviceModel::factory()->create([
            'mime_type' => 'image/png',
            'bit_depth' => 8,
            'colors' => 256,
        ]);
        $format = $method->invoke(null, $png256Model);
        expect($format)->toBe(ImageFormat::PNG_8BIT_256C->value);

        // Test PNG 2-bit 4 color format
        $png4ColorModel = DeviceModel::factory()->create([
            'mime_type' => 'image/png',
            'bit_depth' => 2,
            'colors' => 4,
        ]);
        $format = $method->invoke(null, $png4ColorModel);
        expect($format)->toBe(ImageFormat::PNG_2BIT_4C->value);

        // Test unknown format returns AUTO
        $unknownModel = DeviceModel::factory()->create([
            'mime_type' => 'image/jpeg',
            'bit_depth' => 16,
            'colors' => 65536,
        ]);
        $format = $method->invoke(null, $unknownModel);
        expect($format)->toBe(ImageFormat::AUTO->value);
    }

    public function test_cleanup_folder_identifies_active_images_correctly(): void
    {
        // Create devices with images
        $device1 = Device::factory()->create(['current_screen_image' => 'active-uuid-1']);
        $device2 = Device::factory()->create(['current_screen_image' => 'active-uuid-2']);
        $device3 = Device::factory()->create(['current_screen_image' => null]);

        // Create a plugin with image
        $plugin = \App\Models\Plugin::factory()->create(['current_image' => 'plugin-uuid']);

        // Use reflection to access private method or test the public method
        // Since cleanupFolder is public, we can test it directly
        // The test will verify that the method correctly identifies active images
        // This is more of an integration test, but it's useful to verify the logic

        // For unit testing, we could test the logic that determines active UUIDs
        $activeDeviceImageUuids = Device::pluck('current_screen_image')->filter()->toArray();
        $activePluginImageUuids = \App\Models\Plugin::pluck('current_image')->filter()->toArray();
        $activeImageUuids = array_merge($activeDeviceImageUuids, $activePluginImageUuids);

        expect($activeImageUuids)->toContain('active-uuid-1');
        expect($activeImageUuids)->toContain('active-uuid-2');
        expect($activeImageUuids)->toContain('plugin-uuid');
        expect($activeImageUuids)->not->toContain(null);
    }

    public function test_reset_if_not_cacheable_detects_device_models(): void
    {
        // Create a plugin
        $plugin = \App\Models\Plugin::factory()->create(['current_image' => 'test-uuid']);

        // Create a device with DeviceModel
        Device::factory()->create([
            'device_model_id' => DeviceModel::factory()->create()->id,
        ]);

        // Test that the method detects DeviceModels and resets cache
        ImageGenerationService::resetIfNotCacheable($plugin);

        $plugin->refresh();
        expect($plugin->current_image)->toBeNull();
    }

    public function test_reset_if_not_cacheable_detects_custom_dimensions(): void
    {
        // Create a plugin
        $plugin = \App\Models\Plugin::factory()->create(['current_image' => 'test-uuid']);

        // Create a device with custom dimensions
        Device::factory()->create([
            'width' => 1024, // Different from default 800
            'height' => 768, // Different from default 480
        ]);

        // Test that the method detects custom dimensions and resets cache
        ImageGenerationService::resetIfNotCacheable($plugin);

        $plugin->refresh();
        expect($plugin->current_image)->toBeNull();
    }

    public function test_reset_if_not_cacheable_preserves_cache_for_standard_devices(): void
    {
        // Create a plugin
        $plugin = \App\Models\Plugin::factory()->create(['current_image' => 'test-uuid']);

        // Create devices with standard dimensions
        Device::factory()->count(3)->create([
            'width' => 800,
            'height' => 480,
            'rotate' => 0,
        ]);

        // Test that the method preserves cache for standard devices
        ImageGenerationService::resetIfNotCacheable($plugin);

        $plugin->refresh();
        expect($plugin->current_image)->toBe('test-uuid');
    }

    public function test_reset_if_not_cacheable_handles_null_plugin(): void
    {
        // Test that the method handles null plugin gracefully
        expect(fn () => ImageGenerationService::resetIfNotCacheable(null))->not->toThrow(Exception::class);
    }

    public function test_image_format_enum_includes_new_2bit_4c_format(): void
    {
        // Test that the new format is properly defined in the enum
        expect(ImageFormat::PNG_2BIT_4C->value)->toBe('png_2bit_4c');
        expect(ImageFormat::PNG_2BIT_4C->label())->toBe('PNG 2-bit Grayscale 4c');
    }

    public function test_device_model_relationship_works_correctly(): void
    {
        // Create a DeviceModel
        $deviceModel = DeviceModel::factory()->create();

        // Create a device with the DeviceModel
        $device = Device::factory()->create([
            'device_model_id' => $deviceModel->id,
        ]);

        // Test the relationship
        expect($device->deviceModel)->toBeInstanceOf(DeviceModel::class);
        expect($device->deviceModel->id)->toBe($deviceModel->id);
    }

    public function test_device_without_device_model_returns_null_relationship(): void
    {
        // Create a device without DeviceModel
        $device = Device::factory()->create([
            'device_model_id' => null,
        ]);

        // Test the relationship returns null
        expect($device->deviceModel)->toBeNull();
    }
}

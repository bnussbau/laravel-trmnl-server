<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ImageFormat;
use App\Models\Device;
use App\Models\DeviceModel;
use App\Services\ImageGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageGenerationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::disk('public')->makeDirectory('/images/generated');
    }

    public function test_generates_image_for_device_without_device_model(): void
    {
        // Create a device without a DeviceModel (legacy behavior)
        $device = Device::factory()->create([
            'width' => 800,
            'height' => 480,
            'rotate' => 0,
            'image_format' => ImageFormat::PNG_8BIT_GRAYSCALE->value,
        ]);

        $markup = '<div style="background: white; color: black; padding: 20px;">Test Content</div>';
        $uuid = ImageGenerationService::generateImage($markup, $device->id);

        // Assert the device was updated with a new image UUID
        $device->refresh();
        expect($device->current_screen_image)->toBe($uuid);

        // Assert PNG file was created
        Storage::disk('public')->assertExists("/images/generated/{$uuid}.png");
    }

    public function test_generates_image_for_device_with_device_model(): void
    {
        // Create a DeviceModel
        $deviceModel = DeviceModel::factory()->create([
            'width' => 1024,
            'height' => 768,
            'colors' => 256,
            'bit_depth' => 8,
            'scale_factor' => 1.0,
            'rotation' => 0,
            'mime_type' => 'image/png',
            'offset_x' => 0,
            'offset_y' => 0,
        ]);

        // Create a device with the DeviceModel
        $device = Device::factory()->create([
            'device_model_id' => $deviceModel->id,
        ]);

        $markup = '<div style="background: white; color: black; padding: 20px;">Test Content</div>';
        $uuid = ImageGenerationService::generateImage($markup, $device->id);

        // Assert the device was updated with a new image UUID
        $device->refresh();
        expect($device->current_screen_image)->toBe($uuid);

        // Assert PNG file was created
        Storage::disk('public')->assertExists("/images/generated/{$uuid}.png");
    }

    public function test_generates_4_color_2_bit_png_with_device_model(): void
    {
        // Create a DeviceModel for 4-color, 2-bit PNG
        $deviceModel = DeviceModel::factory()->create([
            'width' => 800,
            'height' => 480,
            'colors' => 4,
            'bit_depth' => 2,
            'scale_factor' => 1.0,
            'rotation' => 0,
            'mime_type' => 'image/png',
            'offset_x' => 0,
            'offset_y' => 0,
        ]);

        // Create a device with the DeviceModel
        $device = Device::factory()->create([
            'device_model_id' => $deviceModel->id,
        ]);

        $markup = '<div style="background: white; color: black; padding: 20px;">Test Content</div>';
        $uuid = ImageGenerationService::generateImage($markup, $device->id);

        // Assert the device was updated with a new image UUID
        $device->refresh();
        expect($device->current_screen_image)->toBe($uuid);

        // Assert PNG file was created
        Storage::disk('public')->assertExists("/images/generated/{$uuid}.png");

        // Verify the image file has content and isn't blank
        $imagePath = Storage::disk('public')->path("/images/generated/{$uuid}.png");
        $imageSize = filesize($imagePath);
        expect($imageSize)->toBeGreaterThan(200); // Should be at least 200 bytes for a 2-bit PNG

        // Verify it's a valid PNG file
        $imageInfo = getimagesize($imagePath);
        expect($imageInfo[0])->toBe(800); // Width
        expect($imageInfo[1])->toBe(480); // Height
        expect($imageInfo[2])->toBe(IMAGETYPE_PNG); // PNG type

        // Debug: Check if the image has any non-transparent pixels
        $image = imagecreatefrompng($imagePath);
        $width = imagesx($image);
        $height = imagesy($image);
        $hasContent = false;
        
        // Check a few sample pixels to see if there's content
        for ($x = 0; $x < min(10, $width); $x += 2) {
            for ($y = 0; $y < min(10, $height); $y += 2) {
                $color = imagecolorat($image, $x, $y);
                if ($color !== 0) { // Not black/transparent
                    $hasContent = true;
                    break 2;
                }
            }
        }
        
        imagedestroy($image);
        expect($hasContent)->toBe(true, "Image should contain visible content");
    }

    public function test_generates_bmp_with_device_model(): void
    {
        // Create a DeviceModel for BMP format
        $deviceModel = DeviceModel::factory()->create([
            'width' => 800,
            'height' => 480,
            'colors' => 2,
            'bit_depth' => 1,
            'scale_factor' => 1.0,
            'rotation' => 0,
            'mime_type' => 'image/bmp',
            'offset_x' => 0,
            'offset_y' => 0,
        ]);

        // Create a device with the DeviceModel
        $device = Device::factory()->create([
            'device_model_id' => $deviceModel->id,
        ]);

        $markup = '<div style="background: white; color: black; padding: 20px;">Test Content</div>';
        $uuid = ImageGenerationService::generateImage($markup, $device->id);

        // Assert the device was updated with a new image UUID
        $device->refresh();
        expect($device->current_screen_image)->toBe($uuid);

        // Assert BMP file was created
        Storage::disk('public')->assertExists("/images/generated/{$uuid}.bmp");
    }

    public function test_applies_scale_factor_from_device_model(): void
    {
        // Create a DeviceModel with scale factor
        $deviceModel = DeviceModel::factory()->create([
            'width' => 800,
            'height' => 480,
            'colors' => 256,
            'bit_depth' => 8,
            'scale_factor' => 2.0, // Scale up by 2x
            'rotation' => 0,
            'mime_type' => 'image/png',
            'offset_x' => 0,
            'offset_y' => 0,
        ]);

        // Create a device with the DeviceModel
        $device = Device::factory()->create([
            'device_model_id' => $deviceModel->id,
        ]);

        $markup = '<div style="background: white; color: black; padding: 20px;">Test Content</div>';
        $uuid = ImageGenerationService::generateImage($markup, $device->id);

        // Assert the device was updated with a new image UUID
        $device->refresh();
        expect($device->current_screen_image)->toBe($uuid);

        // Assert PNG file was created
        Storage::disk('public')->assertExists("/images/generated/{$uuid}.png");
    }

    public function test_applies_rotation_from_device_model(): void
    {
        // Create a DeviceModel with rotation
        $deviceModel = DeviceModel::factory()->create([
            'width' => 800,
            'height' => 480,
            'colors' => 256,
            'bit_depth' => 8,
            'scale_factor' => 1.0,
            'rotation' => 90, // Rotate 90 degrees
            'mime_type' => 'image/png',
            'offset_x' => 0,
            'offset_y' => 0,
        ]);

        // Create a device with the DeviceModel
        $device = Device::factory()->create([
            'device_model_id' => $deviceModel->id,
        ]);

        $markup = '<div style="background: white; color: black; padding: 20px;">Test Content</div>';
        $uuid = ImageGenerationService::generateImage($markup, $device->id);

        // Assert the device was updated with a new image UUID
        $device->refresh();
        expect($device->current_screen_image)->toBe($uuid);

        // Assert PNG file was created
        Storage::disk('public')->assertExists("/images/generated/{$uuid}.png");
    }

    public function test_applies_offset_from_device_model(): void
    {
        // Create a DeviceModel with offset
        $deviceModel = DeviceModel::factory()->create([
            'width' => 800,
            'height' => 480,
            'colors' => 256,
            'bit_depth' => 8,
            'scale_factor' => 1.0,
            'rotation' => 0,
            'mime_type' => 'image/png',
            'offset_x' => 10, // Offset by 10 pixels
            'offset_y' => 20, // Offset by 20 pixels
        ]);

        // Create a device with the DeviceModel
        $device = Device::factory()->create([
            'device_model_id' => $deviceModel->id,
        ]);

        $markup = '<div style="background: white; color: black; padding: 20px;">Test Content</div>';
        $uuid = ImageGenerationService::generateImage($markup, $device->id);

        // Assert the device was updated with a new image UUID
        $device->refresh();
        expect($device->current_screen_image)->toBe($uuid);

        // Assert PNG file was created
        Storage::disk('public')->assertExists("/images/generated/{$uuid}.png");
    }

    public function test_falls_back_to_device_settings_when_no_device_model(): void
    {
        // Create a device with custom settings but no DeviceModel
        $device = Device::factory()->create([
            'width' => 1024,
            'height' => 768,
            'rotate' => 180,
            'image_format' => ImageFormat::PNG_8BIT_256C->value,
        ]);

        $markup = '<div style="background: white; color: black; padding: 20px;">Test Content</div>';
        $uuid = ImageGenerationService::generateImage($markup, $device->id);

        // Assert the device was updated with a new image UUID
        $device->refresh();
        expect($device->current_screen_image)->toBe($uuid);

        // Assert PNG file was created
        Storage::disk('public')->assertExists("/images/generated/{$uuid}.png");
    }

    public function test_handles_auto_image_format_for_legacy_devices(): void
    {
        // Create a device with AUTO format (legacy behavior)
        $device = Device::factory()->create([
            'width' => 800,
            'height' => 480,
            'rotate' => 0,
            'image_format' => ImageFormat::AUTO->value,
            'last_firmware_version' => '1.6.0', // Modern firmware
        ]);

        $markup = '<div style="background: white; color: black; padding: 20px;">Test Content</div>';
        $uuid = ImageGenerationService::generateImage($markup, $device->id);

        // Assert the device was updated with a new image UUID
        $device->refresh();
        expect($device->current_screen_image)->toBe($uuid);

        // Assert PNG file was created (modern firmware defaults to PNG)
        Storage::disk('public')->assertExists("/images/generated/{$uuid}.png");
    }

    public function test_cleanup_folder_removes_unused_images(): void
    {
        // Create active devices with images
        $activeDevice1 = Device::factory()->create(['current_screen_image' => 'active-uuid-1']);
        $activeDevice2 = Device::factory()->create(['current_screen_image' => 'active-uuid-2']);

        // Create some test files
        Storage::disk('public')->put('/images/generated/active-uuid-1.png', 'test');
        Storage::disk('public')->put('/images/generated/active-uuid-2.png', 'test');
        Storage::disk('public')->put('/images/generated/inactive-uuid.png', 'test');
        Storage::disk('public')->put('/images/generated/another-inactive.png', 'test');

        // Run cleanup
        ImageGenerationService::cleanupFolder();

        // Assert active files are preserved
        Storage::disk('public')->assertExists('/images/generated/active-uuid-1.png');
        Storage::disk('public')->assertExists('/images/generated/active-uuid-2.png');

        // Assert inactive files are removed
        Storage::disk('public')->assertMissing('/images/generated/inactive-uuid.png');
        Storage::disk('public')->assertMissing('/images/generated/another-inactive.png');
    }

    public function test_cleanup_folder_preserves_gitignore(): void
    {
        // Create gitignore file
        Storage::disk('public')->put('/images/generated/.gitignore', '*');

        // Create some test files
        Storage::disk('public')->put('/images/generated/test.png', 'test');

        // Run cleanup
        ImageGenerationService::cleanupFolder();

        // Assert gitignore is preserved
        Storage::disk('public')->assertExists('/images/generated/.gitignore');
    }

    public function test_reset_if_not_cacheable_with_device_models(): void
    {
        // Create a plugin
        $plugin = \App\Models\Plugin::factory()->create(['current_image' => 'test-uuid']);

        // Create a device with DeviceModel (should trigger cache reset)
        Device::factory()->create([
            'device_model_id' => DeviceModel::factory()->create()->id,
        ]);

        // Run reset check
        ImageGenerationService::resetIfNotCacheable($plugin);

        // Assert plugin image was reset
        $plugin->refresh();
        expect($plugin->current_image)->toBeNull();
    }

    public function test_reset_if_not_cacheable_with_custom_dimensions(): void
    {
        // Create a plugin
        $plugin = \App\Models\Plugin::factory()->create(['current_image' => 'test-uuid']);

        // Create a device with custom dimensions (should trigger cache reset)
        Device::factory()->create([
            'width' => 1024, // Different from default 800
            'height' => 768, // Different from default 480
        ]);

        // Run reset check
        ImageGenerationService::resetIfNotCacheable($plugin);

        // Assert plugin image was reset
        $plugin->refresh();
        expect($plugin->current_image)->toBeNull();
    }

    public function test_reset_if_not_cacheable_with_standard_devices(): void
    {
        // Create a plugin
        $plugin = \App\Models\Plugin::factory()->create(['current_image' => 'test-uuid']);

        // Create devices with standard dimensions (should not trigger cache reset)
        Device::factory()->count(3)->create([
            'width' => 800,
            'height' => 480,
            'rotate' => 0,
        ]);

        // Run reset check
        ImageGenerationService::resetIfNotCacheable($plugin);

        // Assert plugin image was preserved
        $plugin->refresh();
        expect($plugin->current_image)->toBe('test-uuid');
    }

    public function test_determines_correct_image_format_from_device_model(): void
    {
        // Test BMP format detection
        $bmpModel = DeviceModel::factory()->create([
            'mime_type' => 'image/bmp',
            'bit_depth' => 1,
            'colors' => 2,
        ]);

        $device = Device::factory()->create(['device_model_id' => $bmpModel->id]);
        $markup = '<div>Test</div>';
        $uuid = ImageGenerationService::generateImage($markup, $device->id);

        $device->refresh();
        expect($device->current_screen_image)->toBe($uuid);
        Storage::disk('public')->assertExists("/images/generated/{$uuid}.bmp");

        // Test PNG 8-bit grayscale format detection
        $pngGrayscaleModel = DeviceModel::factory()->create([
            'mime_type' => 'image/png',
            'bit_depth' => 8,
            'colors' => 2,
        ]);

        $device2 = Device::factory()->create(['device_model_id' => $pngGrayscaleModel->id]);
        $uuid2 = ImageGenerationService::generateImage($markup, $device2->id);

        $device2->refresh();
        expect($device2->current_screen_image)->toBe($uuid2);
        Storage::disk('public')->assertExists("/images/generated/{$uuid2}.png");

        // Test PNG 8-bit 256 color format detection
        $png256Model = DeviceModel::factory()->create([
            'mime_type' => 'image/png',
            'bit_depth' => 8,
            'colors' => 256,
        ]);

        $device3 = Device::factory()->create(['device_model_id' => $png256Model->id]);
        $uuid3 = ImageGenerationService::generateImage($markup, $device3->id);

        $device3->refresh();
        expect($device3->current_screen_image)->toBe($uuid3);
        Storage::disk('public')->assertExists("/images/generated/{$uuid3}.png");
    }
}

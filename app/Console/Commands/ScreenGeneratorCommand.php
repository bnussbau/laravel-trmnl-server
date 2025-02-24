<?php

namespace App\Console\Commands;

use App\Models\Device;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Ramsey\Uuid\Uuid;
use Spatie\Browsershot\Browsershot;

class ScreenGeneratorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trmnl:screen:generate {deviceId=1} {view=trmnl}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $deviceId = $this->argument('deviceId');
        $view = $this->argument('view');

        $uuid = Uuid::uuid4()->toString();
        $pngPath = Storage::disk('public')->path('/images/generated/'.$uuid.'.png');
        $bmpPath = Storage::disk('public')->path('/images/generated/'.$uuid.'.bmp');

        // Generate PNG
        try {
            Browsershot::html(view($view)->render())
                ->windowSize(800, 480)
                ->save($pngPath);
        } catch (\Exception $e) {
            $this->error('Failed to generate PNG: '.$e->getMessage());

            return;
        }

        try {
            $this->convertToBmpImageMagick($pngPath, $bmpPath);

        } catch (\ImagickException $e) {
            $this->error('Failed to convert image to BMP: '.$e->getMessage());
        }

        Device::find($deviceId)->update(['current_screen_image' => $uuid]);

        $this->cleanupFolder();
    }

    /**
     * @throws \ImagickException
     */
    public function convertToBmpImageMagick(string $pngPath, string $bmpPath): void
    {
        $imagick = new \Imagick($pngPath);
        $imagick->setImageType(\Imagick::IMGTYPE_GRAYSCALE);
        $imagick->quantizeImage(2, \Imagick::COLORSPACE_GRAY, 0, true, false);
        $imagick->setImageDepth(1);
        $imagick->stripImage();
        $imagick->setFormat('BMP3');
        $imagick->writeImage($bmpPath);
        $imagick->clear();
    }

    // TODO retuns 8-bit BMP

    //    public function convertToBmpGD(string $pngPath, string $bmpPath): void
    //    {
    //        // Load the PNG image
    //        $image = imagecreatefrompng($pngPath);
    //
    //        // Create a new true color image with the same dimensions
    //        $bwImage = imagecreatetruecolor(imagesx($image), imagesy($image));
    //
    //        // Convert to black and white
    //        imagefilter($image, IMG_FILTER_GRAYSCALE);
    //
    //        // Copy the grayscale image to the new image
    //        imagecopy($bwImage, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
    //
    //        // Create a 1-bit palette
    //        imagetruecolortopalette($bwImage, true, 2);
    //
    //        // Save as BMP
    //
    //        imagebmp($bwImage, $bmpPath, false);
    //
    //        // Free up memory
    //        imagedestroy($image);
    //        imagedestroy($bwImage);
    //    }
    public function cleanupFolder(): void
    {
        $activeImageUuids = Device::pluck('current_screen_image')->filter()->toArray();

        $files = Storage::disk('public')->files('/images/generated/');
        foreach ($files as $file) {
            if (basename($file) === '.gitignore') {
                continue;
            }
            // Get filename without path and extension
            $fileUuid = pathinfo($file, PATHINFO_FILENAME);
            // If the UUID is not in use by any device, move it to archive
            if (! in_array($fileUuid, $activeImageUuids)) {
                Storage::disk('public')->delete($file);
            }
        }
    }
}

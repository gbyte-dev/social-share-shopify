<?php

namespace App\Services;

use App\Models\SocialLink;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class LogoProcessingService
{
    /**
     * Process manually uploaded logo file.
     */
    public function processUploadedFile(UploadedFile $file, int $storeId): array
    {
        $path = $file->getRealPath();
        $extension = $file->getClientOriginalExtension() ?: 'png';
        $filename = Str::random(40);
        
        return $this->saveImageVariants($path, $storeId, $filename, $extension);
    }

    /**
     * Process binary image data from automatic logo discovery.
     */
    public function processBinaryData(string $binaryData, int $storeId, string $prefix = 'discovered'): array
    {
        $filename = $prefix . '_' . Str::random(30);
        
        // Binary data ko safely temp file me dalenge native GD read ke liye
        $tempPath = sys_get_temp_dir() . '/' . $filename . '.png';
        file_put_contents($tempPath, $binaryData);
        
        $paths = $this->saveImageVariants($tempPath, $storeId, $filename, 'png');
        
        @unlink($tempPath); // Cleanup temp file
        
        return $paths;
    }

    /**
     * Save original and resized variants directly to public/uploads/logos/
     * USING CORE PHP GD FUNCTIONS (No external package)
     */
    protected function saveImageVariants(string $sourcePath, int $storeId, string $filename, string $extension): array
    {
        $baseDir = 'uploads/logos/' . $storeId;
        $fullPath = public_path($baseDir);
        
        // Agar directory nahi hai toh create karein
        if (!File::exists($fullPath)) {
            File::makeDirectory($fullPath, 0755, true);
        }

        $paths = [
            'original_logo_path' => $baseDir . '/' . $filename . '_original.' . $extension,
            'logo_large_path'    => $baseDir . '/' . $filename . '_56x56.' . $extension,
            'logo_medium_path'   => $baseDir . '/' . $filename . '_44x44.' . $extension,
            'logo_small_path'    => $baseDir . '/' . $filename . '_36x36.' . $extension,
        ];

        // 1. Save Original
        File::copy($sourcePath, public_path($paths['original_logo_path']));
        
        // 2. Process and Save Variants safely using Native PHP
        $this->resizeAndSaveNative($sourcePath, 56, public_path($paths['logo_large_path']), $extension);
        $this->resizeAndSaveNative($sourcePath, 44, public_path($paths['logo_medium_path']), $extension);
        $this->resizeAndSaveNative($sourcePath, 36, public_path($paths['logo_small_path']), $extension);

        return $paths;
    }

    /**
     * Core PHP image resizing without any third party packages.
     * Preserves PNG transparency and aspect ratio.
     */
    protected function resizeAndSaveNative(string $sourcePath, int $targetSize, string $destinationPath, string $extension): void
    {
        // 1. Image ko string se load karein
        $imageData = @file_get_contents($sourcePath);
        $sourceImage = @imagecreatefromstring($imageData);

        if (!$sourceImage) {
            // Agar file format natively supported nahi hai (like invalid SVG), toh direct copy kar do
            File::copy($sourcePath, $destinationPath);
            return;
        }

        $originalWidth = imagesx($sourceImage);
        $originalHeight = imagesy($sourceImage);

        // 2. Aspect Ratio maintain karke naye dimensions nikalein
        $ratio = $originalWidth / $originalHeight;
        if ($originalWidth > $originalHeight) {
            $newWidth = $targetSize;
            $newHeight = (int) ($targetSize / $ratio);
        } else {
            $newHeight = $targetSize;
            $newWidth = (int) ($targetSize * $ratio);
        }

        // Choti image ko zabardasti stretch na karein
        if ($originalWidth <= $targetSize && $originalHeight <= $targetSize) {
            $newWidth = $originalWidth;
            $newHeight = $originalHeight;
        }

        // 3. Nayi blank image create karein
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

        // 4. Background transparent banayein (PNG ke liye zaroori)
        imagealphablending($resizedImage, false);
        imagesavealpha($resizedImage, true);
        $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
        imagefilledrectangle($resizedImage, 0, 0, $newWidth, $newHeight, $transparent);

        // 5. Image ko copy aur scale karein
        imagecopyresampled(
            $resizedImage, $sourceImage,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $originalWidth, $originalHeight
        );

        // 6. Extension ke hisaab se sahi format me save karein
        $ext = strtolower($extension);
        if ($ext === 'jpg' || $ext === 'jpeg') {
            imagejpeg($resizedImage, $destinationPath, 90);
        } elseif ($ext === 'webp' && function_exists('imagewebp')) {
            imagewebp($resizedImage, $destinationPath, 90);
        } elseif ($ext === 'gif') {
            imagegif($resizedImage, $destinationPath);
        } else {
            // Default PNG (supports transparency)
            imagepng($resizedImage, $destinationPath, 9);
        }

        // 7. RAM memory free karein
        imagedestroy($sourceImage);
        imagedestroy($resizedImage);
    }

    /**
     * Delete old logo variants from the public folder when a new one is uploaded.
     */
    public function deleteOldLogoFiles(SocialLink $link): void
    {
        $filesToDelete = [
            $link->original_logo_path,
            $link->logo_large_path,
            $link->logo_medium_path,
            $link->logo_small_path,
        ];

        foreach ($filesToDelete as $path) {
            if ($path && File::exists(public_path($path))) {
                File::delete(public_path($path));
            }
        }
    }
}
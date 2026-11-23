<?php

namespace App\Services;

use App\Models\SocialLink;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use InvalidArgumentException;

class LogoProcessingService
{
    /**
     * Max allowed upload size in bytes (5 MB).
     */
    public const MAX_FILE_SIZE = 5242880;

    /**
     * Supported logo dimensions in pixels.
     *
     * @var array<string, int>
     */
    public const VARIANTS = [
        'small' => 36,
        'medium' => 44,
        'large' => 56,
    ];

    /**
     * Allowed MIME types and their canonical extensions.
     *
     * @var array<string, string>
     */
    protected const ALLOWED_MIMES = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/pjpeg' => 'jpg',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'image/x-icon' => 'ico',
        'image/vnd.microsoft.icon' => 'ico',
        'image/svg+xml' => 'svg',
        'image/svg' => 'svg',
    ];

    /**
     * Process manually uploaded logo file.
     *
     * @return array{original_logo_path: string, logo_small_path: string, logo_medium_path: string, logo_large_path: string}
     */
    public function processUploadedFile(UploadedFile $file, int $storeId): array
    {
        if (! $file->isValid()) {
            throw new InvalidArgumentException('Uploaded file is corrupted or incomplete.');
        }

        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new InvalidArgumentException('Logo file size must not exceed 5MB.');
        }

        $contents = file_get_contents($file->getRealPath());
        if ($contents === false || strlen($contents) === 0) {
            throw new InvalidArgumentException('Uploaded file is empty.');
        }

        return $this->processBinaryData($contents, $storeId, $file->getClientOriginalName());
    }

    /**
     * Process binary image data (from upload or remote discovery).
     *
     * @return array{original_logo_path: string, logo_small_path: string, logo_medium_path: string, logo_large_path: string}
     */
    
    
    public function processBinaryData(
        string $binaryData,
        int $storeId,
        ?string $originalFilename = null
    ): array {
        $size = strlen($binaryData);

        if ($size === 0) {
            throw new InvalidArgumentException('Image content is empty.');
        }

        if ($size > self::MAX_FILE_SIZE) {
            throw new InvalidArgumentException(
                'Image content exceeds the 5MB size limit.'
            );
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        if ($finfo === false) {
            throw new InvalidArgumentException('Unable to detect image format.');
        }

        $detectedMime = finfo_buffer($finfo, $binaryData);
        finfo_close($finfo);

        if ($this->isSvgContent($binaryData)) {
            $detectedMime = 'image/svg+xml';
        }

        // Convert ICO into PNG using Imagick.
        if (
            in_array($detectedMime, [
                'image/x-icon',
                'image/vnd.microsoft.icon',
            ], true) ||
            $this->isIcoContent($binaryData)
        ) {
            $binaryData = $this->convertIcoToPng($binaryData);
            $detectedMime = 'image/png';

            // Validate Imagick output before passing it to GD.
            $imageInfo = @getimagesizefromstring($binaryData);

            $gdImage = @imagecreatefromstring($binaryData);

            Log::info('ICO PNG conversion diagnostics', [
                'output_size' => strlen($binaryData),
                'signature' => bin2hex(substr($binaryData, 0, 8)),
                'image_info' => $imageInfo ?: false,
                'gd_decode_success' => $gdImage !== false,
            ]);

            if ($gdImage !== false) {
                imagedestroy($gdImage);
            }

            if ($imageInfo === false || $gdImage === false) {
                throw new InvalidArgumentException(
                    'Converted ICO image is not readable by GD.'
                );
            }

        }

        $allowedMimes = [
            'image/png',
            'image/jpeg',
            'image/pjpeg',
            'image/webp',
            'image/gif',
            'image/svg+xml',
            'image/svg',
        ];

        if (! in_array($detectedMime, $allowedMimes, true)) {
            throw new InvalidArgumentException(
                "Unsupported image format ({$detectedMime}). Allowed formats: PNG, JPG, WebP, GIF, SVG, ICO."
            );
        }

        $ext = self::ALLOWED_MIMES[$detectedMime];

        // Hash converted content so stored PNG files have consistent names.
        $hash = sha1($binaryData);

        $disk = Storage::disk('public');
        $directory = "logos/{$storeId}";

        if (in_array($detectedMime, ['image/svg+xml', 'image/svg'], true)) {
            return $this->processSvg(
                $binaryData,
                $storeId,
                $hash,
                $disk,
                $directory
            );
        }

        return $this->processRasterImage(
            $binaryData,
            $ext,
            $storeId,
            $hash,
            $disk,
            $directory
        );
    }



    /**
     * Detect ICO binary signature.
     */
    protected function isIcoContent(string $content): bool
    {
        if (strlen($content) < 6) {
            return false;
        }

        $header = unpack('vreserved/vtype/vcount', substr($content, 0, 6));

        return is_array($header)
            && $header['reserved'] === 0
            && $header['type'] === 1
            && $header['count'] > 0;
    }

    /**
     * Convert ICO binary into PNG using Imagick.
     * Selects the largest available valid image.
     */
   
    
    protected function convertIcoToPng(string $binaryData): string
    {
        if (!extension_loaded('imagick')) {
            throw new InvalidArgumentException('Imagick extension is required.');
        }

        $ico = null;
        $frame = null;
        $tmpFile = null;
        $icoPath = null;

        try {
            // Create a temporary file with .ico extension.
            $tmpFile = tempnam(sys_get_temp_dir(), 'socialnest_ico_');

            if ($tmpFile === false) {
                throw new InvalidArgumentException(
                    'Unable to create temporary ICO file.'
                );
            }

            $icoPath = $tmpFile . '.ico';

            if (!rename($tmpFile, $icoPath)) {
                throw new InvalidArgumentException(
                    'Unable to prepare temporary ICO file.'
                );
            }

            // Write uploaded ICO binary.
            $written = file_put_contents($icoPath, $binaryData);

            if ($written === false || $written !== strlen($binaryData)) {
                throw new InvalidArgumentException(
                    'Unable to write complete ICO file.'
                );
            }

            Log::info('ICO decoding started', [
                'php_version' => PHP_VERSION,
                'imagick_version' => \Imagick::getVersion(),
                'ico_formats' => \Imagick::queryFormats('ICO'),
                'input_size' => strlen($binaryData),
                'input_header' => bin2hex(substr($binaryData, 0, 8)),
                'temp_file_exists' => is_file($icoPath),
            ]);

            // Read ICO from its actual .ico file.
            $ico = new \Imagick();
            $ico->readImage($icoPath);

            $frameCount = $ico->getNumberImages();

            Log::info('ICO frames detected', [
                'frame_count' => $frameCount,
            ]);

            if ($frameCount < 1) {
                throw new InvalidArgumentException(
                    'ICO contains no image frames.'
                );
            }

            // Select the largest valid frame.
            $bestArea = 0;

            for ($i = 0; $i < $frameCount; $i++) {
                $ico->setIteratorIndex($i);

                $width = $ico->getImageWidth();
                $height = $ico->getImageHeight();

                if (
                    $width < 1 ||
                    $height < 1 ||
                    $width > 4096 ||
                    $height > 4096
                ) {
                    continue;
                }

                $area = $width * $height;

                if ($area > $bestArea) {
                    if ($frame instanceof \Imagick) {
                        $frame->clear();
                        $frame->destroy();
                    }

                    $frame = $ico->getImage();
                    $bestArea = $area;
                }
            }

            if (!($frame instanceof \Imagick)) {
                throw new InvalidArgumentException(
                    'No valid ICO frame found.'
                );
            }

            // Encode selected frame as PNG.
            $frame->setImageFormat('PNG');
            $frame->stripImage();

            $pngData = $frame->getImageBlob();

            // Verify PNG signature.
            if (
                strlen($pngData) < 8 ||
                substr($pngData, 0, 8) !== "\x89PNG\r\n\x1a\n"
            ) {
                throw new InvalidArgumentException(
                    'Imagick did not produce valid PNG output.'
                );
            }

            // Verify PNG can also be decoded by GD.
            $gdImage = @imagecreatefromstring($pngData);

            if ($gdImage === false) {
                throw new InvalidArgumentException(
                    'Converted PNG cannot be decoded by GD.'
                );
            }

            imagedestroy($gdImage);

            Log::info('ICO successfully converted to PNG', [
                'frame_count' => $frameCount,
                'selected_width' => $frame->getImageWidth(),
                'selected_height' => $frame->getImageHeight(),
                'png_size' => strlen($pngData),
                'png_signature' => bin2hex(substr($pngData, 0, 8)),
            ]);

            return $pngData;

        } catch (\Throwable $e) {
            Log::error('ICO conversion failed', [
                'error' => $e->getMessage(),
                'input_size' => strlen($binaryData),
                'input_header' => bin2hex(substr($binaryData, 0, 8)),
            ]);

            throw new InvalidArgumentException(
                'Unable to convert ICO logo into PNG.',
                0,
                $e
            );

        } finally {
            if ($frame instanceof \Imagick) {
                $frame->clear();
                $frame->destroy();
            }

            if ($ico instanceof \Imagick) {
                $ico->clear();
                $ico->destroy();
            }

            if ($icoPath !== null && is_file($icoPath)) {
                @unlink($icoPath);
            }

            if ($tmpFile !== null && is_file($tmpFile)) {
                @unlink($tmpFile);
            }
        }
    }

    /**
     * Process and sanitize SVG images.
     *
     * @return array{original_logo_path: string, logo_small_path: string, logo_medium_path: string, logo_large_path: string}
     */
    protected function processSvg(string $svgContent, int $storeId, string $hash, mixed $disk, string $directory): array
    {
        $sanitized = $this->sanitizeSvg($svgContent);
        if (! $sanitized) {
            throw new InvalidArgumentException('Unsafe or malicious SVG content detected. Scripts and external entity references are not permitted.');
        }

        $origPath = "{$directory}/{$hash}_original.svg";
        $smallPath = "{$directory}/{$hash}_36x36.svg";
        $mediumPath = "{$directory}/{$hash}_44x44.svg";
        $largePath = "{$directory}/{$hash}_56x56.svg";

        $disk->put($origPath, $sanitized);
        $disk->put($smallPath, $sanitized);
        $disk->put($mediumPath, $sanitized);
        $disk->put($largePath, $sanitized);

        // Also mirror to public/uploads/logos/{storeId} for hosts without symlinks
        $this->mirrorToPublicUploads($storeId, [
            "{$hash}_original.svg" => $sanitized,
            "{$hash}_36x36.svg" => $sanitized,
            "{$hash}_44x44.svg" => $sanitized,
            "{$hash}_56x56.svg" => $sanitized,
        ]);

        return [
            'original_logo_path' => $origPath,
            'logo_small_path' => $smallPath,
            'logo_medium_path' => $mediumPath,
            'logo_large_path' => $largePath,
        ];
    }

    /**
     * Process raster images (PNG, JPEG, WebP, GIF, ICO) using GD.
     * Generates square transparent variants: 36x36, 44x44, 56x56.
     *
     * @return array{original_logo_path: string, logo_small_path: string, logo_medium_path: string, logo_large_path: string}
     */
    protected function processRasterImage(string $binaryData, string $ext, int $storeId, string $hash, mixed $disk, string $directory): array
    {
        $srcImage = @imagecreatefromstring($binaryData);
        if ($srcImage === false) {
            throw new InvalidArgumentException('Corrupt or invalid image content.');
        }

        $srcWidth = imagesx($srcImage);
        $srcHeight = imagesy($srcImage);

        if ($srcWidth <= 0 || $srcHeight <= 0) {
            imagedestroy($srcImage);
            throw new InvalidArgumentException('Image has invalid dimensions.');
        }

        $origPath = "{$directory}/{$hash}_original.{$ext}";
        $disk->put($origPath, $binaryData);

        $mirrorFiles = [
            "{$hash}_original.{$ext}" => $binaryData,
        ];

        $paths = [
            'original_logo_path' => $origPath,
        ];

        // Generate each configured variant
        foreach (self::VARIANTS as $variantName => $targetSize) {
            $canvas = imagecreatetruecolor($targetSize, $targetSize);
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparentColor = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefilledrectangle($canvas, 0, 0, $targetSize, $targetSize, $transparentColor);
            imagealphablending($canvas, true);

            // Compute aspect-ratio fitting
            $scale = min($targetSize / $srcWidth, $targetSize / $srcHeight);
            $dstWidth = max(1, (int) round($srcWidth * $scale));
            $dstHeight = max(1, (int) round($srcHeight * $scale));
            $dstX = (int) round(($targetSize - $dstWidth) / 2);
            $dstY = (int) round(($targetSize - $dstHeight) / 2);

            imagecopyresampled(
                $canvas,
                $srcImage,
                $dstX,
                $dstY,
                0,
                0,
                $dstWidth,
                $dstHeight,
                $srcWidth,
                $srcHeight
            );

            // Output PNG variant
            ob_start();
            imagepng($canvas, null, 8);
            $pngData = (string) ob_get_clean();
            $pngPath = "{$directory}/{$hash}_{$targetSize}x{$targetSize}.png";
            $disk->put($pngPath, $pngData);

            $mirrorFiles["{$hash}_{$targetSize}x{$targetSize}.png"] = $pngData;

            // Output WebP variant if supported
            if (function_exists('imagewebp')) {
                ob_start();
                imagewebp($canvas, null, 90);
                $webpData = (string) ob_get_clean();
                $webpPath = "{$directory}/{$hash}_{$targetSize}x{$targetSize}.webp";
                $disk->put($webpPath, $webpData);

                $mirrorFiles["{$hash}_{$targetSize}x{$targetSize}.webp"] = $webpData;
            }

            imagedestroy($canvas);

            $paths["logo_{$variantName}_path"] = $pngPath;
        }

        imagedestroy($srcImage);

        $this->mirrorToPublicUploads($storeId, $mirrorFiles);

        return [
            'original_logo_path' => $paths['original_logo_path'],
            'logo_small_path' => $paths['logo_small_path'],
            'logo_medium_path' => $paths['logo_medium_path'],
            'logo_large_path' => $paths['logo_large_path'],
        ];
    }

    /**
     * Mirror saved files to public/uploads/logos/{storeId} when not running tests.
     *
     * @param  array<string, string>  $files  [filename => binaryContent]
     */
    protected function mirrorToPublicUploads(int $storeId, array $files): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        $destDir = public_path("uploads/logos/{$storeId}");

        try {
            if (! File::exists($destDir)) {
                File::makeDirectory($destDir, 0755, true);
            }

            foreach ($files as $filename => $content) {
                $written = File::put("{$destDir}/{$filename}", $content);

                if ($written === false) {
                    throw new RuntimeException("Unable to write logo file: {$filename}");
                }
            }
        } catch (\Throwable $e) {
            Log::error('Logo public mirror failed', [
                'store_id' => $storeId,
                'directory' => $destDir,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException(
                'Unable to save logo to public uploads directory.',
                0,
                $e
            );
        }
    }

    /**
     * Create clean default SVG logo variants for custom platform.
     *
     * @return array{original_logo_path: string, logo_small_path: string, logo_medium_path: string, logo_large_path: string}
     */
    public function createDefaultSvgLogo(int $storeId, string $platformName): array
    {
        $hash = 'default_' . substr(sha1(strtolower(trim($platformName))), 0, 16);
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
            . '<circle cx="12" cy="12" r="10" fill="#4f46e5" stroke="#4f46e5"/>'
            . '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>'
            . '<path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>'
            . '</svg>';

        return $this->processSvg($svg, $storeId, $hash, Storage::disk('public'), "logos/{$storeId}");
    }

    /**
     * Inspect whether raw content looks like an SVG.
     */
    protected function isSvgContent(string $content): bool
    {
        $trimmed = ltrim($content);

        return str_starts_with($trimmed, '<svg') || (str_starts_with($trimmed, '<?xml') && str_contains($trimmed, '<svg'));
    }

    /**
     * Sanitize SVG against XSS, XXE, and embedded scripts.
     * Returns sanitized SVG string or false if unsafe content is detected.
     */
    public function sanitizeSvg(string $svg): string|false
    {
        // Reject XXE / DOCTYPE entity attacks
        if (preg_match('/<!ENTITY|<!DOCTYPE|SYSTEM/i', $svg)) {
            return false;
        }

        // Reject script tags
        if (preg_match('/<script[\s>]/i', $svg)) {
            return false;
        }

        // Reject inline javascript:, vbscript:, data: URIs in attributes
        if (preg_match('/\b(href|src|xlink:href)\s*=\s*["\']\s*(?:javascript|vbscript|data):/i', $svg)) {
            return false;
        }

        // Reject inline event handlers (onload, onerror, onclick, etc.)
        if (preg_match('/\bon[a-z]+\s*=/i', $svg)) {
            return false;
        }

        // Must contain valid <svg tag
        if (! preg_match('/<svg[^>]*>[\s\S]*<\/svg>/i', $svg)) {
            return false;
        }

        return trim($svg);
    }

    /**
     * Clean up orphaned logo files for a link without deleting files referenced by other links.
     */
    public function deleteOldLogoFiles(SocialLink $link): void
    {
        $pathsToCheck = array_filter([
            $link->original_logo_path,
            $link->logo_small_path,
            $link->logo_medium_path,
            $link->logo_large_path,
        ]);

        $disk = Storage::disk('public');

        foreach ($pathsToCheck as $path) {
            // Check if any other link uses this exact path
            $isReferenced = SocialLink::where('id', '!=', $link->id)
                ->where(function ($query) use ($path): void {
                    $query->where('original_logo_path', $path)
                        ->orWhere('logo_small_path', $path)
                        ->orWhere('logo_medium_path', $path)
                        ->orWhere('logo_large_path', $path);
                })
                ->exists();

            if (! $isReferenced && $disk->exists($path)) {
                $disk->delete($path);

                // Also delete corresponding .webp if it exists
                $webpPath = preg_replace('/\.png$/i', '.webp', $path);
                if ($webpPath && $webpPath !== $path && $disk->exists($webpPath)) {
                    $disk->delete($webpPath);
                }
            }

            // Also check public/uploads/
            if (! $isReferenced && ! app()->runningUnitTests()) {
                $publicFile = public_path('uploads/'.ltrim($path, '/'));
                if (File::exists($publicFile)) {
                    File::delete($publicFile);
                }
            }
        }
    }
}

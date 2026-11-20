<?php

namespace App\Services;

use App\Models\SocialLink;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
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
    public function processBinaryData(string $binaryData, int $storeId, ?string $originalFilename = null): array
    {
        $size = strlen($binaryData);
        if ($size === 0) {
            throw new InvalidArgumentException('Image content is empty.');
        }

        if ($size > self::MAX_FILE_SIZE) {
            throw new InvalidArgumentException('Image content exceeds the 5MB size limit.');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMime = finfo_buffer($finfo, $binaryData);
        finfo_close($finfo);

        // Check if content is SVG regardless of finfo classification
        if ($this->isSvgContent($binaryData)) {
            $detectedMime = 'image/svg+xml';
        }

        if (! isset(self::ALLOWED_MIMES[$detectedMime])) {
            throw new InvalidArgumentException("Unsupported image format ({$detectedMime}). Allowed formats: PNG, JPG, WebP, GIF, ICO, SVG.");
        }

        $ext = self::ALLOWED_MIMES[$detectedMime];
        $hash = sha1($binaryData);
        $disk = Storage::disk('public');
        $directory = "logos/{$storeId}";

        if ($detectedMime === 'image/svg+xml') {
            return $this->processSvg($binaryData, $storeId, $hash, $disk, $directory);
        }

        return $this->processRasterImage($binaryData, $ext, $storeId, $hash, $disk, $directory);
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

        try {
            $destDir = public_path("uploads/logos/{$storeId}");
            if (! File::exists($destDir)) {
                File::makeDirectory($destDir, 0755, true);
            }

            foreach ($files as $filename => $content) {
                File::put("{$destDir}/{$filename}", $content);
            }
        } catch (\Throwable) {
            // Silently ignore mirror errors if public folder is restricted
        }
    }

    /**
     * Create clean default SVG logo variants for custom platform.
     *
     * @return array{original_logo_path: string, logo_small_path: string, logo_medium_path: string, logo_large_path: string}
     */
    public function createDefaultSvgLogo(int $storeId, string $platformName): array
    {
        $hash = 'default_'.substr(sha1(strtolower(trim($platformName))), 0, 16);
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
            .'<circle cx="12" cy="12" r="10" fill="#4f46e5" stroke="#4f46e5"/>'
            .'<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>'
            .'<path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>'
            .'</svg>';

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
                $publicFile = public_path($path);
                if (File::exists($publicFile)) {
                    File::delete($publicFile);
                }
            }
        }
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ThumbnailService
{
    /**
     * Supported MIME types for GD processing.
     */
    private const SUPPORTED_MIMES = [
        'image/jpeg',
        'image/pjpeg',
        'image/png',
        'image/x-png',
        'image/webp',
    ];

    /**
     * Generate a thumbnail preserving aspect ratio without upscaling.
     *
     * @param string $sourcePath Absolute filesystem path to the source image
     * @param string|null $destinationPath Optional explicit destination path; defaults to thumbnails/ subdir
     * @param int $maxWidth Maximum width of the thumbnail (default: 300)
     * @param int $maxHeight Maximum height of the thumbnail (default: 300)
     * @param int $quality Compression quality for JPEG/WEBP (default: 80)
     * @return string|null Absolute path to generated thumbnail, or null on failure
     */
    public function generateThumbnail(
        string $sourcePath,
        ?string $destinationPath = null,
        int $maxWidth = 300,
        int $maxHeight = 300,
        int $quality = 80
    ): ?string {
        // 1. Security & path traversal validations
        if (! $this->isSafePath($sourcePath)) {
            Log::warning('ThumbnailService: Blocked unsafe source path or traversal attempt', ['path' => $sourcePath]);
            return null;
        }

        if (! is_file($sourcePath) || ! is_readable($sourcePath)) {
            Log::warning('ThumbnailService: Source file does not exist or is not readable', ['path' => $sourcePath]);
            return null;
        }

        // 2. MIME & content validation
        $mime = $this->detectMimeType($sourcePath);
        if ($mime === null || ! in_array($mime, self::SUPPORTED_MIMES, true)) {
            // Non-image or unsupported format (e.g. PDF, SVG, HEIC) - skip without error
            return null;
        }

        // 3. Determine destination path
        if ($destinationPath === null) {
            $destinationPath = $this->defaultThumbnailPath($sourcePath, $maxWidth, $maxHeight);
        }

        if (! $this->isSafePath($destinationPath)) {
            Log::warning('ThumbnailService: Blocked unsafe destination path', ['path' => $destinationPath]);
            return null;
        }

        // Strict protection: Never overwrite the original file!
        if (realpath($sourcePath) && realpath($destinationPath) && realpath($sourcePath) === realpath($destinationPath)) {
            Log::error('ThumbnailService: Destination path matches source path, overwrite prevented', [
                'source' => $sourcePath,
            ]);
            return null;
        }

        // Return existing thumbnail if already generated and newer than source
        if (is_file($destinationPath) && filemtime($destinationPath) >= filemtime($sourcePath)) {
            return $destinationPath;
        }

        // 4. Ensure destination directory exists
        $destDir = dirname($destinationPath);
        if (! is_dir($destDir) && ! @mkdir($destDir, 0755, true) && ! is_dir($destDir)) {
            Log::error('ThumbnailService: Failed to create destination directory', ['dir' => $destDir]);
            return null;
        }

        // 5. Read and decode image using GD
        try {
            $contents = @file_get_contents($sourcePath);
            if ($contents === false) {
                return null;
            }

            $sourceImage = @imagecreatefromstring($contents);
            unset($contents);

            if ($sourceImage === false) {
                Log::warning('ThumbnailService: GD failed to decode image content', ['source' => $sourcePath]);
                return null;
            }

            $origWidth = imagesx($sourceImage);
            $origHeight = imagesy($sourceImage);

            if ($origWidth <= 0 || $origHeight <= 0) {
                $this->destroyGdImage($sourceImage);
                return null;
            }

            // 6. Calculate aspect-ratio preserving dimensions (no upscaling)
            $ratio = min($maxWidth / $origWidth, $maxHeight / $origHeight, 1.0);
            $targetWidth = max(1, (int) round($origWidth * $ratio));
            $targetHeight = max(1, (int) round($origHeight * $ratio));

            $thumbImage = imagecreatetruecolor($targetWidth, $targetHeight);
            if ($thumbImage === false) {
                $this->destroyGdImage($sourceImage);
                return null;
            }

            // Preserve alpha transparency for PNG and WebP
            if ($mime === 'image/png' || $mime === 'image/x-png' || $mime === 'image/webp') {
                imagealphablending($thumbImage, false);
                imagesavealpha($thumbImage, true);
                $transparent = imagecolorallocatealpha($thumbImage, 255, 255, 255, 127);
                imagefilledrectangle($thumbImage, 0, 0, $targetWidth, $targetHeight, $transparent);
            }

            imagecopyresampled(
                $thumbImage,
                $sourceImage,
                0,
                0,
                0,
                0,
                $targetWidth,
                $targetHeight,
                $origWidth,
                $origHeight
            );

            // 7. Write to destination file
            $written = false;
            if ($mime === 'image/png' || $mime === 'image/x-png') {
                $written = @imagepng($thumbImage, $destinationPath, 8);
            } elseif ($mime === 'image/webp' && function_exists('imagewebp')) {
                $written = @imagewebp($thumbImage, $destinationPath, $quality);
            } else {
                $written = @imagejpeg($thumbImage, $destinationPath, $quality);
            }

            $this->destroyGdImage($sourceImage);
            $this->destroyGdImage($thumbImage);

            return $written ? $destinationPath : null;
        } catch (\Throwable $e) {
            Log::error('ThumbnailService: Exception while generating thumbnail', [
                'error' => $e->getMessage(),
                'source' => $sourcePath,
            ]);
            return null;
        }
    }

    /**
     * Get thumbnail URL with graceful fallback to original URL.
     *
     * @param string|null $originalUrl Full original image URL or relative path
     * @param int $maxWidth Max thumbnail width
     * @param int $maxHeight Max thumbnail height
     * @return string|null Thumbnail URL if generated/available, otherwise fallback to original URL
     */
    public function getThumbnailUrl(?string $originalUrl, int $maxWidth = 300, int $maxHeight = 300): ?string
    {
        if (empty($originalUrl)) {
            return null;
        }

        // If it's a relative storage path like 'storage/uploads/foo.jpg'
        $cleaned = ltrim(parse_url($originalUrl, PHP_URL_PATH) ?? '', '/');
        $publicFilePath = public_path($cleaned);

        if (is_file($publicFilePath)) {
            $thumbPath = $this->generateThumbnail($publicFilePath, null, $maxWidth, $maxHeight);
            if ($thumbPath && is_file($thumbPath)) {
                // Convert absolute path back to public URL
                $relative = str_replace(public_path(), '', $thumbPath);
                $relative = str_replace('\\', '/', ltrim($relative, '/\\'));
                return asset($relative);
            }
        }

        // Safe fallback: return the original URL intact
        return $originalUrl;
    }

    /**
     * Validate path against traversal attacks, remote schemes, and null bytes.
     */
    private function isSafePath(string $path): bool
    {
        if (str_contains($path, "\0")) {
            return false;
        }

        if (preg_match('#^[a-zA-Z][a-zA-Z0-9+.-]*://#', $path)) {
            return false; // Remote URL schemes not permitted
        }

        if (str_contains($path, '..')) {
            return false; // Traversal blocked
        }

        return true;
    }

    /**
     * Detect MIME type safely via finfo.
     */
    private function detectMimeType(string $path): ?string
    {
        if (! function_exists('finfo_open')) {
            return @mime_content_type($path) ?: null;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            return null;
        }

        $mime = finfo_file($finfo, $path);
        finfo_close($finfo);

        return is_string($mime) ? strtolower($mime) : null;
    }

    /**
     * Compute a predictable thumbnail destination path.
     */
    private function defaultThumbnailPath(string $sourcePath, int $maxWidth, int $maxHeight): string
    {
        $dir = dirname($sourcePath);
        $filename = pathinfo($sourcePath, PATHINFO_FILENAME);
        $ext = pathinfo($sourcePath, PATHINFO_EXTENSION);

        return $dir . DIRECTORY_SEPARATOR . 'thumbnails' . DIRECTORY_SEPARATOR . "{$filename}_{$maxWidth}x{$maxHeight}.{$ext}";
    }

    /**
     * Safely destroy GD resource or object across PHP versions.
     */
    private function destroyGdImage($image): void
    {
        if (is_resource($image)) {
            @imagedestroy($image);
        }
    }
}

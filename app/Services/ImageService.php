<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Single source of truth for all article image operations.
 *
 * Storage convention:
 *   Main images → uploads/news/{newsId}/filename.jpg
 *   Thumbnails  → uploads/news/{newsId}/thumb/filename_thumb.jpg
 *
 * The DB always stores BARE FILENAMES (e.g. "photo.jpg", "photo_thumb.jpg").
 * The path is deterministic from news_id, so it is never stored.
 * Use resolvePath(newsId, filename) to construct full disk paths on the fly.
 */
class ImageService
{
    private const DISK = 'public';
    private const TEMP_DIR = 'uploads/news/temp';
    private const THUMB_WIDTH = 150;
    private const THUMB_HEIGHT = 150;
    private const THUMB_QUALITY = 85;

    // ─── Path Resolution ────────────────────────────────────────────

    /**
     * Resolve a stored value to a full relative path on disk.
     * Handles both legacy bare filenames and modern full paths.
     */
    public static function resolvePath(int $newsId, ?string $stored): ?string
    {
        if (!$stored) return null;
        if (str_contains($stored, '/')) return $stored;

        return "uploads/news/{$newsId}/{$stored}";
    }

    /**
     * Resolve a stored thumbnail value to a full relative path.
     * Thumbnails live in the /thumb/ subdirectory.
     */
    public static function resolveThumbPath(int $newsId, ?string $stored): ?string
    {
        if (!$stored) return null;
        if (str_contains($stored, '/')) return $stored;

        return "uploads/news/{$newsId}/thumb/{$stored}";
    }

    /**
     * Build a URL-ready path (for API responses) from a stored image value.
     */
    public static function toUrl(int $newsId, ?string $stored): ?string
    {
        $path = self::resolvePath($newsId, $stored);

        return $path ? '/' . ltrim($path, '/') : null;
    }

    /**
     * Build a URL-ready path for a thumbnail.
     */
    public static function thumbUrl(int $newsId, ?string $stored): ?string
    {
        $path = self::resolveThumbPath($newsId, $stored);

        return $path ? '/' . ltrim($path, '/') : null;
    }

    // ─── Path Classification ────────────────────────────────────────

    /**
     * Is this a temp upload path that needs to be moved?
     */
    public static function isTempPath(?string $path): bool
    {
        return $path && str_starts_with($path, self::TEMP_DIR);
    }

    /**
     * Is this path already in the article's final directory?
     */
    public static function isInArticleDir(?string $path, int $newsId): bool
    {
        return $path && str_contains($path, "uploads/news/{$newsId}/");
    }

    /**
     * Is this a bare filename (no directory component)?
     */
    public static function isBareFilename(?string $path): bool
    {
        return $path && !str_contains($path, '/');
    }

    // ─── File Operations ────────────────────────────────────────────

    /**
     * Move an image from temp to the article's directory and generate a thumbnail.
     *
     * @return array{filename: string, thumbFilename: string}
     *   - filename:      bare filename of the moved image  (e.g. "photo.jpg")
     *   - thumbFilename: bare filename of the thumbnail     (e.g. "photo_thumb.jpg"), or '' on failure
     */
    public static function moveFromTemp(string $tempPath, int $newsId): array
    {
        $disk = Storage::disk(self::DISK);
        $fileName = basename($tempPath);
        $articleDir = "uploads/news/{$newsId}";
        $finalPath = "{$articleDir}/{$fileName}";
        $thumbDir = "{$articleDir}/thumb";
        $thumbFileName = pathinfo($fileName, PATHINFO_FILENAME) . '_thumb.jpg';
        $thumbPath = "{$thumbDir}/{$thumbFileName}";

        $disk->makeDirectory($articleDir);
        $disk->makeDirectory($thumbDir);

        if (!$disk->exists($tempPath)) {
            Log::error('[ImageService] Temp file not found', [
                'tempPath' => $tempPath,
                'newsId' => $newsId,
            ]);

            return ['filename' => $fileName, 'thumbFilename' => ''];
        }

        $disk->move($tempPath, $finalPath);

        $thumbSuccess = self::createThumbnail(
            $disk->path($finalPath),
            $disk->path($thumbPath),
        );

        Log::info('[ImageService] Image processed', [
            'newsId' => $newsId,
            'from' => $tempPath,
            'to' => $finalPath,
            'thumbnail' => $thumbSuccess ? $thumbFileName : 'failed',
        ]);

        return [
            'filename' => $fileName,
            'thumbFilename' => $thumbSuccess ? $thumbFileName : '',
        ];
    }

    /**
     * Delete an image and its thumbnail from disk.
     */
    public static function deleteImage(int $newsId, ?string $stored): void
    {
        if (!$stored) return;

        $disk = Storage::disk(self::DISK);
        $path = self::resolvePath($newsId, $stored);

        if ($path && $disk->exists($path)) {
            $disk->delete($path);
        }

        // Derive and delete the associated thumbnail
        $thumbPath = "uploads/news/{$newsId}/thumb/"
            . pathinfo(basename($stored), PATHINFO_FILENAME)
            . '_thumb.jpg';

        if ($disk->exists($thumbPath)) {
            $disk->delete($thumbPath);
        }
    }

    // ─── Thumbnail Generation ───────────────────────────────────────

    /**
     * Create a JPEG thumbnail. Returns true on success.
     */
    private static function createThumbnail(string $sourcePath, string $destPath): bool
    {
        try {
            $info = @getimagesize($sourcePath);
            if (!$info) return false;

            $source = match ($info['mime']) {
                'image/jpeg' => imagecreatefromjpeg($sourcePath),
                'image/png' => imagecreatefrompng($sourcePath),
                'image/gif' => imagecreatefromgif($sourcePath),
                default => null,
            };

            if (!$source) return false;

            $thumb = imagecreatetruecolor(self::THUMB_WIDTH, self::THUMB_HEIGHT);
            imagecopyresampled(
                $thumb, $source,
                0, 0, 0, 0,
                self::THUMB_WIDTH, self::THUMB_HEIGHT,
                imagesx($source), imagesy($source),
            );
            imagejpeg($thumb, $destPath, self::THUMB_QUALITY);
            imagedestroy($source);
            imagedestroy($thumb);

            return true;
        } catch (\Throwable $e) {
            Log::warning('[ImageService] Thumbnail creation failed', [
                'source' => $sourcePath,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}

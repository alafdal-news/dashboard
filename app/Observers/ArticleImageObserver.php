<?php

namespace App\Observers;

use App\Models\ArticleImage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ArticleImageObserver
{

    public function saved(ArticleImage $gallery): void
    {
        // Get the raw image_name value from attributes (bypasses accessor)
        $rawImageName = $gallery->getAttributes()['image_name'] ?? null;
        
        if ($gallery->isDirty('image_name') && $rawImageName) {
            Log::info('[ArticleImageObserver] Processing gallery image', [
                'gallery_id' => $gallery->gallery_id,
                'news_id' => $gallery->news_id,
                'raw_image_name' => $rawImageName,
            ]);
            $this->processGalleryImage($gallery, $rawImageName);
        }
    }

    /**
     * Process gallery image: Move from temp to article folder and create thumbnail
     */
    private function processGalleryImage(ArticleImage $gallery, string $imagePath): void
    {
        $disk = Storage::disk('public');
        
        // Safety: Ensure we have a parent news_id
        if (!$gallery->news_id) {
            Log::warning('[ArticleImageObserver] No news_id set, skipping', [
                'gallery_id' => $gallery->gallery_id,
            ]);
            return;
        }
        
        // If it's already in the correct folder, just store filename
        if (str_contains($imagePath, "uploads/news/{$gallery->news_id}/")) {
            $fileName = basename($imagePath);
            $gallery->setRawAttributes(array_merge(
                $gallery->getAttributes(),
                ['image_name' => $fileName]
            ));
            $gallery->saveQuietly();
            return;
        }

        // If it's just a filename (no path), it's already processed
        if (!str_contains($imagePath, '/')) {
            Log::info('[ArticleImageObserver] Bare filename, already processed', [
                'gallery_id' => $gallery->gallery_id,
                'news_id' => $gallery->news_id,
                'image' => $imagePath,
            ]);
            return;
        }

        // It's a temp file, need to move it
        $fileName = basename($imagePath);
        $newDir = "uploads/news/{$gallery->news_id}/";
        $newPath = $newDir . $fileName;
        $thumbDir = $newDir . "thumb/";
        $thumbName = pathinfo($fileName, PATHINFO_FILENAME) . "_thumb.jpg";
        $thumbPath = $thumbDir . $thumbName;

        Log::info('[ArticleImageObserver] Moving gallery image from temp', [
            'gallery_id' => $gallery->gallery_id,
            'news_id' => $gallery->news_id,
            'from' => $imagePath,
            'to' => $newPath,
        ]);

        // Create directories
        if (!$disk->exists($newDir)) $disk->makeDirectory($newDir);
        if (!$disk->exists($thumbDir)) $disk->makeDirectory($thumbDir);

        // Move the main file
        if ($disk->exists($imagePath)) {
            $disk->move($imagePath, $newPath);

            // Generate thumbnail (non-critical)
            try {
                $this->createThumbnail($disk->path($newPath), $disk->path($thumbPath));
            } catch (\Throwable $e) {
                Log::warning('[ArticleImageObserver] Thumbnail creation failed', [
                    'gallery_id' => $gallery->gallery_id,
                    'error' => $e->getMessage(),
                ]);
                $thumbName = '';
            }

            // Update model with just the filename (legacy format)
            $gallery->setRawAttributes(array_merge(
                $gallery->getAttributes(),
                [
                    'image_name' => $fileName,
                    'thumb_name' => $thumbName,
                ]
            ));
            $gallery->saveQuietly();
            
            Log::info('[ArticleImageObserver] Gallery image processed', [
                'gallery_id' => $gallery->gallery_id,
                'filename' => $fileName,
            ]);
        } else {
            Log::error('[ArticleImageObserver] Source image NOT FOUND on disk!', [
                'gallery_id' => $gallery->gallery_id,
                'news_id' => $gallery->news_id,
                'imagePath' => $imagePath,
                'disk_root' => $disk->path(''),
            ]);
        }
    }

    private function createThumbnail($src, $dest)
    {
        $info = @getimagesize($src);
        if (!$info) return;

        $img = match ($info['mime']) {
            'image/jpeg' => imagecreatefromjpeg($src),
            'image/png' => imagecreatefrompng($src),
            default => null,
        };
        if (!$img) return;

        $tmp = imagecreatetruecolor(150, 150);
        imagecopyresampled($tmp, $img, 0, 0, 0, 0, 150, 150, imagesx($img), imagesy($img));
        imagejpeg($tmp, $dest, 85);
        imagedestroy($img);
        imagedestroy($tmp);
    }
}

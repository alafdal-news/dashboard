<?php

namespace App\Observers;

use App\Models\Article;
use App\Models\ArticleImage;
use App\Services\FirebaseNotificationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ArticleObserver
{

    public function saved(Article $article): void
    {
        // Get the raw image value from attributes (bypasses accessor)
        $rawImage = $article->getAttributes()['image'] ?? null;
        
        // 1. Handle Cover Image (news_tbl)
        if ($article->isDirty('image') && $rawImage) {
            Log::info('[ArticleObserver] Processing image for article', [
                'news_id' => $article->news_id,
                'raw_image' => $rawImage,
            ]);
            
            $this->processImage($article, $rawImage);
        }
    }

    /**
     * Process image: Move from temp to article folder and create thumbnail
     */
    private function processImage(Article $article, string $imagePath): void
    {
        $disk = Storage::disk('public');
        
        // If it's already in the correct folder, just store filename
        if (str_contains($imagePath, "uploads/news/{$article->news_id}/")) {
            $fileName = basename($imagePath);
            $article->setRawAttributes(array_merge(
                $article->getAttributes(),
                ['image' => $fileName]
            ));
            $article->saveQuietly();
            Log::info('[ArticleObserver] Image already in correct folder', [
                'news_id' => $article->news_id,
                'filename' => $fileName,
            ]);
            return;
        }

        // If it's just a filename (no path), it's already processed
        if (!str_contains($imagePath, '/')) {
            Log::info('[ArticleObserver] Image is bare filename, skipping', [
                'news_id' => $article->news_id,
                'image' => $imagePath,
            ]);
            return;
        }

        // It's a temp file, need to move it
        $fileName = basename($imagePath);
        $newDir = "uploads/news/{$article->news_id}/";
        $newPath = $newDir . $fileName;
        $thumbDir = $newDir . "thumb/";
        $thumbName = pathinfo($fileName, PATHINFO_FILENAME) . "_thumb.jpg";
        $thumbPath = $thumbDir . $thumbName;

        Log::info('[ArticleObserver] Moving image from temp', [
            'news_id' => $article->news_id,
            'from' => $imagePath,
            'to' => $newPath,
            'disk_root' => $disk->path(''),
        ]);

        // Create directories
        if (!$disk->exists($newDir)) $disk->makeDirectory($newDir);
        if (!$disk->exists($thumbDir)) $disk->makeDirectory($thumbDir);

        // Move the main file
        if ($disk->exists($imagePath)) {
            $disk->move($imagePath, $newPath);

            // Generate thumbnail (non-critical — don't fail if it errors)
            try {
                $this->createThumbnail($disk->path($newPath), $disk->path($thumbPath), 150, 150);
            } catch (\Throwable $e) {
                Log::warning('[ArticleObserver] Thumbnail creation failed', [
                    'news_id' => $article->news_id,
                    'error' => $e->getMessage(),
                ]);
                $thumbName = '';
            }

            // Update model with just the filename (legacy format)
            $article->setRawAttributes(array_merge(
                $article->getAttributes(),
                [
                    'image' => $fileName,
                    'thumbnail_image' => $thumbName,
                ]
            ));
            $article->saveQuietly();
            
            Log::info('[ArticleObserver] Image processed successfully', [
                'news_id' => $article->news_id,
                'filename' => $fileName,
                'final_path' => $newPath,
            ]);
        } else {
            Log::error('[ArticleObserver] Source image NOT FOUND on disk!', [
                'news_id' => $article->news_id,
                'imagePath' => $imagePath,
                'disk_root' => $disk->path(''),
                'full_path' => $disk->path($imagePath),
                'exists_check' => file_exists($disk->path($imagePath)),
            ]);
        }
    }

    private function createThumbnail($src, $dest, $w, $h)
    {
        $info = getimagesize($src);
        if (!$info) return;

        $mime = $info['mime'];
        switch ($mime) {
            case 'image/jpeg':
                $img = imagecreatefromjpeg($src);
                break;
            case 'image/png':
                $img = imagecreatefrompng($src);
                break;
            case 'image/gif':
                $img = imagecreatefromgif($src);
                break;
            default:
                return;
        }

        $width = imagesx($img);
        $height = imagesy($img);
        $tmp = imagecreatetruecolor($w, $h);
        imagecopyresampled($tmp, $img, 0, 0, 0, 0, $w, $h, $width, $height);
        imagejpeg($tmp, $dest, 85);
        imagedestroy($img);
        imagedestroy($tmp);
    }

    /**
     * Handle the Article "created" event.
     * Sends push notification if the notification flag is enabled.
     */
    public function created(Article $article): void
    {
        // Clear home page cache so new articles appear immediately
        Cache::forget('home_page_data');

        // Only send notification for new articles with notification flag enabled
        if ($article->notification) {
            try {
                $notificationService = app(FirebaseNotificationService::class);
                $notificationService->sendArticleNotification(
                    $article->news_id,
                    $article->news_title
                );
                
                Log::info('Push notification dispatched for article', [
                    'news_id' => $article->news_id,
                    'title' => $article->news_title,
                ]);
            } catch (\Throwable $e) {
                // Log error but don't fail the article creation
                Log::error('Failed to send push notification', [
                    'news_id' => $article->news_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Handle the Article "updated" event.
     */
    public function updated(Article $article): void
    {
        // Clear home page cache so updated articles appear immediately
        Cache::forget('home_page_data');
    }

    /**
     * Handle the Article "deleted" event.
     */
    public function deleted(Article $article): void
    {
        // Clear home page cache so deleted articles are removed immediately
        Cache::forget('home_page_data');
    }

    /**
     * Handle the Article "restored" event.
     */
    public function restored(Article $article): void
    {
        //
    }

    /**
     * Handle the Article "force deleted" event.
     */
    public function forceDeleted(Article $article): void
    {
        //
    }
}

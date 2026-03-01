<?php

namespace App\Observers;

use App\Models\Article;
use App\Services\FirebaseNotificationService;
use App\Services\ImageService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ArticleObserver
{
    /**
     * After every save (create or update), process a new cover image upload.
     */
    public function saved(Article $article): void
    {
        if (!$article->isDirty('image') || !$article->image) {
            return;
        }

        $imagePath = $article->image;

        // Bare filename — already in the correct DB format, nothing to do.
        if (ImageService::isBareFilename($imagePath)) {
            return;
        }

        // Already in the article's final directory — strip to bare filename.
        if (ImageService::isInArticleDir($imagePath, $article->news_id)) {
            $article->image = basename($imagePath);
            $article->saveQuietly();
            return;
        }

        // Temp upload — move to final directory and save bare filename.
        if (ImageService::isTempPath($imagePath)) {
            $result = ImageService::moveFromTemp($imagePath, $article->news_id);

            $article->image = $result['filename'];
            $article->thumbnail_image = $result['thumbFilename'];
            $article->saveQuietly();
            return;
        }

        // Unknown path format — log and leave as-is.
        Log::warning('[ArticleObserver] Unexpected image path format', [
            'news_id' => $article->news_id,
            'image' => $imagePath,
        ]);
    }

    /**
     * Handle the Article "created" event.
     */
    public function created(Article $article): void
    {
        Cache::forget('home_page_data');

        if ($article->notification) {
            try {
                app(FirebaseNotificationService::class)
                    ->sendArticleNotification($article->news_id, $article->news_title);

                Log::info('Push notification dispatched', [
                    'news_id' => $article->news_id,
                ]);
            } catch (\Throwable $e) {
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
        Cache::forget('home_page_data');
    }

    /**
     * Handle the Article "deleted" event.
     */
    public function deleted(Article $article): void
    {
        Cache::forget('home_page_data');
    }
}

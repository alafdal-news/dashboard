<?php

namespace App\Observers;

use App\Models\ArticleImage;
use App\Services\ImageService;
use Illuminate\Support\Facades\Log;

class ArticleImageObserver
{
    /**
     * After every save, process a new gallery image upload.
     */
    public function saved(ArticleImage $gallery): void
    {
        if (!$gallery->isDirty('image_name') || !$gallery->image_name) {
            return;
        }

        if (!$gallery->news_id) {
            Log::warning('[ArticleImageObserver] No news_id, skipping', [
                'gallery_id' => $gallery->gallery_id,
            ]);
            return;
        }

        $imagePath = $gallery->image_name;

        // Bare filename — already in the correct DB format, nothing to do.
        if (ImageService::isBareFilename($imagePath)) {
            return;
        }

        // Already in the article's final directory — strip to bare filename.
        if (ImageService::isInArticleDir($imagePath, $gallery->news_id)) {
            $gallery->image_name = basename($imagePath);
            $gallery->saveQuietly();
            return;
        }

        // Temp upload — move to final directory and save bare filename.
        if (ImageService::isTempPath($imagePath)) {
            $result = ImageService::moveFromTemp($imagePath, $gallery->news_id);

            $gallery->image_name = $result['filename'];
            $gallery->thumb_name = $result['thumbFilename'];
            $gallery->saveQuietly();
            return;
        }

        // Unknown path format — log and leave as-is.
        Log::warning('[ArticleImageObserver] Unexpected image path format', [
            'gallery_id' => $gallery->gallery_id,
            'news_id' => $gallery->news_id,
            'image_name' => $imagePath,
        ]);
    }
}

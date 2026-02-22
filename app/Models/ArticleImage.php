<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ArticleImage extends Model
{
    protected $table = 'news_gallery';
    protected $primaryKey = 'gallery_id';
    public $timestamps = false;

    protected $fillable = [
        'news_id',
        'image_name', // Stores "news_img_2023...jpg"
        'thumb_name', // Stores "news_img_2023..._thumb.jpg"
        'coverpage',  // 1 if it's the cover, 0 otherwise
        'active',
    ];

    /**
     * Image name accessor/mutator: Converts between filename (DB) and full path (App)
     * 
     * GET: "filename.jpg" → "uploads/news/{news_id}/filename.jpg" (deterministic, no I/O)
     * SET: "uploads/news/temp/filename.jpg" → stores as-is (observer handles)
     *      "uploads/news/{id}/filename.jpg" → stores "filename.jpg"
     */
    protected function imageName(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (!$value) return null;
                
                // If it's already a full path, return as-is
                if (str_contains($value, '/')) {
                    return $value;
                }
                
                // Convert bare filename to full path (no disk I/O needed)
                if ($this->news_id) {
                    return "uploads/news/{$this->news_id}/{$value}";
                }
                
                return $value;
            },
            set: function ($value) {
                if (!$value) return null;
                
                // If it's in the article's folder, extract just filename for DB
                if ($this->news_id && str_contains($value, "uploads/news/{$this->news_id}/")) {
                    return basename($value);
                }
                
                // For temp uploads or new files, store full path (observer will process)
                return $value;
            },
        );
    }

    // Relationship back to Article
    public function article()
    {
        return $this->belongsTo(Article::class, 'news_id');
    }

    /**
     * Get the raw image filename as stored in DB (bypasses accessor)
     */
    public function getRawImageNameAttribute(): ?string
    {
        return $this->attributes['image_name'] ?? null;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

class Article extends Model
{
    // --- 1. LEGACY CONFIGURATION ---
    protected $table = 'news_tbl';
    protected $primaryKey = 'news_id';
    public $timestamps = false; // Legacy table doesn't use standard timestamps

    // Fields we are allowed to modify
    protected $fillable = [
        'news_title',
        'news_desc',
        'active',
        'news_date',
        'id_cat',
        'important',
        'notification',
        'show_slider',
        'news_time',
        'addBy',
        'updateBy',
        'addDate',
        'updateDate',
        'views',
        'youtube_url',
        'voiceover_url',
        'author',
        'thumbnail_image',
        'image',
        'embedding',
        'user_id',
        'date_time_utc',
        'add_source',
    ];

    // --- 2. DATA CASTING (Fixing Types) ---
    protected $casts = [
        'active' => 'boolean',     // Converts enum('0', '1') to true/false
        'important' => 'boolean',
        'views' => 'integer',      // Fixes the legacy varchar issue
        'notification' => 'boolean',
        'show_slider' => 'boolean',
        'date_time_utc' => 'datetime', // Main datetime - stored as UTC in DB, displayed in user's timezone
        // 'news_date' => 'date',
        // 'addDate' => 'date',
        // 'updateDate' => 'date',
    ];

    // --- 3. ACCESSORS & MUTATORS (The "Clean Code" Layer) ---

    /**
     * Image accessor/mutator: Converts between filename (DB) and full path (App)
     * 
     * GET: "filename.jpg" → "uploads/news/{id}/filename.jpg"
     * SET: "uploads/news/temp/filename.jpg" → stores "uploads/news/temp/filename.jpg" (observer handles the rest)
     *      "uploads/news/{id}/filename.jpg" → stores "filename.jpg" (already processed)
     */
    protected function image(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (!$value) return null;
                
                // If it's already a full path, return as-is
                if (str_contains($value, '/')) {
                    return $value;
                }
                
                // Convert filename to full path for existing articles
                if ($this->news_id) {
                    $fullPath = "uploads/news/{$this->news_id}/{$value}";
                    if (Storage::disk('public')->exists($fullPath)) {
                        return $fullPath;
                    }
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

    // Title: Maps 'title' <-> 'news_title'
    protected function title(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => $attributes['news_title'],
            set: fn($value) => ['news_title' => $value],
        );
    }

    // Content: Maps 'content' <-> 'news_desc'
    protected function content(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => $attributes['news_desc'],
            set: fn($value) => ['news_desc' => $value],
        );
    }

    // Category ID: Maps 'category_id' <-> 'id_cat'
    protected function categoryId(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => $attributes['id_cat'],
            set: fn($value) => ['id_cat' => $value],
        );
    }

    // --- 4. RELATIONSHIPS ---
    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'id_cat', 'id');
    }

    // Relationship to the Gallery Table
    public function images()
    {
        return $this->hasMany(ArticleImage::class, 'news_id');
    }

    // --- 5. HELPER METHODS ---
    
    /**
     * Get the raw image filename as stored in DB (bypasses accessor)
     */
    public function getRawImageAttribute(): ?string
    {
        return $this->attributes['image'] ?? null;
    }

    /**
     * Get the directory path for this article's images
     */
    public function getImageDirectoryAttribute(): string
    {
        return "uploads/news/{$this->news_id}";
    }
}

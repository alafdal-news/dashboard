<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

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
        // NOTE: active, important, show_slider, notification are enum('0','1')
        // in the legacy DB. We use custom Attribute accessors below instead of
        // boolean casts, because boolean casts send integer 1/0 to MySQL which
        // MySQL compares against the enum INDEX (not the value), breaking queries.
        'views' => 'integer',      // Fixes the legacy varchar issue
        'date_time_utc' => 'datetime', // Main datetime - stored as UTC in DB, displayed in user's timezone
        // 'news_date' => 'date',
        // 'addDate' => 'date',
        // 'updateDate' => 'date',
    ];

    // --- ENUM BOOLEAN ACCESSORS ---
    // These handle the legacy enum('0','1') columns safely,
    // converting to/from PHP booleans without the MySQL enum index bug.

    protected function active(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value === '1',
            set: fn($value) => is_bool($value) ? ($value ? '1' : '0') : $value,
        );
    }

    protected function important(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value === '1',
            set: fn($value) => is_bool($value) ? ($value ? '1' : '0') : $value,
        );
    }

    protected function showSlider(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value === '1',
            set: fn($value) => is_bool($value) ? ($value ? '1' : '0') : $value,
        );
    }

    protected function notification(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value === '1',
            set: fn($value) => is_bool($value) ? ($value ? '1' : '0') : $value,
        );
    }

    // --- 3. ACCESSORS & MUTATORS ---
    // NOTE: Image fields (image, thumbnail_image) have NO accessors/mutators.
    // All path logic is centralized in App\Services\ImageService.
    // The DB stores full relative paths (e.g. "uploads/news/123/file.jpg").

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

    // Gallery images only (excludes the cover image entry)
    public function galleryImages()
    {
        return $this->hasMany(ArticleImage::class, 'news_id')->where('coverpage', '0');
    }

    // --- 5. HELPER METHODS ---
    // Image path helpers are in App\Services\ImageService.
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleImage extends Model
{
    protected $table = 'news_gallery';
    protected $primaryKey = 'gallery_id';
    public $timestamps = false;

    protected $fillable = [
        'news_id',
        'image_name', // Bare filename, e.g. "photo.jpg"
        'thumb_name', // Bare thumbnail filename, e.g. "photo_thumb.jpg"
        'coverpage',  // Legacy: '1' for cover, '0' otherwise (unused by new CMS)
        'active',
    ];

    // NOTE: image_name and thumb_name have NO accessors/mutators.
    // All path logic is centralized in App\Services\ImageService.

    // Relationship back to Article
    public function article()
    {
        return $this->belongsTo(Article::class, 'news_id');
    }
}

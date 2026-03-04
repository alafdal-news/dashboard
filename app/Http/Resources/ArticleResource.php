<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Cover image: always from articles table `image` column (single source of truth)
        $coverImage = $this->getCoverImage();
        
        return [
            'id' => $this->news_id,
            'title' => $this->news_title,
            'excerpt' => $this->getExcerpt(),
            'content' => $this->news_desc,
            'slug' => $this->generateSlug(),
            'image' => $coverImage,
            'thumbnail' => $this->thumbnail_image,
            'youtube_url' => $this->youtube_url ?: null,
            'voiceover_url' => $this->voiceover_url ?: null,
            'views' => (int) $this->views,
            'date' => $this->news_date,
            'time' => $this->news_time,
            'datetime_utc' => $this->date_time_utc,
            'is_important' => (bool) $this->important,
            'is_featured' => (bool) $this->show_slider,
            'author' => $this->author,
            'category' => $this->whenLoaded('category', function () {
                return new CategoryResource($this->category);
            }),
            'images' => $this->whenLoaded('galleryImages', function () {
                return $this->galleryImages->map(function ($image) {
                    return [
                        'id' => $image->gallery_id,
                        'url' => $this->buildImageUrl($image->image_name),
                    ];
                });
            }),
        ];
    }
    
    /**
     * Build a proper image URL from a stored image name/path.
     * Handles cases where the value already contains the full relative path.
     */
    private function buildImageUrl(?string $imageName): ?string
    {
        if (!$imageName) {
            return null;
        }

        // If the name already contains a path separator, treat it as a full relative path
        if (str_contains($imageName, '/')) {
            return '/' . ltrim($imageName, '/');
        }

        // Otherwise it's just a filename — prepend the expected directory
        return '/uploads/news/' . $this->news_id . '/' . $imageName;
    }

    /**
     * Get cover image URL — single source of truth: articles table `image` column.
     * Gallery images are never used as cover.
     */
    private function getCoverImage(): ?string
    {
        if ($this->image) {
            return $this->buildImageUrl($this->image);
        }

        // Default placeholder
        return '/uploads/news/p5.jpg';
    }
    
    /**
     * Generate URL-friendly slug from title
     */
    private function generateSlug(): string
    {
        // For Arabic, we'll use the ID as primary identifier
        // but create a readable slug for SEO
        $title = $this->news_title;
        $slug = preg_replace('/\s+/', '-', trim($title));
        $slug = preg_replace('/[^\p{Arabic}\p{L}\p{N}\-]/u', '', $slug);
        return mb_substr($slug, 0, 100);
    }
    
    /**
     * Get excerpt from content
     */
    private function getExcerpt(): string
    {
        $content = strip_tags(html_entity_decode($this->news_desc));
        $words = preg_split('/\s+/', $content, 31);
        
        if (count($words) > 30) {
            return implode(' ', array_slice($words, 0, 30)) . '...';
        }
        
        return $content;
    }
}

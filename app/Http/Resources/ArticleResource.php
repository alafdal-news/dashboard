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
        // Get cover image from images relationship or direct image field
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
            'images' => $this->whenLoaded('images', function () {
                return $this->images->map(function ($image) {
                    return [
                        'id' => $image->gallery_id,
                        'url' => $this->buildImageUrl($image->image_name),
                        'is_cover' => (bool) $image->coverpage,
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
     * Get cover image URL
     */
    private function getCoverImage(): ?string
    {
        // First check for image in news_gallery with coverpage = 1
        if ($this->relationLoaded('images') && $this->images->count() > 0) {
            $cover = $this->images->first(fn($img) => $img->coverpage == '1');
            if ($cover) {
                return $this->buildImageUrl($cover->image_name);
            }
            // Fallback to first image
            $first = $this->images->first();
            if ($first) {
                return $this->buildImageUrl($first->image_name);
            }
        }
        
        // Fallback to direct image field
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

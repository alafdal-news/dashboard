<?php

namespace App\Http\Resources;

use App\Services\ImageService;
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
            'thumbnail' => ImageService::thumbUrl($this->news_id, $this->thumbnail_image),
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
                return $this->images->map(fn ($image) => [
                    'id' => $image->gallery_id,
                    'url' => ImageService::toUrl($this->news_id, $image->image_name),
                    'is_cover' => (bool) $image->coverpage,
                ]);
            }),
        ];
    }
    
    /**
     * Get the cover image URL.
     *
     * Priority: article.image → first gallery image → placeholder.
     */
    private function getCoverImage(): ?string
    {
        // Primary: the article's dedicated cover image
        if ($this->image) {
            return ImageService::toUrl($this->news_id, $this->image);
        }

        // Fallback: first gallery image (covers legacy data without a direct cover)
        if ($this->relationLoaded('images') && $this->images->isNotEmpty()) {
            return ImageService::toUrl($this->news_id, $this->images->first()->image_name);
        }

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

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->generateSlug(),
            'is_parent' => (bool) $this->is_parent,
            'parent_id' => $this->parent_id,
            'article_count' => $this->whenCounted('articles'),
        ];
    }
    
    /**
     * Generate URL-friendly slug from name
     */
    private function generateSlug(): string
    {
        $name = $this->name;
        $slug = preg_replace('/\s+/', '-', trim($name));
        $slug = preg_replace('/[^\p{Arabic}\p{L}\p{N}\-]/u', '', $slug);
        return $slug;
    }
}

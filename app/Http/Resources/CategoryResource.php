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
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->generateSlug(),
            'is_parent' => (bool) $this->is_parent,
            'parent_id' => $this->parent_id,
            'article_count' => $this->whenCounted('articles'),
        ];

        // Include children when they have been eager-loaded
        if ($this->relationLoaded('children')) {
            $data['children'] = CategoryResource::collection(
                $this->children->where('active', '1')->sortBy('id')->values()
            );
        }

        return $data;
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

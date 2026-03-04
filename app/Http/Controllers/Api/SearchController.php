<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    /**
     * Search articles by title or content
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        $perPage = $request->get('per_page', 15);
        $categoryId = $request->get('category_id');
        
        if (empty($query)) {
            return response()->json([
                'success' => false,
                'message' => 'Search query is required',
                'data' => [],
            ], 400);
        }
        
        $articlesQuery = Article::with(['category', 'galleryImages'])
            ->where('active', '1')
            ->where(function ($q) use ($query) {
                $q->where('news_title', 'LIKE', "%{$query}%")
                  ->orWhere('news_desc', 'LIKE', "%{$query}%");
            })
            ->orderBy('news_id', 'desc');
        
        if ($categoryId) {
            $articlesQuery->where('id_cat', $categoryId);
        }
        
        $articles = $articlesQuery->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'query' => $query,
            'data' => ArticleResource::collection($articles),
            'meta' => [
                'current_page' => $articles->currentPage(),
                'last_page' => $articles->lastPage(),
                'per_page' => $articles->perPage(),
                'total' => $articles->total(),
            ]
        ]);
    }
}

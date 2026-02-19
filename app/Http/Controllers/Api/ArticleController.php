<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleResource;
use App\Http\Resources\ArticleCollection;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ArticleController extends Controller
{
    /**
     * Get paginated list of articles
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $categoryId = $request->get('category_id');
        
        $query = Article::with(['category', 'images'])
            ->where('active', '1')
            ->orderBy('news_id', 'desc');
        
        if ($categoryId) {
            $query->where('id_cat', $categoryId);
        }
        
        $articles = $query->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => ArticleResource::collection($articles),
            'meta' => [
                'current_page' => $articles->currentPage(),
                'last_page' => $articles->lastPage(),
                'per_page' => $articles->perPage(),
                'total' => $articles->total(),
            ]
        ]);
    }
    
    /**
     * Get featured/slider articles
     */
    public function slider(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 20);
        
        $articles = Article::with(['category', 'images'])
            ->where('active', '1')
            ->where('show_slider', '1')
            ->orderBy('news_id', 'desc')
            ->limit($limit)
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => ArticleResource::collection($articles),
        ]);
    }
    
    /**
     * Get featured articles (important flag)
     */
    public function featured(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        
        $articles = Article::with(['category', 'images'])
            ->where('active', '1')
            ->where('important', '1')
            ->orderBy('news_id', 'desc')
            ->limit($limit)
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => ArticleResource::collection($articles),
        ]);
    }
    
    /**
     * Get latest articles
     */
    public function latest(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        
        $articles = Article::with(['category', 'images'])
            ->where('active', '1')
            ->orderBy('news_id', 'desc')
            ->limit($limit)
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => ArticleResource::collection($articles),
        ]);
    }
    
    /**
     * Get popular articles (by views)
     */
    public function popular(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        
        $articles = Article::with(['category', 'images'])
            ->where('active', '1')
            ->where('news_date', '>=', now()->subDays(7)->toDateString())
            ->orderBy('views', 'desc')
            ->limit($limit)
            ->get();

        // Fallback: if fewer results than requested, extend to 30 days
        if ($articles->count() < $limit) {
            $articles = Article::with(['category', 'images'])
                ->where('active', '1')
                ->where('news_date', '>=', now()->subDays(30)->toDateString())
                ->orderBy('views', 'desc')
                ->limit($limit)
                ->get();
        }
        
        return response()->json([
            'success' => true,
            'data' => ArticleResource::collection($articles),
        ]);
    }
    
    /**
     * Get breaking news (latest important articles)
     */
    public function breaking(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 5);
        
        $articles = Article::with(['category'])
            ->where('active', '1')
            ->where('important', '1')
            ->orderBy('news_id', 'desc')
            ->limit($limit)
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => ArticleResource::collection($articles),
        ]);
    }
    
    /**
     * Get single article by ID
     */
    public function show(int $id): JsonResponse
    {
        $article = Article::with(['category', 'images'])
            ->where('active', '1')
            ->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => new ArticleResource($article),
        ]);
    }
    
    /**
     * Get related articles (same category)
     */
    public function related(int $id, Request $request): JsonResponse
    {
        $limit = $request->get('limit', 5);
        
        $article = Article::findOrFail($id);
        
        $related = Article::with(['category', 'images'])
            ->where('active', '1')
            ->where('id_cat', $article->id_cat)
            ->where('news_id', '!=', $id)
            ->orderBy('news_id', 'desc')
            ->limit($limit)
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => ArticleResource::collection($related),
        ]);
    }
    
    /**
     * Increment article view count
     */
    public function incrementView(int $id): JsonResponse
    {
        $article = Article::findOrFail($id);
        $article->increment('views');
        
        return response()->json([
            'success' => true,
            'views' => $article->views,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleResource;
use App\Http\Resources\CategoryResource;
use App\Models\Article;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    /**
     * Get aggregated home page data
     * This reduces multiple API calls to a single request
     */
    public function index(): JsonResponse
    {
        // Cache for 1 minute for fresher news
        $data = Cache::remember('home_page_data', 60, function () {
            return [
                'slider' => $this->getSliderArticles(),
                'breaking' => $this->getBreakingNews(),
                'categories' => $this->getCategoriesWithArticles(),
                'popular' => $this->getPopularArticles(),
                'latest' => $this->getLatestArticles(),
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
    
    private function getSliderArticles(): array
    {
        $articles = Article::with(['category', 'images'])
            ->where('active', true)
            ->where('show_slider', true)
            ->orderBy('news_id', 'desc')
            ->limit(20)
            ->get();
        
        return ArticleResource::collection($articles)->resolve();
    }
    
    private function getBreakingNews(int $limit = 15): array
    {
        $articles = Article::with(['category'])
            ->where('active', true)
            ->where('important', true)
            ->orderBy('news_id', 'desc')
            ->limit($limit)
            ->get();
        
        return ArticleResource::collection($articles)->resolve();
    }
    
    private function getCategoriesWithArticles(): array
    {
        // Get main categories based on the block-templates structure
        // Categories: سياسة (1,2,12), مجتمع, أمن وقضاء, فن وثقافة, رياضة, مقالات خاصة
        $categoryIds = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
        
        $categories = Category::where('active', true)
            ->whereIn('id', $categoryIds)
            ->get();
        
        $result = [];
        
        foreach ($categories as $category) {
            $articles = Article::with(['images'])
                ->where('active', true)
                ->where('id_cat', $category->id)
                ->orderBy('news_id', 'desc')
                ->limit(4)
                ->get();
            
            if ($articles->count() > 0) {
                $result[] = [
                    'category' => (new CategoryResource($category))->resolve(),
                    'articles' => ArticleResource::collection($articles)->resolve(),
                ];
            }
        }
        
        return $result;
    }
    
    private function getPopularArticles(): array
    {
        $articles = Article::with(['category', 'images'])
            ->where('active', true)
            ->where('news_date', '>=', now()->subDays(7)->toDateString())
            ->orderBy('views', 'desc')
            ->limit(5)
            ->get();

        // Fallback: if fewer than 5 articles in the last 7 days, extend to 30 days
        if ($articles->count() < 5) {
            $articles = Article::with(['category', 'images'])
                ->where('active', true)
                ->where('news_date', '>=', now()->subDays(30)->toDateString())
                ->orderBy('views', 'desc')
                ->limit(5)
                ->get();
        }
        
        return ArticleResource::collection($articles)->resolve();
    }
    
    private function getLatestArticles(): array
    {
        $articles = Article::with(['category', 'images'])
            ->where('active', true)
            ->orderBy('news_id', 'desc')
            ->limit(10)
            ->get();
        
        return ArticleResource::collection($articles)->resolve();
    }
}

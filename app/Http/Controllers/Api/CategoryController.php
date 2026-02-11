<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleResource;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    /**
     * Get all active categories
     */
    public function index(): JsonResponse
    {
        $categories = Category::where('active', true)
            ->orderBy('id')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection($categories),
        ]);
    }
    
    /**
     * Get single category by ID
     */
    public function show(int $id): JsonResponse
    {
        $category = Category::where('active', true)
            ->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => new CategoryResource($category),
        ]);
    }
    
    /**
     * Get articles by category
     */
    public function articles(int $id, Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        
        $category = Category::where('active', true)->findOrFail($id);
        
        $articles = $category->articles()
            ->with(['category', 'images'])
            ->where('active', true)
            ->orderBy('news_id', 'desc')
            ->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'category' => new CategoryResource($category),
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

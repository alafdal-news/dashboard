<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleResource;
use App\Http\Resources\CategoryResource;
use App\Models\Article;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    /**
     * Get all active categories, organised hierarchically.
     *
     * Returns two views:
     *  - `tree`  – parent categories with `children` nested inside
     *  - `flat`  – every active category as a simple flat list
     *
     * The frontend should use `tree` for navigation / sidebar
     * and `flat` when it needs a quick id→name lookup.
     */
    public function index(): JsonResponse
    {
        $all = Category::where('active', '1')
            ->withCount(['articles' => function ($q) {
                $q->where('active', '1');
            }])
            ->orderBy('id')
            ->get();

        // Parents (is_parent = true) with their active children eager-loaded
        $parents = $all->filter(fn ($c) => $c->is_parent);
        // Manually set the children relation from the already-fetched set
        // so we don't fire N+1 queries.
        $childrenGrouped = $all->filter(fn ($c) => !$c->is_parent && $c->parent_id)
            ->groupBy('parent_id');

        foreach ($parents as $parent) {
            $parent->setRelation(
                'children',
                $childrenGrouped->get($parent->id, collect())
            );
        }

        return response()->json([
            'success' => true,
            'data' => [
                'tree' => CategoryResource::collection($parents->values()),
                'flat' => CategoryResource::collection($all),
            ],
        ]);
    }

    /**
     * Get single category by ID
     */
    public function show(int $id): JsonResponse
    {
        $category = Category::where('active', '1')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new CategoryResource($category),
        ]);
    }

    /**
     * Get articles by category.
     *
     * For **parent** categories the query includes articles from the parent
     * itself *and* all its active child categories, so that pages like
     * "سياسة" (id 30) aggregate articles from "سياسة دولية" (1),
     * "سياسة محلية" (2), and "سياسة عربية" (12).
     */
    public function articles(int $id, Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);

        $category = Category::where('active', '1')->findOrFail($id);

        // Collect all category IDs whose articles should appear on this page
        $categoryIds = [$category->id];

        if ($category->is_parent) {
            $childIds = Category::where('parent_id', $category->id)
                ->where('active', '1')
                ->pluck('id')
                ->toArray();

            $categoryIds = array_merge($categoryIds, $childIds);
        }

        $articles = Article::with(['category', 'images'])
            ->whereIn('id_cat', $categoryIds)
            ->where('active', '1')
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
            ],
        ]);
    }
}

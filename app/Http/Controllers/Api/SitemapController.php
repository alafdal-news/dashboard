<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SitemapController extends Controller
{
    /**
     * Get total article count for sitemap index generation.
     */
    public function count(): JsonResponse
    {
        $count = DB::table('news_tbl')
            ->where('active', '1')
            ->count();

        return response()->json([
            'success' => true,
            'count' => $count,
        ]);
    }

    /**
     * Get a page of article IDs + dates for sitemap generation.
     * Returns minimal data (id, date) for performance.
     * 
     * @param int $page  0-indexed page number
     * @param int $limit  items per page (max 50000, matches Google sitemap limit)
     */
    public function articles(int $page = 0): JsonResponse
    {
        $limit = 50000;
        $offset = $page * $limit;

        $articles = DB::table('news_tbl')
            ->where('active', '1')
            ->orderBy('news_id', 'desc')
            ->select('news_id', 'news_date', 'updateDate')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $articles,
        ]);
    }
}

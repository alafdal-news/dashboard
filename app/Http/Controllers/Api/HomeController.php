<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    /**
     * Section definitions matching the old homepage layout (block-templates).
     *
     * Each entry maps a slug to:
     *   - name:         Arabic display name
     *   - category_ids: DB category IDs to include
     *   - limit:        number of articles to fetch
     *   - layout:       hint for the frontend renderer
     */
    private const SECTIONS = [
        [
            'name'         => 'سياسة',
            'slug'         => 'politics',
            'category_ids' => [1, 2, 12],
            'limit'        => 5,
            'layout'       => 'politics',
        ],
        [
            'name'         => 'سياسة دولية',
            'slug'         => 'international-politics',
            'category_ids' => [1],
            'limit'        => 5,
            'layout'       => 'articles',
        ],
        [
            'name'         => 'مجتمع',
            'slug'         => 'society',
            'parent_id'    => 32,
            'category_ids' => [6, 15, 16, 17, 27],
            'limit'        => 5,
            'layout'       => 'society',
        ],
        [
            'name'         => 'أمن وقضاء',
            'slug'         => 'security',
            'category_ids' => [13],
            'limit'        => 5,
            'layout'       => 'security',
        ],
        [
            'name'         => 'فن وثقافة',
            'slug'         => 'culture',
            'category_ids' => [18],
            'limit'        => 5,
            'layout'       => 'culture',
        ],
        [
            'name'         => 'أخبار رياضية',
            'slug'         => 'sports',
            'category_ids' => [8],
            'limit'        => 5,
            'layout'       => 'sports',
        ],
    ];

    /**
     * Get aggregated home page data.
     */
    public function index(): JsonResponse
    {
        $data = Cache::remember('home_page_data', 60, function () {
            return [
                'slider'   => $this->getSliderArticles(),
                'breaking' => $this->getBreakingNews(),
                'urgent'   => $this->getUrgentArticles(),
                'sections' => $this->getSections(),
                'popular'  => $this->getPopularArticles(),
                'latest'   => $this->getLatestArticles(),
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /* ───── Slider ───── */

    private function getSliderArticles(): array
    {
        $articles = Article::with(['category', 'images'])
            ->where('active', '1')
            ->where('show_slider', '1')
            ->orderBy('news_id', 'desc')
            ->limit(20)
            ->get();

        return ArticleResource::collection($articles)->resolve();
    }

    /* ───── Breaking news ticker (≤ 25) ───── */

    private function getBreakingNews(): array
    {
        $articles = Article::with(['category'])
            ->where('active', '1')
            ->where('important', '1')
            ->orderBy('news_id', 'desc')
            ->limit(25)
            ->get();

        return ArticleResource::collection($articles)->resolve();
    }

    /* ───── Urgent sidebar (≤ 7) ───── */

    private function getUrgentArticles(): array
    {
        $articles = Article::with(['category'])
            ->where('active', '1')
            ->where('important', '1')
            ->orderBy('news_id', 'desc')
            ->limit(7)
            ->get();

        return ArticleResource::collection($articles)->resolve();
    }

    /* ───── Grouped category sections ───── */

    private function getSections(): array
    {
        $result = [];

        foreach (self::SECTIONS as $section) {
            $articles = Article::with(['category', 'images'])
                ->where('active', '1')
                ->whereIn('id_cat', $section['category_ids'])
                ->orderBy('news_id', 'desc')
                ->limit($section['limit'])
                ->get();

            // Always include the section even if empty so the frontend
            // can decide whether to render it.
            $result[] = [
                'name'         => $section['name'],
                'slug'         => $section['slug'],
                'parent_id'    => $section['parent_id'] ?? $section['category_ids'][0] ?? null,
                'category_ids' => $section['category_ids'],
                'layout'       => $section['layout'],
                'articles'     => ArticleResource::collection($articles)->resolve(),
            ];
        }

        return $result;
    }

    /* ───── Popular (last 7 → 30 days fallback) ───── */

    private function getPopularArticles(): array
    {
        $articles = Article::with(['category', 'images'])
            ->where('active', '1')
            ->where('news_date', '>=', now()->subDays(7)->toDateString())
            ->orderBy('views', 'desc')
            ->limit(5)
            ->get();

        if ($articles->count() < 5) {
            $articles = Article::with(['category', 'images'])
                ->where('active', '1')
                ->where('news_date', '>=', now()->subDays(30)->toDateString())
                ->orderBy('views', 'desc')
                ->limit(5)
                ->get();
        }

        return ArticleResource::collection($articles)->resolve();
    }

    /* ───── Latest ───── */

    private function getLatestArticles(): array
    {
        $articles = Article::with(['category', 'images'])
            ->where('active', '1')
            ->orderBy('news_id', 'desc')
            ->limit(10)
            ->get();

        return ArticleResource::collection($articles)->resolve();
    }
}

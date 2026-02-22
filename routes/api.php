<?php

use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\SitemapController;
use App\Http\Controllers\Api\VideoController;
use App\Http\Controllers\Api\HomeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| These routes serve both the Next.js frontend and the mobile app.
| All routes are prefixed with /api
|
*/

// Public API Routes (no authentication required)
Route::prefix('v1')->group(function () {
    
    // Home page data (aggregated)
    Route::get('/home', [HomeController::class, 'index']);
    
    // Articles
    Route::prefix('articles')->group(function () {
        Route::get('/', [ArticleController::class, 'index']);
        Route::get('/featured', [ArticleController::class, 'featured']);
        Route::get('/slider', [ArticleController::class, 'slider']);
        Route::get('/latest', [ArticleController::class, 'latest']);
        Route::get('/popular', [ArticleController::class, 'popular']);
        Route::get('/breaking', [ArticleController::class, 'breaking']);
        Route::get('/{id}', [ArticleController::class, 'show']);
        Route::get('/{id}/related', [ArticleController::class, 'related']);
        Route::post('/{id}/view', [ArticleController::class, 'incrementView']);
    });
    
    // Categories
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::get('/{id}', [CategoryController::class, 'show']);
        Route::get('/{id}/articles', [CategoryController::class, 'articles']);
    });
    
    // Search
    Route::get('/search', [SearchController::class, 'search']);
    
    // Videos
    Route::prefix('videos')->group(function () {
        Route::get('/', [VideoController::class, 'index']);
        Route::get('/{id}', [VideoController::class, 'show']);
    });

    // Sitemap (used by Next.js to generate sitemap.xml)
    Route::prefix('sitemap')->group(function () {
        Route::get('/count', [SitemapController::class, 'count']);
        Route::get('/articles/{page}', [SitemapController::class, 'articles']);
    });
});

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\VideoResource;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class VideoController extends Controller
{
    /**
     * Get paginated list of videos
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        
        // Using DB query since Video model may not be fully configured
        $videos = DB::table('video_tbl')
            ->where('active', '1')
            ->orderBy('id', 'desc')
            ->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $videos->items(),
            'meta' => [
                'current_page' => $videos->currentPage(),
                'last_page' => $videos->lastPage(),
                'per_page' => $videos->perPage(),
                'total' => $videos->total(),
            ]
        ]);
    }
    
    /**
     * Get single video by ID
     */
    public function show(int $id): JsonResponse
    {
        $video = DB::table('video_tbl')
            ->where('id', $id)
            ->where('active', '1')
            ->first();
        
        if (!$video) {
            return response()->json([
                'success' => false,
                'message' => 'Video not found',
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $video,
        ]);
    }
}

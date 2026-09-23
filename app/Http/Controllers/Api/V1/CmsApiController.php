<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\CuratedVideo;
use App\Models\NewsArticle;
use App\Models\Scheme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CmsApiController extends Controller
{
    /**
     * Get government schemes and subsidies.
     */
    public function schemes(Request $request): JsonResponse
    {
        $category = $request->query('category');
        $search = $request->query('search');

        $schemes = Scheme::active()
            ->when($category, fn($q) => $q->where('category', $category))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('title', 'like', "%{$search}%")
                        ->orWhere('title_kn', 'like', "%{$search}%")
                        ->orWhere('sponsoring_agency', 'like', "%{$search}%");
                });
            })
            ->orderBy('display_order')
            ->orderBy('title')
            ->paginate($request->query('per_page', 15));

        return response()->json([
            'status' => 'success',
            'data' => $schemes->items(),
            'pagination' => [
                'current_page' => $schemes->currentPage(),
                'last_page' => $schemes->lastPage(),
                'per_page' => $schemes->perPage(),
                'total' => $schemes->total(),
            ],
        ]);
    }

    /**
     * Get agricultural news and advisories.
     */
    public function news(Request $request): JsonResponse
    {
        $priority = $request->query('priority');

        $news = NewsArticle::active()
            ->when($priority, fn($q) => $q->where('priority', $priority))
            ->recent()
            ->paginate($request->query('per_page', 15));

        return response()->json([
            'status' => 'success',
            'data' => $news->items(),
            'pagination' => [
                'current_page' => $news->currentPage(),
                'last_page' => $news->lastPage(),
                'per_page' => $news->perPage(),
                'total' => $news->total(),
            ],
        ]);
    }

    /**
     * Get curated video tutorials and farming guides.
     */
    public function videos(Request $request): JsonResponse
    {
        $cropId = $request->query('crop_id');
        $category = $request->query('category');

        $videos = CuratedVideo::active()
            ->with(['crop:id,name,name_kn'])
            ->when($cropId, fn($q) => $q->where('crop_id', $cropId))
            ->when($category, fn($q) => $q->where('category', $category))
            ->orderBy('display_order')
            ->latest()
            ->paginate($request->query('per_page', 15));

        $data = collect($videos->items())->map(function ($video) {
            return [
                'id' => $video->id,
                'title' => $video->title,
                'title_kn' => $video->title_kn,
                'youtube_video_id' => $video->youtube_video_id,
                'youtube_url' => $video->youtube_url,
                'thumbnail_url' => $video->thumbnail_url,
                'embed_url' => $video->embed_url,
                'crop' => $video->crop,
                'category' => $video->category,
                'channel_name' => $video->channel_name,
                'duration_text' => $video->duration_text,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $data,
            'pagination' => [
                'current_page' => $videos->currentPage(),
                'last_page' => $videos->lastPage(),
                'per_page' => $videos->perPage(),
                'total' => $videos->total(),
            ],
        ]);
    }

    /**
     * Get agronomy articles and expert best practices.
     */
    public function articles(Request $request): JsonResponse
    {
        $cropId = $request->query('crop_id');
        $category = $request->query('category');

        $articles = Article::published()
            ->with(['crop:id,name,name_kn'])
            ->when($cropId, fn($q) => $q->where('crop_id', $cropId))
            ->when($category, fn($q) => $q->where('category', $category))
            ->latest('published_at')
            ->paginate($request->query('per_page', 15));

        return response()->json([
            'status' => 'success',
            'data' => $articles->items(),
            'pagination' => [
                'current_page' => $articles->currentPage(),
                'last_page' => $articles->lastPage(),
                'per_page' => $articles->perPage(),
                'total' => $articles->total(),
            ],
        ]);
    }
}

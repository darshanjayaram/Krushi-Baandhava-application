<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Crop;
use App\Models\CuratedVideo;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VideoController extends Controller
{
    public function index(Request $request): View
    {
        $cropId = $request->query('crop_id');
        $category = $request->query('category');
        $growthStage = $request->query('growth_stage');
        $search = $request->query('search');

        // Admin-configured max videos per page (defaults to 12)
        $perPage = (int) SystemSetting::get('farmer_video_per_page', 12);
        if ($perPage < 3 || $perPage > 60) {
            $perPage = 12;
        }

        // Hero spotlight video (featured video) when no specific filters/search applied and on first page
        $featuredVideo = null;
        if (!$cropId && !$category && !$growthStage && !$search && $request->query('page', 1) == 1) {
            $featuredVideo = CuratedVideo::active()
                ->featured()
                ->with('crop')
                ->latest()
                ->first();
        }

        $videos = CuratedVideo::active()
            ->with('crop')
            ->when($featuredVideo, fn($q) => $q->where('id', '!=', $featuredVideo->id))
            ->when($cropId, fn($q) => $q->where('crop_id', $cropId))
            ->when($category, fn($q) => $q->where('category', $category))
            ->when($growthStage, fn($q) => $q->byStage($growthStage))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('title', 'like', "%{$search}%")
                        ->orWhere('title_kn', 'like', "%{$search}%")
                        ->orWhere('channel_name', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('is_featured')
            ->orderBy('display_order')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $crops = Crop::orderBy('name')->get(['id', 'name', 'name_kn']);
        $categories = CuratedVideo::getCategories();
        $growthStages = CuratedVideo::getGrowthStages();

        return view('farmer.videos.index', compact(
            'videos', 
            'crops', 
            'categories', 
            'growthStages',
            'cropId', 
            'category', 
            'growthStage',
            'search',
            'featuredVideo',
            'perPage'
        ));
    }
}

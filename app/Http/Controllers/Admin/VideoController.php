<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Crop;
use App\Models\CuratedVideo;
use App\Models\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class VideoController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $cropId = $request->query('crop_id');
        $category = $request->query('category');
        $growthStage = $request->query('growth_stage');
        $status = $request->query('status');
        $featured = $request->query('featured');

        $query = CuratedVideo::with('crop');

        if ($cropId) {
            $query->where('crop_id', $cropId);
        }

        if ($category) {
            $query->where('category', $category);
        }

        if ($growthStage) {
            $query->where('growth_stage', $growthStage);
        }

        if ($status !== null && $status !== '') {
            $query->where('is_active', $status === 'active');
        }

        if ($featured !== null && $featured !== '') {
            $query->where('is_featured', $featured === '1' || $featured === 'yes');
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('title_kn', 'like', "%{$search}%")
                    ->orWhere('channel_name', 'like', "%{$search}%");
            });
        }

        $videos = $query->orderByDesc('is_featured')
            ->orderBy('display_order')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $crops = Crop::where('is_active', true)->orderBy('name')->get();

        // High-level metrics for admin dashboard
        $stats = [
            'total' => CuratedVideo::count(),
            'active' => CuratedVideo::where('is_active', true)->count(),
            'featured' => CuratedVideo::where('is_featured', true)->count(),
            'crops_covered' => CuratedVideo::whereNotNull('crop_id')->distinct('crop_id')->count('crop_id'),
        ];

        $categories = CuratedVideo::getCategories();
        $growthStages = CuratedVideo::getGrowthStages();
        $farmerPerPage = (int) SystemSetting::get('farmer_video_per_page', 12);

        return view('admin.videos.index', compact(
            'videos',
            'crops',
            'categories',
            'growthStages',
            'stats',
            'farmerPerPage',
            'search',
            'cropId',
            'category',
            'growthStage',
            'status',
            'featured'
        ));
    }

    public function create(): View
    {
        $crops = Crop::where('is_active', true)->orderBy('name')->get();

        return view('admin.videos.form', [
            'video' => new CuratedVideo([
                'is_active' => true,
                'is_featured' => false,
                'category' => 'cultivation',
                'growth_stage' => 'general',
                'language' => 'kn',
                'display_order' => 0
            ]),
            'crops' => $crops,
            'categories' => CuratedVideo::getCategories(),
            'growthStages' => CuratedVideo::getGrowthStages(),
            'isEdit' => false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'title_kn' => 'nullable|string|max:255',
            'youtube_url' => 'required|string|max:500',
            'crop_id' => 'nullable|exists:crops,id',
            'category' => 'required|string|max:50',
            'growth_stage' => 'nullable|string|max:50',
            'language' => 'nullable|string|max:20',
            'channel_name' => 'nullable|string|max:255',
            'duration_text' => 'nullable|string|max:30',
            'display_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
        ]);

        $youtubeId = CuratedVideo::extractYoutubeId($validated['youtube_url']);
        if (!$youtubeId) {
            return back()->withInput()->withErrors(['youtube_url' => 'ಮಾನ್ಯವಾದ ಯೂಟ್ಯೂಬ್ ಲಿಂಕ್ ನಮೂದಿಸಿ (Invalid YouTube URL).']);
        }

        $validated['youtube_video_id'] = $youtubeId;
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['growth_stage'] = $validated['growth_stage'] ?? 'general';
        $validated['language'] = $validated['language'] ?? 'kn';
        $validated['display_order'] = (int) ($validated['display_order'] ?? 0);

        CuratedVideo::create($validated);

        return redirect()->route('admin.videos.index')->with('status', 'ಕೃಷಿ ವಿಡಿಯೋವನ್ನು ಯಶಸ್ವಿಯಾಗಿ ಸೇರಿಸಲಾಗಿದೆ (Video added successfully).');
    }

    public function edit(CuratedVideo $video): View
    {
        $crops = Crop::where('is_active', true)->orderBy('name')->get();

        return view('admin.videos.form', [
            'video' => $video,
            'crops' => $crops,
            'categories' => CuratedVideo::getCategories(),
            'growthStages' => CuratedVideo::getGrowthStages(),
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, CuratedVideo $video): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'title_kn' => 'nullable|string|max:255',
            'youtube_url' => 'required|string|max:500',
            'crop_id' => 'nullable|exists:crops,id',
            'category' => 'required|string|max:50',
            'growth_stage' => 'nullable|string|max:50',
            'language' => 'nullable|string|max:20',
            'channel_name' => 'nullable|string|max:255',
            'duration_text' => 'nullable|string|max:30',
            'display_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
        ]);

        $youtubeId = CuratedVideo::extractYoutubeId($validated['youtube_url']);
        if (!$youtubeId) {
            return back()->withInput()->withErrors(['youtube_url' => 'ಮಾನ್ಯವಾದ ಯೂಟ್ಯೂಬ್ ಲಿಂಕ್ ನಮೂದಿಸಿ (Invalid YouTube URL).']);
        }

        $validated['youtube_video_id'] = $youtubeId;
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['growth_stage'] = $validated['growth_stage'] ?? 'general';
        $validated['language'] = $validated['language'] ?? 'kn';
        $validated['display_order'] = (int) ($validated['display_order'] ?? 0);

        $video->update($validated);

        return redirect()->route('admin.videos.index')->with('status', 'ವಿಡಿಯೋ ವಿವರಗಳನ್ನು ನವೀಕರಿಸಲಾಗಿದೆ (Video updated successfully).');
    }

    /**
     * Fast AJAX / Form toggle for active visibility status.
     */
    public function toggle(Request $request, CuratedVideo $video)
    {
        $video->update(['is_active' => !$video->is_active]);

        $msg = $video->is_active ? 'ವಿಡಿಯೋವನ್ನು ಸಕ್ರಿಯಗೊಳಿಸಲಾಗಿದೆ (Activated)' : 'ವಿಡಿಯೋವನ್ನು ನಿಷ್ಕ್ರಿಯಗೊಳಿಸಲಾಗಿದೆ (Deactivated)';

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_active' => $video->is_active,
                'message' => $msg,
            ]);
        }

        return back()->with('status', $msg);
    }

    /**
     * Fast AJAX / Form toggle for Featured spotlight status.
     */
    public function toggleFeatured(Request $request, CuratedVideo $video)
    {
        $video->update(['is_featured' => !$video->is_featured]);

        $msg = $video->is_featured ? 'ವಿಶೇಷ ವಿಡಿಯೋವಾಗಿ ಗುರುತಿಸಲಾಗಿದೆ (Set as Featured)' : 'ವಿಶೇಷ ಪಟ್ಟಿಯಿಂದ ತೆಗೆಯಲಾಗಿದೆ (Unset from Featured)';

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_featured' => $video->is_featured,
                'message' => $msg,
            ]);
        }

        return back()->with('status', $msg);
    }

    /**
     * 1-Click YouTube Metadata Auto-Fetcher (via YouTube oEmbed API).
     * No API key required.
     */
    public function fetchMetadata(Request $request): JsonResponse
    {
        $url = trim($request->input('url', ''));

        if (!$url) {
            return response()->json(['success' => false, 'message' => 'Please provide a YouTube URL'], 422);
        }

        $youtubeId = CuratedVideo::extractYoutubeId($url);

        if (!$youtubeId) {
            return response()->json(['success' => false, 'message' => 'Invalid YouTube link or ID format'], 422);
        }

        try {
            $canonicalUrl = "https://www.youtube.com/watch?v={$youtubeId}";
            $response = Http::timeout(6)
                ->acceptJson()
                ->get('https://www.youtube.com/oembed', [
                    'url' => $canonicalUrl,
                    'format' => 'json',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return response()->json([
                    'success' => true,
                    'youtube_video_id' => $youtubeId,
                    'title' => $data['title'] ?? '',
                    'channel_name' => $data['author_name'] ?? '',
                    'thumbnail_url' => "https://img.youtube.com/vi/{$youtubeId}/hqdefault.jpg",
                    'embed_url' => "https://www.youtube.com/embed/{$youtubeId}?autoplay=1&rel=0",
                ]);
            }

            // Fallback if oEmbed fails but we have valid ID
            return response()->json([
                'success' => true,
                'youtube_video_id' => $youtubeId,
                'title' => '',
                'channel_name' => '',
                'thumbnail_url' => "https://img.youtube.com/vi/{$youtubeId}/hqdefault.jpg",
                'embed_url' => "https://www.youtube.com/embed/{$youtubeId}?autoplay=1&rel=0",
                'notice' => 'Video ID recognized, please fill remaining fields manually.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => true,
                'youtube_video_id' => $youtubeId,
                'thumbnail_url' => "https://img.youtube.com/vi/{$youtubeId}/hqdefault.jpg",
                'embed_url' => "https://www.youtube.com/embed/{$youtubeId}?autoplay=1&rel=0",
            ]);
        }
    }

    /**
     * Bulk action on multiple videos (activate, deactivate, delete).
     */
    public function bulkAction(Request $request): RedirectResponse
    {
        $action = $request->input('bulk_action');
        $ids = $request->input('selected_ids', []);

        if (empty($ids) || !is_array($ids)) {
            return back()->with('error', 'ಯಾವುದೇ ವಿಡಿಯೋವನ್ನು ಆಯ್ಕೆ ಮಾಡಿಲ್ಲ (No videos selected).');
        }

        switch ($action) {
            case 'activate':
                CuratedVideo::whereIn('id', $ids)->update(['is_active' => true]);
                $msg = count($ids) . ' ವಿಡಿಯೋಗಳನ್ನು ಸಕ್ರಿಯಗೊಳಿಸಲಾಗಿದೆ (Activated).';
                break;
            case 'deactivate':
                CuratedVideo::whereIn('id', $ids)->update(['is_active' => false]);
                $msg = count($ids) . ' ವಿಡಿಯೋಗಳನ್ನು ನಿಷ್ಕ್ರಿಯಗೊಳಿಸಲಾಗಿದೆ (Deactivated).';
                break;
            case 'feature':
                CuratedVideo::whereIn('id', $ids)->update(['is_featured' => true]);
                $msg = count($ids) . ' ವಿಡಿಯೋಗಳನ್ನು ವಿಶೇಷ ಪಟ್ಟಿಗೆ ಸೇರಿಸಲಾಗಿದೆ (Featured).';
                break;
            case 'unfeature':
                CuratedVideo::whereIn('id', $ids)->update(['is_featured' => false]);
                $msg = count($ids) . ' ವಿಡಿಯೋಗಳನ್ನು ವಿಶೇಷ ಪಟ್ಟಿಯಿಂದ ತೆಗೆಯಲಾಗಿದೆ (Unfeatured).';
                break;
            case 'delete':
                CuratedVideo::whereIn('id', $ids)->delete();
                $msg = count($ids) . ' ವಿಡಿಯೋಗಳನ್ನು ಅಳಿಸಲಾಗಿದೆ (Deleted).';
                break;
            default:
                return back()->with('error', 'ಅಮಾನ್ಯ ಕ್ರಿಯೆ (Invalid action).');
        }

        return redirect()->route('admin.videos.index')->with('status', $msg);
    }

    public function destroy(CuratedVideo $video): RedirectResponse
    {
        $video->delete();

        return redirect()->route('admin.videos.index')->with('status', 'ವಿಡಿಯೋವನ್ನು ಅಳಿಸಲಾಗಿದೆ (Video deleted).');
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'per_page' => 'required|integer|in:4,6,8,9,12,15,16,18,20,24,30',
        ]);

        SystemSetting::set(
            'farmer_video_per_page',
            (int) $validated['per_page'],
            'integer',
            'videos',
            'Max videos displayed per page on farmer video hub'
        );

        return redirect()->route('admin.videos.index')->with('status', "ಪ್ರತಿ ಪುಟದ ಗರಿಷ್ಠ ವಿಡಿಯೋಗಳ ಸಂಖ್ಯೆಯನ್ನು {$validated['per_page']} ಗೆ ನವೀಕರಿಸಲಾಗಿದೆ (Farmer Hub updated to {$validated['per_page']} videos per page).");
    }
}

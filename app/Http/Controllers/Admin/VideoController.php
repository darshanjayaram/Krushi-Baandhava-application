<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Crop;
use App\Models\CuratedVideo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VideoController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $cropId = $request->query('crop_id');
        $category = $request->query('category');

        $videos = CuratedVideo::with('crop')
            ->when($cropId, fn($q) => $q->where('crop_id', $cropId))
            ->when($category, fn($q) => $q->where('category', $category))
            ->when($search, function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('title_kn', 'like', "%{$search}%")
                    ->orWhere('channel_name', 'like', "%{$search}%");
            })
            ->orderBy('display_order')
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $crops = Crop::where('is_active', true)->orderBy('name')->get();

        return view('admin.videos.index', compact('videos', 'crops', 'search', 'cropId', 'category'));
    }

    public function create(): View
    {
        $crops = Crop::where('is_active', true)->orderBy('name')->get();

        return view('admin.videos.form', [
            'video' => new CuratedVideo(['is_active' => true, 'category' => 'cultivation', 'display_order' => 0]),
            'crops' => $crops,
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
            'channel_name' => 'nullable|string|max:255',
            'duration_text' => 'nullable|string|max:30',
            'display_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $youtubeId = CuratedVideo::extractYoutubeId($validated['youtube_url']);
        if (!$youtubeId) {
            return back()->withInput()->withErrors(['youtube_url' => 'ಮಾನ್ಯವಾದ ಯೂಟ್ಯೂಬ್ ಲಿಂಕ್ ನಮೂದಿಸಿ (Invalid YouTube URL).']);
        }

        $validated['youtube_video_id'] = $youtubeId;
        $validated['is_active'] = $request->boolean('is_active');
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
            'channel_name' => 'nullable|string|max:255',
            'duration_text' => 'nullable|string|max:30',
            'display_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $youtubeId = CuratedVideo::extractYoutubeId($validated['youtube_url']);
        if (!$youtubeId) {
            return back()->withInput()->withErrors(['youtube_url' => 'ಮಾನ್ಯವಾದ ಯೂಟ್ಯೂಬ್ ಲಿಂಕ್ ನಮೂದಿಸಿ (Invalid YouTube URL).']);
        }

        $validated['youtube_video_id'] = $youtubeId;
        $validated['is_active'] = $request->boolean('is_active');
        $validated['display_order'] = (int) ($validated['display_order'] ?? 0);

        $video->update($validated);

        return redirect()->route('admin.videos.index')->with('status', 'ವಿಡಿಯೋ ವಿವರಗಳನ್ನು ನವೀಕರಿಸಲಾಗಿದೆ (Video updated successfully).');
    }

    public function toggle(CuratedVideo $video): RedirectResponse
    {
        $video->update(['is_active' => !$video->is_active]);

        $msg = $video->is_active ? 'ವಿಡಿಯೋವನ್ನು ಸಕ್ರಿಯಗೊಳಿಸಲಾಗಿದೆ (Activated)' : 'ವಿಡಿಯೋವನ್ನು ನಿಷ್ಕ್ರಿಯಗೊಳಿಸಲಾಗಿದೆ (Deactivated)';
        return back()->with('status', $msg);
    }

    public function destroy(CuratedVideo $video): RedirectResponse
    {
        $video->delete();

        return redirect()->route('admin.videos.index')->with('status', 'ವಿಡಿಯೋವನ್ನು ಅಳಿಸಲಾಗಿದೆ (Video deleted).');
    }
}

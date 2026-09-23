<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Crop;
use App\Models\CuratedVideo;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VideoController extends Controller
{
    public function index(Request $request): View
    {
        $cropId = $request->query('crop_id');
        $category = $request->query('category');
        $search = $request->query('search');

        $videos = CuratedVideo::active()
            ->with('crop')
            ->when($cropId, fn($q) => $q->where('crop_id', $cropId))
            ->when($category, fn($q) => $q->where('category', $category))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('title', 'like', "%{$search}%")
                        ->orWhere('title_kn', 'like', "%{$search}%")
                        ->orWhere('channel_name', 'like', "%{$search}%");
                });
            })
            ->orderBy('display_order')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $crops = Crop::orderBy('name')->get(['id', 'name', 'name_kn']);

        $categories = [
            'farming_tips' => ['name_en' => 'Farming Tips & Techniques', 'name_kn' => 'ಕೃಷಿ ತಂತ್ರಜ್ಞಾನ & ಸಲಹೆಗಳು', 'icon' => '🌾'],
            'pest_control' => ['name_en' => 'Pest & Disease Control', 'name_kn' => 'ಕೀಟ ಹಾಗೂ ರೋಗ ನಿರ್ವಹಣೆ', 'icon' => '🐛'],
            'irrigation' => ['name_en' => 'Irrigation Management', 'name_kn' => 'ನೀರಾವರಿ ಪದ್ಧತಿಗಳು', 'icon' => '💧'],
            'success_stories' => ['name_en' => 'Success Stories', 'name_kn' => 'ಯಶಸ್ವಿ ರೈತರ ಕಥೆಗಳು', 'icon' => '🏆'],
            'machinery' => ['name_en' => 'Farm Machinery & Tools', 'name_kn' => 'ಕೃಷಿ ಯಂತ್ರಗಳು', 'icon' => '🚜'],
        ];

        return view('farmer.videos.index', compact('videos', 'crops', 'categories', 'cropId', 'category', 'search'));
    }
}

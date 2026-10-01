<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Scheme;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SchemeController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $category = $request->query('category');

        $perPage = (int) SystemSetting::get('schemes_per_page', 9);
        $sliderAutoplay = (int) SystemSetting::get('schemes_slider_autoplay', 5);
        $showBanner = (bool) SystemSetting::get('schemes_show_banner', true);

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
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        // Multi-banner spotlight slider (featured schemes from admin panel)
        $bannerSchemes = collect();
        if ($showBanner && empty($search)) {
            $bannerQuery = Scheme::active();
            if ($category) {
                $bannerQuery->where('category', $category);
            }
            $bannerSchemes = (clone $bannerQuery)->where('is_featured', true)
                ->orderBy('display_order')
                ->get();

            if ($bannerSchemes->isEmpty()) {
                // Fallback to top active schemes if none explicitly featured in this category
                $bannerSchemes = $bannerQuery->orderBy('display_order')->take(3)->get();
            }
        }

        $categories = [
            'subsidy' => ['name_en' => 'Subsidies & Grants', 'name_kn' => 'ಸಬ್ಸಿಡಿ & ಅನುದಾನ', 'icon' => '💰'],
            'machinery' => ['name_en' => 'Farm Machinery', 'name_kn' => 'ಕೃಷಿ ಯಂತ್ರೋಪಕರಣ', 'icon' => '🚜'],
            'irrigation' => ['name_en' => 'Micro Irrigation', 'name_kn' => 'ಸೂಕ್ಷ್ಮ ನೀರಾವರಿ', 'icon' => '💧'],
            'insurance' => ['name_en' => 'Crop Insurance', 'name_kn' => 'ಬೆಳೆ ವಿಮೆ', 'icon' => '🛡️'],
            'organic' => ['name_en' => 'Organic & Soil', 'name_kn' => 'ಸಾವಯವ & ಮಣ್ಣು', 'icon' => '🌱'],
        ];

        return view('farmer.schemes.index', compact(
            'schemes',
            'categories',
            'category',
            'search',
            'bannerSchemes',
            'sliderAutoplay',
            'showBanner',
            'perPage'
        ));
    }

    public function show(string $slug)
    {
        $scheme = Scheme::active()->where('slug', $slug)->firstOrFail();
        $targetUrl = $scheme->apply_url ?: $scheme->official_url ?: route('farmer.schemes.index');

        return redirect()->away($targetUrl);
    }
}

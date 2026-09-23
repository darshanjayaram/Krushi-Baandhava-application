<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Scheme;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SchemeController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $category = $request->query('category');

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
            ->paginate(12)
            ->withQueryString();

        $categories = [
            'subsidy' => ['name_en' => 'Subsidies & Grants', 'name_kn' => 'ಸಬ್ಸಿಡಿ & ಅನುದಾನ', 'icon' => '💰'],
            'machinery' => ['name_en' => 'Farm Machinery', 'name_kn' => 'ಕೃಷಿ ಯಂತ್ರೋಪಕರಣ', 'icon' => '🚜'],
            'irrigation' => ['name_en' => 'Micro Irrigation', 'name_kn' => 'ಸೂಕ್ಷ್ಮ ನೀರಾವರಿ', 'icon' => '💧'],
            'insurance' => ['name_en' => 'Crop Insurance', 'name_kn' => 'ಬೆಳೆ ವಿಮೆ', 'icon' => '☂️'],
            'organic' => ['name_en' => 'Organic & Soil', 'name_kn' => 'ಸಾವಯವ & ಮಣ್ಣು', 'icon' => '🌱'],
        ];

        return view('farmer.schemes.index', compact('schemes', 'categories', 'category', 'search'));
    }

    public function show(string $slug): View
    {
        $scheme = Scheme::active()->where('slug', $slug)->firstOrFail();

        $relatedSchemes = Scheme::active()
            ->where('id', '!=', $scheme->id)
            ->where('category', $scheme->category)
            ->take(3)
            ->get();

        return view('farmer.schemes.show', compact('scheme', 'relatedSchemes'));
    }
}

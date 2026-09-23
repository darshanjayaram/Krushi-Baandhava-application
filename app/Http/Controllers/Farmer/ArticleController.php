<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Crop;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function index(Request $request): View
    {
        $cropId = $request->query('crop_id');
        $category = $request->query('category');
        $search = $request->query('search');

        $articles = Article::published()
            ->with('crop')
            ->when($cropId, fn($q) => $q->where('crop_id', $cropId))
            ->when($category, fn($q) => $q->where('category', $category))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('title', 'like', "%{$search}%")
                        ->orWhere('title_kn', 'like', "%{$search}%")
                        ->orWhere('summary', 'like', "%{$search}%")
                        ->orWhere('summary_kn', 'like', "%{$search}%");
                });
            })
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        $crops = Crop::orderBy('name')->get(['id', 'name', 'name_kn']);

        $categories = [
            'cultivation' => ['name_en' => 'Cultivation Methods', 'name_kn' => 'ಬೇಸಾಯ ಕ್ರಮಗಳು', 'icon' => '🌱'],
            'pest_control' => ['name_en' => 'Pest & Disease Control', 'name_kn' => 'ಕೀಟ & ರೋಗ ಬಾಧೆ', 'icon' => '🛡️'],
            'soil_fertilizer' => ['name_en' => 'Soil & Fertilizer', 'name_kn' => 'ಮಣ್ಣು & ರಸಗೊಬ್ಬರ', 'icon' => '🧪'],
            'harvest_storage' => ['name_en' => 'Harvest & Post-Harvest', 'name_kn' => 'ಕೊಯ್ಲು & ಸಂಗ್ರಹಣೆ', 'icon' => '📦'],
            'general' => ['name_en' => 'General Agriculture', 'name_kn' => 'ಸಾಮಾನ್ಯ ಕೃಷಿ ಮಾಹಿತಿ', 'icon' => '📚'],
        ];

        return view('farmer.articles.index', compact('articles', 'crops', 'categories', 'cropId', 'category', 'search'));
    }

    public function show(string $slug): View
    {
        $article = Article::published()->with('crop')->where('slug', $slug)->firstOrFail();

        $relatedArticles = Article::published()
            ->where('id', '!=', $article->id)
            ->where(function ($q) use ($article) {
                $q->where('category', $article->category);
                if ($article->crop_id) {
                    $q->orWhere('crop_id', $article->crop_id);
                }
            })
            ->latest('published_at')
            ->take(3)
            ->get();

        return view('farmer.articles.show', compact('article', 'relatedArticles'));
    }
}

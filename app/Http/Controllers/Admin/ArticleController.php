<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Crop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $cropId = $request->query('crop_id');
        $category = $request->query('category');

        $articles = Article::with('crop')
            ->when($cropId, fn($q) => $q->where('crop_id', $cropId))
            ->when($category, fn($q) => $q->where('category', $category))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('title', 'like', "%{$search}%")
                        ->orWhere('title_kn', 'like', "%{$search}%")
                        ->orWhere('author_name', 'like', "%{$search}%");
                });
            })
            ->orderBy('published_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $crops = Crop::where('is_active', true)->orderBy('name')->get();

        return view('admin.articles.index', compact('articles', 'crops', 'search', 'cropId', 'category'));
    }

    public function create(): View
    {
        $crops = Crop::where('is_active', true)->orderBy('name')->get();

        return view('admin.articles.form', [
            'article' => new Article(['is_published' => true, 'category' => 'cultivation', 'published_at' => now()]),
            'crops' => $crops,
            'isEdit' => false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'title_kn' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255|unique:articles,slug',
            'crop_id' => 'nullable|exists:crops,id',
            'category' => 'required|string|max:50',
            'summary' => 'nullable|string',
            'summary_kn' => 'nullable|string',
            'body' => 'nullable|string',
            'body_kn' => 'nullable|string',
            'featured_image' => 'nullable|url|max:500',
            'author_name' => 'nullable|string|max:255',
            'published_at' => 'nullable|date',
            'is_published' => 'nullable|boolean',
        ]);

        $validated['slug'] = !empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['title']);
        $validated['is_published'] = $request->boolean('is_published');
        $validated['published_at'] = $validated['published_at'] ?? now();

        Article::create($validated);

        return redirect()->route('admin.articles.index')->with('status', 'ಕೃಷಿ ಲೇಖನವನ್ನು ಪ್ರಕಟಿಸಲಾಗಿದೆ (Article published successfully).');
    }

    public function edit(Article $article): View
    {
        $crops = Crop::where('is_active', true)->orderBy('name')->get();

        return view('admin.articles.form', [
            'article' => $article,
            'crops' => $crops,
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, Article $article): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'title_kn' => 'nullable|string|max:255',
            'slug' => 'required|string|max:255|unique:articles,slug,' . $article->id,
            'crop_id' => 'nullable|exists:crops,id',
            'category' => 'required|string|max:50',
            'summary' => 'nullable|string',
            'summary_kn' => 'nullable|string',
            'body' => 'nullable|string',
            'body_kn' => 'nullable|string',
            'featured_image' => 'nullable|url|max:500',
            'author_name' => 'nullable|string|max:255',
            'published_at' => 'nullable|date',
            'is_published' => 'nullable|boolean',
        ]);

        $validated['slug'] = Str::slug($validated['slug']);
        $validated['is_published'] = $request->boolean('is_published');

        $article->update($validated);

        return redirect()->route('admin.articles.index')->with('status', 'ಲೇಖನವನ್ನು ನವೀಕರಿಸಲಾಗಿದೆ (Article updated successfully).');
    }

    public function toggle(Article $article): RedirectResponse
    {
        $article->update(['is_published' => !$article->is_published]);

        $msg = $article->is_published ? 'ಲೇಖನವನ್ನು ಸಕ್ರಿಯಗೊಳಿಸಲಾಗಿದೆ (Published)' : 'ಲೇಖನವನ್ನು ಡ್ರಾಫ್ಟ್‌ಗೆ ಇಳಿಸಲಾಗಿದೆ (Drafted)';
        return back()->with('status', $msg);
    }

    public function destroy(Article $article): RedirectResponse
    {
        $article->delete();

        return redirect()->route('admin.articles.index')->with('status', 'ಲೇಖನವನ್ನು ಅಳಿಸಲಾಗಿದೆ (Article deleted).');
    }
}

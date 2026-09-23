<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsArticle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NewsController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $priority = $request->query('priority');

        $news = NewsArticle::query()
            ->when($priority, fn($q) => $q->where('priority', $priority))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('headline', 'like', "%{$search}%")
                        ->orWhere('headline_kn', 'like', "%{$search}%")
                        ->orWhere('source_name', 'like', "%{$search}%");
                });
            })
            ->orderBy('published_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.news.index', compact('news', 'search', 'priority'));
    }

    public function create(): View
    {
        return view('admin.news.form', [
            'news' => new NewsArticle(['is_active' => true, 'priority' => 'standard', 'published_at' => now()]),
            'isEdit' => false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'headline' => 'nullable|string|max:255',
            'headline_kn' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'title_kn' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255|unique:news_articles,slug',
            'summary' => 'nullable|string',
            'summary_kn' => 'nullable|string',
            'content' => 'nullable|string',
            'content_kn' => 'nullable|string',
            'body' => 'nullable|string',
            'body_kn' => 'nullable|string',
            'source_name' => 'nullable|string|max:255',
            'source_url' => 'nullable|url|max:500',
            'priority' => 'nullable|string|in:breaking,important,high,standard',
            'image_url' => 'nullable|url|max:500',
            'published_at' => 'nullable|date',
            'is_active' => 'nullable|boolean',
        ]);

        $headline = $validated['headline'] ?? $validated['title'] ?? 'ಕೃಷಿ ಸುದ್ದಿ ಪ್ರಕಟಣೆ';
        $validated['headline'] = $headline;
        $validated['headline_kn'] = $validated['headline_kn'] ?? $validated['title_kn'] ?? null;
        $validated['content'] = $validated['content'] ?? $validated['body'] ?? null;
        $validated['content_kn'] = $validated['content_kn'] ?? $validated['body_kn'] ?? null;
        $validated['source_name'] = $validated['source_name'] ?? 'Department of Agriculture';
        $priority = $validated['priority'] ?? 'standard';
        $validated['priority'] = ($priority === 'high') ? 'important' : $priority;

        $validated['slug'] = !empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($headline);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['published_at'] = $validated['published_at'] ?? now();

        NewsArticle::create($validated);

        return redirect()->route('admin.news.index')->with('status', 'ಕೃಷಿ ಸುದ್ದಿಯನ್ನು ಪ್ರಕಟಿಸಲಾಗಿದೆ (News published successfully).');
    }

    public function edit(NewsArticle $news): View
    {
        return view('admin.news.form', [
            'news' => $news,
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, NewsArticle $news): RedirectResponse
    {
        $validated = $request->validate([
            'headline' => 'nullable|string|max:255',
            'headline_kn' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'title_kn' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255|unique:news_articles,slug,' . $news->id,
            'summary' => 'nullable|string',
            'summary_kn' => 'nullable|string',
            'content' => 'nullable|string',
            'content_kn' => 'nullable|string',
            'body' => 'nullable|string',
            'body_kn' => 'nullable|string',
            'source_name' => 'nullable|string|max:255',
            'source_url' => 'nullable|url|max:500',
            'priority' => 'nullable|string|in:breaking,important,high,standard',
            'image_url' => 'nullable|url|max:500',
            'published_at' => 'nullable|date',
            'is_active' => 'nullable|boolean',
        ]);

        $headline = $validated['headline'] ?? $validated['title'] ?? $news->headline;
        $validated['headline'] = $headline;
        $validated['headline_kn'] = $validated['headline_kn'] ?? $validated['title_kn'] ?? $news->headline_kn;
        $validated['content'] = $validated['content'] ?? $validated['body'] ?? $news->content;
        $validated['content_kn'] = $validated['content_kn'] ?? $validated['body_kn'] ?? $news->content_kn;
        $validated['source_name'] = $validated['source_name'] ?? $news->source_name;
        if (!empty($validated['priority'])) {
            $validated['priority'] = ($validated['priority'] === 'high') ? 'important' : $validated['priority'];
        }

        $validated['slug'] = !empty($validated['slug']) ? Str::slug($validated['slug']) : $news->slug;
        $validated['is_active'] = $request->boolean('is_active');

        $news->update($validated);

        return redirect()->route('admin.news.index')->with('status', 'ಸುದ್ದಿ ವಿವರಗಳನ್ನು ನವೀಕರಿಸಲಾಗಿದೆ (News updated successfully).');
    }

    public function toggle(NewsArticle $news): RedirectResponse
    {
        $news->update(['is_active' => !$news->is_active]);

        $msg = $news->is_active ? 'ಸುದ್ದಿಯನ್ನು ಸಕ್ರಿಯಗೊಳಿಸಲಾಗಿದೆ (Activated)' : 'ಸುದ್ದಿಯನ್ನು ನಿಷ್ಕ್ರಿಯಗೊಳಿಸಲಾಗಿದೆ (Deactivated)';
        return back()->with('status', $msg);
    }

    public function destroy(NewsArticle $news): RedirectResponse
    {
        $news->delete();

        return redirect()->route('admin.news.index')->with('status', 'ಸುದ್ದಿಯನ್ನು ಅಳಿಸಲಾಗಿದೆ (News deleted).');
    }
}

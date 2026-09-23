<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\NewsArticle;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsController extends Controller
{
    public function index(Request $request): View
    {
        $priority = $request->query('priority');

        $news = NewsArticle::active()
            ->when($priority, fn($q) => $q->where('priority', $priority))
            ->recent()
            ->paginate(12)
            ->withQueryString();

        $breakingNews = NewsArticle::active()->breaking()->recent()->take(3)->get();

        return view('farmer.news.index', compact('news', 'breakingNews', 'priority'));
    }

    public function show(string $slug): View
    {
        $news = NewsArticle::active()->where('slug', $slug)->firstOrFail();

        $recentNews = NewsArticle::active()
            ->where('id', '!=', $news->id)
            ->recent()
            ->take(4)
            ->get();

        return view('farmer.news.show', compact('news', 'recentNews'));
    }
}

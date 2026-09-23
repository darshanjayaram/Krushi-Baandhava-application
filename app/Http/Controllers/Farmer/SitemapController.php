<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Crop;
use App\Models\Market;
use App\Models\NewsArticle;
use App\Models\Scheme;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    /**
     * Generate dynamic XML sitemap for search engine indexing.
     */
    public function index(): Response
    {
        $xml = Cache::remember('public_sitemap_xml', 3600, function () {
            $baseUrl = rtrim(config('app.url', url('/')), '/');
            $now = Carbon::now()->toAtomString();

            $coreUrls = [
                ['url' => "{$baseUrl}/", 'priority' => '1.0', 'freq' => 'daily', 'lastmod' => $now],
                ['url' => "{$baseUrl}/crops", 'priority' => '0.9', 'freq' => 'daily', 'lastmod' => $now],
                ['url' => "{$baseUrl}/markets", 'priority' => '0.9', 'freq' => 'daily', 'lastmod' => $now],
                ['url' => "{$baseUrl}/nearby-markets", 'priority' => '0.9', 'freq' => 'daily', 'lastmod' => $now],
                ['url' => "{$baseUrl}/where-to-sell", 'priority' => '0.9', 'freq' => 'daily', 'lastmod' => $now],
                ['url' => "{$baseUrl}/weather", 'priority' => '0.8', 'freq' => 'daily', 'lastmod' => $now],
                ['url' => "{$baseUrl}/schemes", 'priority' => '0.8', 'freq' => 'daily', 'lastmod' => $now],
                ['url' => "{$baseUrl}/news", 'priority' => '0.8', 'freq' => 'hourly', 'lastmod' => $now],
                ['url' => "{$baseUrl}/videos", 'priority' => '0.7', 'freq' => 'weekly', 'lastmod' => $now],
                ['url' => "{$baseUrl}/articles", 'priority' => '0.7', 'freq' => 'weekly', 'lastmod' => $now],
            ];

            // Dynamic Entities
            $crops = Crop::where('is_active', true)->get(['slug', 'updated_at']);
            $markets = Market::karnataka()->where('is_active', true)->get(['code', 'updated_at']);
            $schemes = Scheme::active()->get(['slug', 'updated_at']);
            $news = NewsArticle::published()->get(['slug', 'updated_at']);
            $articles = Article::published()->get(['slug', 'updated_at']);

            $output = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $output .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

            // Add Core Pages
            foreach ($coreUrls as $item) {
                $output .= "  <url>\n";
                $output .= "    <loc>{$item['url']}</loc>\n";
                $output .= "    <lastmod>{$item['lastmod']}</lastmod>\n";
                $output .= "    <changefreq>{$item['freq']}</changefreq>\n";
                $output .= "    <priority>{$item['priority']}</priority>\n";
                $output .= "  </url>\n";
            }

            // Add Crops
            foreach ($crops as $c) {
                $lastmod = ($c->updated_at ?? Carbon::now())->toAtomString();
                $output .= "  <url>\n";
                $output .= "    <loc>{$baseUrl}/crops/{$c->slug}</loc>\n";
                $output .= "    <lastmod>{$lastmod}</lastmod>\n";
                $output .= "    <changefreq>daily</changefreq>\n";
                $output .= "    <priority>0.9</priority>\n";
                $output .= "  </url>\n";
            }

            // Add Mandis
            foreach ($markets as $m) {
                $lastmod = ($m->updated_at ?? Carbon::now())->toAtomString();
                $output .= "  <url>\n";
                $output .= "    <loc>{$baseUrl}/markets/{$m->code}</loc>\n";
                $output .= "    <lastmod>{$lastmod}</lastmod>\n";
                $output .= "    <changefreq>daily</changefreq>\n";
                $output .= "    <priority>0.8</priority>\n";
                $output .= "  </url>\n";
            }

            // Add Schemes
            foreach ($schemes as $s) {
                $lastmod = ($s->updated_at ?? Carbon::now())->toAtomString();
                $output .= "  <url>\n";
                $output .= "    <loc>{$baseUrl}/schemes/{$s->slug}</loc>\n";
                $output .= "    <lastmod>{$lastmod}</lastmod>\n";
                $output .= "    <changefreq>weekly</changefreq>\n";
                $output .= "    <priority>0.8</priority>\n";
                $output .= "  </url>\n";
            }

            // Add News
            foreach ($news as $n) {
                $lastmod = ($n->updated_at ?? Carbon::now())->toAtomString();
                $output .= "  <url>\n";
                $output .= "    <loc>{$baseUrl}/news/{$n->slug}</loc>\n";
                $output .= "    <lastmod>{$lastmod}</lastmod>\n";
                $output .= "    <changefreq>daily</changefreq>\n";
                $output .= "    <priority>0.7</priority>\n";
                $output .= "  </url>\n";
            }

            // Add Farming Guides
            foreach ($articles as $a) {
                $lastmod = ($a->updated_at ?? Carbon::now())->toAtomString();
                $output .= "  <url>\n";
                $output .= "    <loc>{$baseUrl}/articles/{$a->slug}</loc>\n";
                $output .= "    <lastmod>{$lastmod}</lastmod>\n";
                $output .= "    <changefreq>monthly</changefreq>\n";
                $output .= "    <priority>0.7</priority>\n";
                $output .= "  </url>\n";
            }

            $output .= '</urlset>';

            return $output;
        });

        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }
}

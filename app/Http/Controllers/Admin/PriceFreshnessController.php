<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Crop;
use App\Models\CropCategory;
use App\Models\MarketPrice;
use App\Models\SystemSetting;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Throwable;

class PriceFreshnessController extends Controller
{
    /**
     * Standard baseline staleness defaults.
     */
    protected array $defaultCategoryDays = [
        'vegetables' => 7,
        'fruits' => 7,
        'cereals-millets' => 21,
        'pulses' => 21,
        'commercial-plantation' => 30,
        'spices' => 30,
        'oilseeds' => 30,
        'commercial-crops' => 30,
    ];

    /**
     * Display the Price Freshness & Staleness Rules module.
     */
    public function index(Request $request): View
    {
        $globalDays = (int) SystemSetting::get('crop_price_staleness_days', 14);
        $categoryDays = SystemSetting::get('category_price_staleness_days', []);
        if (!is_array($categoryDays)) {
            $categoryDays = [];
        }

        $categories = CropCategory::where('is_active', true)
            ->withCount(['crops' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('display_order')
            ->get();

        $platformMaxDate = MarketPrice::karnataka()->max('price_date') ?? Carbon::today()->toDateString();
        $anchorDate = Carbon::today()->gt(Carbon::parse($platformMaxDate)) ? Carbon::today() : Carbon::parse($platformMaxDate);

        $evaluation = $this->evaluateCrops($globalDays, $categoryDays, $anchorDate->toDateString());

        return view('admin.price-freshness.index', [
            'globalDays' => $globalDays,
            'categoryDays' => $categoryDays,
            'defaultCategoryDays' => $this->defaultCategoryDays,
            'categories' => $categories,
            'platformMaxDate' => $platformMaxDate,
            'anchorDate' => $anchorDate->toDateString(),
            'stats' => $evaluation['stats'],
            'crops' => $evaluation['crops'],
        ]);
    }

    /**
     * Asynchronously save staleness rules and return updated simulation.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'global_days' => 'required|integer|min:1|max:90',
            'category_days' => 'nullable|array',
            'category_days.*' => 'nullable|integer|min:1|max:90',
        ]);

        $oldGlobal = SystemSetting::get('crop_price_staleness_days', 14);
        $oldCategory = SystemSetting::get('category_price_staleness_days', []);

        $globalDays = max(1, (int) $validated['global_days']);
        $rawCatDays = $validated['category_days'] ?? [];
        $sanitizedCategoryDays = [];

        foreach ($rawCatDays as $catSlug => $days) {
            if ($days !== null && $days !== '') {
                $sanitizedCategoryDays[trim((string) $catSlug)] = max(1, (int) $days);
            }
        }

        SystemSetting::set('crop_price_staleness_days', $globalDays, 'integer', 'price_freshness', 'Global default maximum age in days before APMC market prices are considered stale.');
        SystemSetting::set('category_price_staleness_days', $sanitizedCategoryDays, 'json', 'price_freshness', 'Dynamic staleness window thresholds in days by agricultural crop category.');

        // Invalidate specific setting caches
        Cache::forget('system_setting_crop_price_staleness_days');
        Cache::forget('system_setting_category_price_staleness_days');

        // Audit Log
        if (class_exists(AuditLog::class)) {
            try {
                AuditLog::create([
                    'user_id' => Auth::id(),
                    'action' => 'update_price_freshness_rules',
                    'entity_type' => 'SystemSetting',
                    'entity_id' => null,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'old_values' => [
                        'crop_price_staleness_days' => $oldGlobal,
                        'category_price_staleness_days' => $oldCategory,
                    ],
                    'new_values' => [
                        'crop_price_staleness_days' => $globalDays,
                        'category_price_staleness_days' => $sanitizedCategoryDays,
                    ],
                    'created_at' => now(),
                ]);
            } catch (Throwable) {
                // Ignore audit failure if schema mismatch
            }
        }

        $platformMaxDate = MarketPrice::karnataka()->max('price_date') ?? Carbon::today()->toDateString();
        $anchorDate = Carbon::today()->gt(Carbon::parse($platformMaxDate)) ? Carbon::today() : Carbon::parse($platformMaxDate);
        $evaluation = $this->evaluateCrops($globalDays, $sanitizedCategoryDays, $anchorDate->toDateString());

        return response()->json([
            'success' => true,
            'message' => 'Price freshness rules and category thresholds updated successfully.',
            'global_days' => $globalDays,
            'category_days' => $sanitizedCategoryDays,
            'stats' => $evaluation['stats'],
            'crops' => $evaluation['crops'],
        ]);
    }

    /**
     * Asynchronously simulate crop active/stale status without persisting changes.
     */
    public function simulate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'global_days' => 'required|integer|min:1|max:90',
            'category_days' => 'nullable|array',
            'category_days.*' => 'nullable|integer|min:1|max:90',
        ]);

        $globalDays = max(1, (int) $validated['global_days']);
        $rawCatDays = $validated['category_days'] ?? [];
        $sanitizedCategoryDays = [];

        foreach ($rawCatDays as $catSlug => $days) {
            if ($days !== null && $days !== '') {
                $sanitizedCategoryDays[trim((string) $catSlug)] = max(1, (int) $days);
            }
        }

        $platformMaxDate = MarketPrice::karnataka()->max('price_date') ?? Carbon::today()->toDateString();
        $anchorDate = Carbon::today()->gt(Carbon::parse($platformMaxDate)) ? Carbon::today() : Carbon::parse($platformMaxDate);
        $evaluation = $this->evaluateCrops($globalDays, $sanitizedCategoryDays, $anchorDate->toDateString());

        return response()->json([
            'success' => true,
            'message' => 'Simulation completed.',
            'global_days' => $globalDays,
            'category_days' => $sanitizedCategoryDays,
            'stats' => $evaluation['stats'],
            'crops' => $evaluation['crops'],
        ]);
    }

    /**
     * Asynchronously reset thresholds to standard system defaults.
     */
    public function reset(Request $request): JsonResponse
    {
        $globalDays = 14;
        $categoryDays = $this->defaultCategoryDays;

        SystemSetting::set('crop_price_staleness_days', $globalDays, 'integer', 'price_freshness', 'Global default maximum age in days before APMC market prices are considered stale.');
        SystemSetting::set('category_price_staleness_days', $categoryDays, 'json', 'price_freshness', 'Dynamic staleness window thresholds in days by agricultural crop category.');

        Cache::forget('system_setting_crop_price_staleness_days');
        Cache::forget('system_setting_category_price_staleness_days');

        $platformMaxDate = MarketPrice::karnataka()->max('price_date') ?? Carbon::today()->toDateString();
        $anchorDate = Carbon::today()->gt(Carbon::parse($platformMaxDate)) ? Carbon::today() : Carbon::parse($platformMaxDate);
        $evaluation = $this->evaluateCrops($globalDays, $categoryDays, $anchorDate->toDateString());

        return response()->json([
            'success' => true,
            'message' => 'Freshness rules restored to recommended system defaults.',
            'global_days' => $globalDays,
            'category_days' => $categoryDays,
            'stats' => $evaluation['stats'],
            'crops' => $evaluation['crops'],
        ]);
    }

    /**
     * Compute impact metrics and active/suppressed status for all crops.
     */
    protected function evaluateCrops(int $globalDays, array $categoryDays, string $anchorDate): array
    {
        $crops = Crop::with(['category', 'varieties' => fn ($q) => $q->where('is_active', true)])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Cached aggregation query for latest dates and mandis per crop (5 minutes TTL for instant async response)
        $latestDatesByCrop = Cache::remember('price_freshness_crop_stats', 300, function () {
            return MarketPrice::karnataka()
                ->selectRaw('crop_id, MAX(price_date) as max_date, COUNT(DISTINCT market_id) as total_mandis')
                ->groupBy('crop_id')
                ->get()
                ->keyBy('crop_id');
        });

        $cropsList = [];
        $activeCount = 0;
        $staleCount = 0;
        $noDataCount = 0;

        foreach ($crops as $crop) {
            $catSlug = $crop->category?->slug ?? '';
            $thresholdDays = isset($categoryDays[$catSlug]) && is_numeric($categoryDays[$catSlug])
                ? max(1, (int) $categoryDays[$catSlug])
                : max(1, $globalDays);

            $cutoffDate = Carbon::parse($anchorDate)->subDays($thresholdDays)->toDateString();
            $priceStat = $latestDatesByCrop->get($crop->id);
            $lastTradedDate = $priceStat?->max_date;

            $status = 'stale';
            $statusLabel = 'Suppressed (Stale)';
            $badgeColor = 'rose';

            if (!$lastTradedDate) {
                $status = 'no_data';
                $statusLabel = 'No Historical Data';
                $badgeColor = 'slate';
                $noDataCount++;
            } elseif ($lastTradedDate >= $cutoffDate) {
                $status = 'active';
                $statusLabel = 'Active on Platform';
                $badgeColor = 'emerald';
                $activeCount++;
            } else {
                $staleCount++;
            }

            $cropsList[] = [
                'id' => $crop->id,
                'name' => $crop->name,
                'name_kn' => $crop->name_kn,
                'slug' => $crop->slug,
                'category_name' => $crop->category?->name ?? 'Uncategorized',
                'category_slug' => $catSlug,
                'threshold_days' => $thresholdDays,
                'cutoff_date' => $cutoffDate,
                'last_traded_date' => $lastTradedDate,
                'days_since_trade' => $lastTradedDate ? Carbon::parse($lastTradedDate)->diffInDays(Carbon::parse($anchorDate)) : null,
                'status' => $status,
                'status_label' => $statusLabel,
                'badge_color' => $badgeColor,
                'total_mandis' => (int) ($priceStat?->total_mandis ?? 0),
                'varieties_count' => $crop->varieties->count(),
                'photo_url' => $crop->image_url ?: asset('images/crops/' . ($crop->slug . '.jpg')),
            ];
        }

        $totalCrops = count($cropsList);
        $activePercentage = $totalCrops > 0 ? round(($activeCount / $totalCrops) * 100, 1) : 0;

        return [
            'stats' => [
                'total_crops' => $totalCrops,
                'active_count' => $activeCount,
                'stale_count' => $staleCount,
                'no_data_count' => $noDataCount,
                'active_percentage' => $activePercentage,
                'anchor_date' => $anchorDate,
                'global_days' => $globalDays,
            ],
            'crops' => $cropsList,
        ];
    }
}

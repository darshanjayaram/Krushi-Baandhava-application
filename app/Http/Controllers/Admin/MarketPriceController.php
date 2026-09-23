<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Crop;
use App\Models\DataSource;
use App\Models\District;
use App\Models\Market;
use App\Models\MarketPrice;
use App\Services\Ingestion\MarketPriceIngestionService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketPriceController extends Controller
{
    public function __construct(
        protected MarketPriceIngestionService $ingestionService
    ) {
    }

    /**
     * Display a listing of canonical market prices with filtering and analytics.
     */
    public function index(Request $request): View
    {
        $cropId = $request->query('crop_id');
        $marketId = $request->query('market_id');
        $districtId = $request->query('district_id');
        $dataSourceId = $request->query('data_source_id');
        $date = $request->query('date');
        $search = $request->query('search');

        $query = MarketPrice::with(['crop', 'variety', 'market.district', 'dataSource'])
            ->orderBy('price_date', 'desc')
            ->orderBy('created_at', 'desc');

        if ($cropId) {
            $query->where('crop_id', $cropId);
        }

        if ($marketId) {
            $query->where('market_id', $marketId);
        }

        if ($districtId) {
            $query->whereHas('market', fn ($m) => $m->where('district_id', $districtId));
        }

        if ($dataSourceId) {
            $query->where('data_source_id', $dataSourceId);
        }

        if ($date) {
            $query->where('price_date', $date);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('crop', function ($cq) use ($search) {
                    $cq->where('name', 'like', "%{$search}%")
                       ->orWhere('name_kn', 'like', "%{$search}%");
                })->orWhereHas('market', function ($mq) use ($search) {
                    $mq->where('name', 'like', "%{$search}%")
                       ->orWhere('name_kn', 'like', "%{$search}%");
                });
            });
        }

        $prices = $query->paginate(25)->withQueryString();

        // High-level statistics
        $totalPrices = MarketPrice::count();
        $latestDate = MarketPrice::max('price_date') ?? Carbon::today()->toDateString();
        $latestCount = MarketPrice::where('price_date', $latestDate)->count();
        $activeMarketsCount = MarketPrice::where('price_date', $latestDate)->distinct('market_id')->count('market_id');
        $avgSpread = MarketPrice::where('price_date', $latestDate)
            ->whereNotNull('max_price')
            ->whereNotNull('min_price')
            ->selectRaw('AVG(max_price - min_price) as avg_spread')
            ->value('avg_spread') ?? 0;

        $crops = Crop::where('is_active', true)->orderBy('name')->get();
        $markets = Market::where('is_active', true)->with('district')->orderBy('name')->get();
        $districts = District::where('is_active', true)->orderBy('name')->get();
        $dataSources = DataSource::orderBy('name')->get();

        return view('admin.prices.index', compact(
            'prices',
            'totalPrices',
            'latestDate',
            'latestCount',
            'activeMarketsCount',
            'avgSpread',
            'crops',
            'markets',
            'districts',
            'dataSources',
            'cropId',
            'marketId',
            'districtId',
            'dataSourceId',
            'date',
            'search'
        ));
    }

    /**
     * Trigger on-demand sync from the admin prices view.
     */
    public function sync(Request $request): RedirectResponse
    {
        $sourceId = $request->input('data_source_id');
        $date = $request->input('target_date', Carbon::today()->toDateString());

        if ($sourceId) {
            $source = DataSource::findOrFail($sourceId);
            $result = $this->ingestionService->ingest($source, [
                'filters' => ['date' => $date],
            ]);

            $msg = "Synced {$source->name}: {$result['inserted']} new canonical records, {$result['updated']} updated, {$result['duplicate']} duplicates skipped.";
            return redirect()->route('admin.prices.index')->with('success', $msg);
        }

        // Sync all active sources
        $activeSources = DataSource::where('is_active', true)->get();
        $totalInserted = 0;
        $totalUpdated = 0;
        $totalDuplicates = 0;

        foreach ($activeSources as $source) {
            $res = $this->ingestionService->ingest($source, [
                'filters' => ['date' => $date],
            ]);
            $totalInserted += $res['inserted'];
            $totalUpdated += $res['updated'];
            $totalDuplicates += $res['duplicate'];
        }

        $msg = "Batch sync complete: {$totalInserted} new records, {$totalUpdated} updated, {$totalDuplicates} duplicates skipped.";
        return redirect()->route('admin.prices.index')->with('success', $msg);
    }
}

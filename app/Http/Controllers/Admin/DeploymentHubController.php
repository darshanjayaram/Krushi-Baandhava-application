<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Crop;
use App\Models\CropSourceMapping;
use App\Models\CropVariety;
use App\Models\DataSource;
use App\Models\District;
use App\Models\Market;
use App\Models\MarketPrice;
use App\Models\MarketSourceMapping;
use App\Services\Ingestion\MarketPriceIngestionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class DeploymentHubController extends Controller
{
    /**
     * Display the Fresh Deployment & APMC Discovery Hub.
     */
    public function index()
    {
        $districtsCount = District::count();
        $marketsCount = Market::count();
        $cropsCount = Crop::count();
        $varietiesCount = CropVariety::count();
        $pricesCount = MarketPrice::count();
        $dataSources = DataSource::all();

        $unresolvedCropsCount = CropSourceMapping::whereNull('crop_id')->count();
        $unresolvedMarketsCount = MarketSourceMapping::whereNull('market_id')->count();

        $recentMandis = Market::with('district')->latest()->take(6)->get();
        $recentCrops = Crop::with('varieties')->latest()->take(6)->get();

        return view('admin.deployment-hub.index', compact(
            'districtsCount',
            'marketsCount',
            'cropsCount',
            'varietiesCount',
            'pricesCount',
            'dataSources',
            'unresolvedCropsCount',
            'unresolvedMarketsCount',
            'recentMandis',
            'recentCrops'
        ));
    }

    /**
     * Discover and auto-provision missing mandis from live API feed.
     */
    public function discoverMandis(MarketPriceIngestionService $ingestionService)
    {
        try {
            $beforeCount = Market::count();
            $result = $ingestionService->ingest('data_gov_mandi', ['force' => true]);
            $afterCount = Market::count();
            $discovered = max(0, $afterCount - $beforeCount);

            if ($discovered > 0) {
                return back()->with('success', "API Discovery complete: Discovered and auto-provisioned {$discovered} new APMC mandis from data.gov.in!");
            }

            return back()->with('success', "API Discovery complete: All reporting APMC mandis are already registered and mapped (Total: {$afterCount} active mandis).");
        } catch (\Throwable $e) {
            return back()->with('error', 'Discovery failed: ' . $e->getMessage());
        }
    }

    /**
     * Discover and auto-provision missing crops from live API feed.
     */
    public function discoverCrops(MarketPriceIngestionService $ingestionService)
    {
        try {
            $beforeCount = Crop::count();
            $result = $ingestionService->ingest('data_gov_mandi', ['force' => true]);
            $afterCount = Crop::count();
            $discovered = max(0, $afterCount - $beforeCount);

            if ($discovered > 0) {
                return back()->with('success', "API Discovery complete: Discovered and auto-provisioned {$discovered} new crop commodities from data.gov.in!");
            }

            return back()->with('success', "API Discovery complete: All reporting commodities are already registered and mapped (Total: {$afterCount} crops).");
        } catch (\Throwable $e) {
            return back()->with('error', 'Discovery failed: ' . $e->getMessage());
        }
    }

    /**
     * 1-Click re-import / ensure standard Karnataka master records.
     */
    public function importStandardMaster()
    {
        try {
            Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\GeographicSeeder', '--force' => true]);
            Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\KarnatakaMandiAliasSeeder', '--force' => true]);
            Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\CropMasterSeeder', '--force' => true]);
            Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\AddMissedKarnatakaCropsSeeder', '--force' => true]);

            Artisan::call('optimize:clear');

            $districts = District::count();
            $markets = Market::count();
            $crops = Crop::count();

            return back()->with('success', "Standard Karnataka master directories synchronized! Currently active: {$districts} Districts, {$markets} APMC Mandis, and {$crops} Crops.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Master synchronization failed: ' . $e->getMessage());
        }
    }

    /**
     * 1-Click trigger live APMC price sync from data.gov.in or CEDA.
     */
    public function syncLivePrices(MarketPriceIngestionService $ingestionService, Request $request)
    {
        $source = $request->input('source', 'data_gov_mandi');
        try {
            $result = $ingestionService->ingest($source, ['force' => true]);
            $msg = "Live Sync completed for [{$source}]: {$result['received']} records received, {$result['inserted']} new rates inserted, {$result['updated']} rates updated ({$result['duration_ms']}ms).";
            return back()->with('success', $msg);
        } catch (\Throwable $e) {
            return back()->with('error', 'Live price sync failed: ' . $e->getMessage());
        }
    }
}

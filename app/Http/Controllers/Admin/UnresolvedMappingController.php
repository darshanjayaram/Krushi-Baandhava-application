<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Crop;
use App\Models\CropSourceMapping;
use App\Models\DataSource;
use App\Models\Market;
use App\Models\MarketPriceRaw;
use App\Models\MarketSourceMapping;
use App\Services\Ingestion\MarketPriceIngestionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UnresolvedMappingController extends Controller
{
    /**
     * Display unmapped commodity and market aliases detected from rejected raw feeds.
     */
    public function index(): View
    {
        // Fetch all rejected records to detect unresolved commodity and market aliases
        $rejectedRecords = MarketPriceRaw::with('dataSource')
            ->where('processing_status', 'rejected')
            ->get();

        $unresolvedCrops = [];
        $unresolvedMarkets = [];

        foreach ($rejectedRecords as $rec) {
            $rawCrop = null;
            $rawMarket = null;
            $rawDistrict = null;

            // 1. Try extracting from error_message
            if (preg_match("/Unmapped commodity alias: '([^']+)'/", $rec->error_message ?? '', $matches)) {
                $rawCrop = $matches[1];
            }
            if (preg_match("/Unmapped market alias: '([^']+)'/", $rec->error_message ?? '', $matches)) {
                $rawMarket = $matches[1];
            }

            // 2. Fallback to extracting from payload if error_message didn't have explicit alias
            if ((!$rawCrop || !$rawMarket) && is_array($rec->payload)) {
                $p = $rec->payload;
                if (isset($p['data'][0]) && is_array($p['data'][0])) {
                    $item = $p['data'][0];
                } else {
                    $item = $p;
                }

                if (!$rawCrop) {
                    $rawCrop = $item['Commodity'] ?? ($item['cmdt_name'] ?? ($item['commodity_name'] ?? ($item['crop'] ?? ($item['commodity'] ?? null))));
                }
                if (!$rawMarket) {
                    $rawMarket = $item['Market'] ?? ($item['market_name'] ?? ($item['market'] ?? null));
                }
                if (!$rawDistrict) {
                    $rawDistrict = $item['District'] ?? ($item['district_name'] ?? ($item['district'] ?? null));
                }
            }

            // Add to unresolved crops list if present and not 'Unknown'
            if (!empty($rawCrop) && strtolower(trim($rawCrop)) !== 'unknown') {
                $rawCrop = trim($rawCrop);
                $dsId = $rec->data_source_id;
                $key = "{$dsId}_{$rawCrop}";

                if (!isset($unresolvedCrops[$key])) {
                    $unresolvedCrops[$key] = [
                        'data_source_id' => $dsId,
                        'data_source_name' => $rec->dataSource?->name ?? 'Unknown',
                        'raw_crop_name' => $rawCrop,
                        'count' => 0,
                        'last_seen' => $rec->received_at,
                    ];
                }
                $unresolvedCrops[$key]['count']++;
            }

            // Add to unresolved markets list if present and not 'Unknown'
            if (!empty($rawMarket) && strtolower(trim($rawMarket)) !== 'unknown') {
                $rawMarket = trim($rawMarket);
                $dsId = $rec->data_source_id;
                $key = "{$dsId}_{$rawMarket}";

                if (!isset($unresolvedMarkets[$key])) {
                    $unresolvedMarkets[$key] = [
                        'data_source_id' => $dsId,
                        'data_source_name' => $rec->dataSource?->name ?? 'Unknown',
                        'raw_market_name' => $rawMarket,
                        'raw_district_name' => $rawDistrict,
                        'count' => 0,
                        'last_seen' => $rec->received_at,
                    ];
                }
                $unresolvedMarkets[$key]['count']++;
            }
        }

        $canonicalCrops = Crop::with('category')->orderBy('name')->get();
        $canonicalMarkets = Market::with('district')->karnataka()->orderBy('name')->get();
        $dataSources = DataSource::orderBy('name')->get();

        return view('admin.data_quality.unresolved', compact(
            'unresolvedCrops',
            'unresolvedMarkets',
            'canonicalCrops',
            'canonicalMarkets',
            'dataSources'
        ));
    }

    /**
     * Map a raw commodity alias to a canonical Crop and trigger auto-reprocess.
     */
    public function resolveCrop(Request $request, MarketPriceIngestionService $service): RedirectResponse
    {
        $validated = $request->validate([
            'data_source_id' => ['required', 'exists:data_sources,id'],
            'source_crop_name' => ['required', 'string', 'max:255'],
            'crop_id' => ['required', 'exists:crops,id'],
        ]);

        $mapping = CropSourceMapping::updateOrCreate(
            [
                'data_source_id' => $validated['data_source_id'],
                'source_crop_name' => $validated['source_crop_name'],
            ],
            [
                'crop_id' => $validated['crop_id'],
                'is_verified' => true,
            ]
        );

        AuditLog::log(
            'mapping.resolve_crop',
            'CropSourceMapping',
            $mapping->id,
            null,
            $mapping->toArray()
        );

        // Auto-reprocess affected records
        $reprocessResults = $service->reprocessBatch($validated['data_source_id'], $validated['source_crop_name']);

        $crop = Crop::find($validated['crop_id']);

        return redirect()->route('admin.unresolved-mappings.index')
            ->with('success', "Mapped alias '{$validated['source_crop_name']}' to '{$crop->name}'. Auto-reprocessed: {$reprocessResults['processed']} records converted to active market prices.");
    }

    /**
     * Map a raw mandi alias to a canonical Market and trigger auto-reprocess.
     */
    public function resolveMarket(Request $request, MarketPriceIngestionService $service): RedirectResponse
    {
        $validated = $request->validate([
            'data_source_id' => ['required', 'exists:data_sources,id'],
            'source_market_name' => ['required', 'string', 'max:255'],
            'market_id' => ['required', 'exists:markets,id'],
            'source_district_name' => ['nullable', 'string', 'max:150'],
        ]);

        $mapping = MarketSourceMapping::updateOrCreate(
            [
                'data_source_id' => $validated['data_source_id'],
                'source_market_name' => $validated['source_market_name'],
                'source_district_name' => $validated['source_district_name'] ?? null,
            ],
            [
                'market_id' => $validated['market_id'],
                'is_verified' => true,
            ]
        );

        AuditLog::log(
            'mapping.resolve_market',
            'MarketSourceMapping',
            $mapping->id,
            null,
            $mapping->toArray()
        );

        // Auto-reprocess affected records
        $reprocessResults = $service->reprocessBatch($validated['data_source_id'], $validated['source_market_name']);

        $market = Market::find($validated['market_id']);

        return redirect()->route('admin.unresolved-mappings.index')
            ->with('success', "Mapped alias '{$validated['source_market_name']}' to '{$market->name}'. Auto-reprocessed: {$reprocessResults['processed']} records converted to active market prices.");
    }
}

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
        // Fetch rejected records with unmapped commodity aliases
        $cropErrors = MarketPriceRaw::where('processing_status', 'rejected')
            ->where('error_message', 'like', 'Unmapped commodity alias:%')
            ->get();

        $unresolvedCrops = [];
        foreach ($cropErrors as $rec) {
            if (preg_match("/Unmapped commodity alias: '([^']+)'/", $rec->error_message, $matches)) {
                $rawCrop = $matches[1];
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
        }

        // Fetch rejected records with unmapped market aliases
        $marketErrors = MarketPriceRaw::where('processing_status', 'rejected')
            ->where('error_message', 'like', 'Unmapped market alias:%')
            ->get();

        $unresolvedMarkets = [];
        foreach ($marketErrors as $rec) {
            if (preg_match("/Unmapped market alias: '([^']+)'/", $rec->error_message, $matches)) {
                $rawMarket = $matches[1];
                $dsId = $rec->data_source_id;
                $key = "{$dsId}_{$rawMarket}";

                if (!isset($unresolvedMarkets[$key])) {
                    $unresolvedMarkets[$key] = [
                        'data_source_id' => $dsId,
                        'data_source_name' => $rec->dataSource?->name ?? 'Unknown',
                        'raw_market_name' => $rawMarket,
                        'raw_district_name' => $rec->payload['District'] ?? $rec->payload['district'] ?? null,
                        'count' => 0,
                        'last_seen' => $rec->received_at,
                    ];
                }
                $unresolvedMarkets[$key]['count']++;
            }
        }

        $canonicalCrops = Crop::orderBy('name')->get();
        $canonicalMarkets = Market::karnataka()->orderBy('name')->get();
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

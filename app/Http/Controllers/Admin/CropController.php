<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CropRequest;
use App\Models\AuditLog;
use App\Models\Crop;
use App\Models\CropCategory;
use App\Models\CropSourceMapping;
use App\Models\DataSource;
use App\Services\Ingestion\MarketPriceIngestionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CropController extends Controller
{
    /**
     * Display a listing of crops.
     */
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $categoryId = $request->query('category_id');

        $crops = Crop::with(['category', 'sourceMappings.dataSource', 'varieties.sourceMappings.dataSource'])
            ->withCount(['varieties', 'prices'])
            ->when($categoryId, function ($query, $categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('name_kn', 'like', "%{$search}%")
                        ->orWhere('scientific_name', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $categories = CropCategory::where('is_active', true)->orderBy('display_order')->get();

        return view('admin.master.crops.index', compact('crops', 'categories', 'search', 'categoryId'));
    }

    /**
     * Show the form for creating a new crop.
     */
    public function create(Request $request): View
    {
        $categories = CropCategory::where('is_active', true)->orderBy('display_order')->get();

        return view('admin.master.crops.form', [
            'crop' => new Crop([
                'category_id' => $request->query('category_id') ?? $categories->first()?->id,
                'standard_unit' => 'Quintal',
                'is_major' => false,
                'is_active' => true,
            ]),
            'categories' => $categories,
            'isEdit' => false,
        ]);
    }

    /**
     * Store a newly created crop.
     */
    public function store(CropRequest $request): RedirectResponse
    {
        $data = $request->validated();
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }
        $data['is_major'] = $request->boolean('is_major');
        $data['is_active'] = $request->boolean('is_active');
        $data['allow_user_sort_toggle'] = $request->boolean('allow_user_sort_toggle');
        $data['enable_smart_badges'] = $request->boolean('enable_smart_badges');
        $data['market_radius_km'] = $request->filled('market_radius_km') ? (int) $request->input('market_radius_km') : 300;
        $data['default_market_sort'] = $request->input('default_market_sort', 'nearest_first') ?: 'nearest_first';

        $crop = Crop::create($data);

        AuditLog::log('crop.create', 'Crop', $crop->id, null, $crop->toArray());

        return redirect()->route('admin.crops.index')
            ->with('success', "Crop '{$crop->name}' registered successfully.");
    }

    /**
     * Show the form for editing the crop and managing varieties.
     */
    public function edit(Crop $crop): View
    {
        $crop->load([
            'varieties' => fn ($q) => $q->with('sourceMappings.dataSource')->orderBy('name'),
            'sourceMappings.dataSource',
        ]);
        $categories = CropCategory::where('is_active', true)->orderBy('display_order')->get();
        $dataSources = DataSource::where('is_active', true)->orderBy('name')->get();

        return view('admin.master.crops.form', [
            'crop' => $crop,
            'categories' => $categories,
            'dataSources' => $dataSources,
            'isEdit' => true,
        ]);
    }

    /**
     * Update the specified crop.
     */
    public function update(CropRequest $request, Crop $crop): RedirectResponse
    {
        $oldValues = $crop->toArray();
        $data = $request->validated();
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }
        $data['is_major'] = $request->boolean('is_major');
        $data['is_active'] = $request->boolean('is_active');
        $data['allow_user_sort_toggle'] = $request->boolean('allow_user_sort_toggle');
        $data['enable_smart_badges'] = $request->boolean('enable_smart_badges');
        $data['market_radius_km'] = $request->filled('market_radius_km') ? (int) $request->input('market_radius_km') : 300;
        $data['default_market_sort'] = $request->input('default_market_sort', 'nearest_first') ?: 'nearest_first';

        $crop->update($data);

        AuditLog::log('crop.update', 'Crop', $crop->id, $oldValues, $crop->toArray());

        return redirect()->route('admin.crops.index')
            ->with('success', "Crop '{$crop->name}' updated successfully.");
    }

    /**
     * Toggle the active status of a crop.
     */
    public function toggleStatus(Crop $crop): RedirectResponse
    {
        $crop->is_active = !$crop->is_active;
        $crop->save();

        AuditLog::log('crop.status_toggle', 'Crop', $crop->id, null, ['is_active' => $crop->is_active]);

        $statusLabel = $crop->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Crop '{$crop->name}' was {$statusLabel}.");
    }

    /**
     * Add a raw API variety alias mapped to a specific canonical crop grade.
     */
    public function addVarietyAlias(Request $request, Crop $crop, MarketPriceIngestionService $service): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'crop_variety_id' => ['required', 'exists:crop_varieties,id'],
            'source_variety_name' => ['required', 'string', 'max:150'],
            'data_source_id' => ['nullable', 'exists:data_sources,id'],
        ]);

        $variety = $crop->varieties()->where('id', $validated['crop_variety_id'])->firstOrFail();
        $dataSourceId = !empty($validated['data_source_id'])
            ? (int) $validated['data_source_id']
            : (DataSource::where('code', 'data_gov_mandi')->value('id') ?? DataSource::first()->id);

        $mapping = CropSourceMapping::updateOrCreate(
            [
                'data_source_id' => $dataSourceId,
                'source_crop_name' => $crop->name,
                'source_variety_name' => trim($validated['source_variety_name']),
            ],
            [
                'crop_id' => $crop->id,
                'crop_variety_id' => $variety->id,
                'confidence_score' => 1.00,
                'is_verified' => true,
            ]
        );

        AuditLog::log('crop.variety_alias_add', 'CropSourceMapping', $mapping->id, null, $mapping->toArray());

        // Auto-reprocess affected records
        $reprocessResults = $service->reprocessBatch($dataSourceId, $crop->name);

        $msg = "Raw variety alias '{$validated['source_variety_name']}' successfully mapped to grade '{$variety->name}'.";
        if ($reprocessResults['processed'] > 0) {
            $msg .= " Auto-reprocessed {$reprocessResults['processed']} existing raw records into active prices!";
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'message' => $msg,
                'mapping' => [
                    'id' => $mapping->id,
                    'raw_name' => $mapping->source_variety_name,
                    'variety_id' => $variety->id,
                    'variety_name' => $variety->name,
                ],
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Remove a variety alias mapping from this crop.
     */
    public function removeVarietyAlias(Request $request, Crop $crop, CropSourceMapping $mapping): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        if ($mapping->crop_id !== $crop->id) {
            abort(403, 'Unauthorized variety alias modification.');
        }

        $aliasName = $mapping->source_variety_name;
        $mappingId = $mapping->id;
        $mapping->delete();

        AuditLog::log('crop.variety_alias_remove', 'CropSourceMapping', $mappingId, null, ['deleted_alias' => $aliasName]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'message' => "Variety alias '{$aliasName}' removed.",
                'raw_name' => $aliasName,
            ]);
        }

        return back()->with('success', "Variety alias '{$aliasName}' removed from {$crop->name}.");
    }

    /**
     * Fetch live raw variety strings for this crop from external data sources
     * and report whether each string is already mapped or unmapped.
     */
    public function liveVarieties(Request $request, Crop $crop): \Illuminate\Http\JsonResponse
    {
        $dataSourceId = $request->query('data_source_id');
        $dataSource = $dataSourceId ? DataSource::find($dataSourceId) : null;

        if (!$dataSource) {
            if ($crop->isCoffeeBoard()) {
                $dataSource = DataSource::where('code', 'coffee_board')->first();
            } elseif ($crop->isCoconutBoard()) {
                $dataSource = DataSource::where('code', 'coconut_board')->first();
            } else {
                $dataSource = DataSource::where('code', 'tss_sirsi')->where('is_active', true)->first()
                    ?? DataSource::where('code', 'ceda_agmarknet')->where('is_active', true)->first()
                    ?? DataSource::where('code', 'data_gov_mandi')->first()
                    ?? DataSource::first();
            }
        }

        if (!$dataSource) {
            return response()->json(['ok' => false, 'error' => 'No active data source found for this crop.'], 404);
        }

        try {
            $provider = \App\Services\DataSources\DataSourceRegistry::make($dataSource);
            
            // 1. Check raw records in database
            $rawRecords = \App\Models\MarketPriceRaw::where('data_source_id', $dataSource->id)
                ->latest()
                ->take(150)
                ->get();

            $extractedVarieties = [];

            if ($rawRecords->isNotEmpty()) {
                foreach ($rawRecords as $rec) {
                    $payload = $rec->payload ?? [];
                    $cName = $payload['source_crop'] ?? $payload['commodity'] ?? $payload['Commodity'] ?? '';
                    if (stripos($cName, $crop->name) !== false || stripos($crop->name, $cName) !== false || empty($cName)) {
                        $vName = trim($payload['source_variety'] ?? $payload['variety'] ?? $payload['Variety'] ?? $payload['grade'] ?? '');
                        if ($vName !== '' && !isset($extractedVarieties[$vName])) {
                            $extractedVarieties[$vName] = [
                                'name' => $vName,
                                'price' => (float) ($payload['modal_price'] ?? $payload['Modal_Price'] ?? $payload['max_price'] ?? $payload['min_price'] ?? 0),
                                'market' => $payload['source_market'] ?? $payload['market'] ?? $payload['Market'] ?? 'APMC Mandi',
                                'date' => isset($payload['price_date']) ? \Carbon\Carbon::parse($payload['price_date'])->format('d M') : now()->format('d M'),
                            ];
                        }
                    }
                }
            }

            // 2. Also check processed market prices for this crop
            $processedPrices = \App\Models\MarketPrice::with(['variety', 'market'])
                ->where('crop_id', $crop->id)
                ->where('data_source_id', $dataSource->id)
                ->latest('price_date')
                ->take(30)
                ->get();

            foreach ($processedPrices as $mp) {
                $vName = $mp->variety?->name ?? 'Standard Grade';
                if (!isset($extractedVarieties[$vName])) {
                    $extractedVarieties[$vName] = [
                        'name' => $vName,
                        'price' => (float) ($mp->modal_price ?: $mp->max_price ?: 0),
                        'market' => $mp->market?->name ?? 'APMC Mandi',
                        'date' => $mp->price_date ? $mp->price_date->format('d M') : now()->format('d M'),
                    ];
                }
            }

            // 3. Existing mappings for this crop and source
            $mappings = CropSourceMapping::with('variety')
                ->where('crop_id', $crop->id)
                ->where('data_source_id', $dataSource->id)
                ->get()
                ->keyBy('source_variety_name');

            foreach ($mappings as $mapName => $mapping) {
                if (!isset($extractedVarieties[$mapName])) {
                    $extractedVarieties[$mapName] = [
                        'name' => $mapName,
                        'price' => 0,
                        'market' => 'Configured Feed',
                        'date' => now()->format('d M'),
                    ];
                }
            }

            // 4. If still empty, inspect health check sample payload
            if (empty($extractedVarieties)) {
                $health = $provider->healthCheck();
                $sample = $health['sample_payload'] ?? null;
                if ($sample && is_array($sample)) {
                    $sVariety = trim($sample['source_variety'] ?? $sample['variety'] ?? $sample['Variety'] ?? $sample['grade'] ?? '');
                    if ($sVariety !== '') {
                        $extractedVarieties[$sVariety] = [
                            'name' => $sVariety,
                            'price' => (float) ($sample['modal_price'] ?? $sample['Modal_Price'] ?? 0),
                            'market' => $sample['source_market'] ?? $sample['market'] ?? 'APMC Mandi',
                            'date' => now()->format('d M'),
                        ];
                    }
                }
            }

            $results = [];
            foreach ($extractedVarieties as $name => $info) {
                $mapping = $mappings->get($name);
                $results[] = [
                    'raw_name' => $name,
                    'price' => (float) ($info['price'] ?? 0),
                    'market' => $info['market'] ?? 'APMC Mandi',
                    'date' => $info['date'] ?? now()->format('d M'),
                    'is_mapped' => (bool) $mapping,
                    'mapped_variety_id' => $mapping?->crop_variety_id,
                    'mapped_variety_name' => $mapping?->variety?->name,
                    'mapping_id' => $mapping?->id,
                ];
            }

            return response()->json([
                'ok' => true,
                'crop' => ['id' => $crop->id, 'name' => $crop->name],
                'source' => ['id' => $dataSource->id, 'name' => $dataSource->name, 'code' => $dataSource->code],
                'varieties' => array_values($results),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Inspect raw API feed response payload for this crop and selected data source.
     */
    public function inspectFeed(Request $request, Crop $crop): \Illuminate\Http\JsonResponse
    {
        $dataSourceId = $request->query('data_source_id');
        $dataSource = $dataSourceId ? DataSource::find($dataSourceId) : null;

        if (!$dataSource) {
            if ($crop->isCoffeeBoard()) {
                $dataSource = DataSource::where('code', 'coffee_board')->first();
            } elseif ($crop->isCoconutBoard()) {
                $dataSource = DataSource::where('code', 'coconut_board')->first();
            } else {
                $dataSource = DataSource::where('code', 'tss_sirsi')->where('is_active', true)->first()
                    ?? DataSource::where('code', 'ceda_agmarknet')->where('is_active', true)->first()
                    ?? DataSource::first();
            }
        }

        if (!$dataSource) {
            return response()->json(['ok' => false, 'error' => 'Data source not found.'], 404);
        }

        try {
            $provider = \App\Services\DataSources\DataSourceRegistry::make($dataSource);
            $health = $provider->healthCheck();

            // Fetch recent raw records or sample payload
            $recentRaw = \App\Models\MarketPriceRaw::where('data_source_id', $dataSource->id)
                ->latest()
                ->take(10)
                ->get()
                ->map(fn($r) => $r->payload)
                ->values();

            if ($recentRaw->isEmpty() && !empty($health['sample_payload'])) {
                $recentRaw = collect([$health['sample_payload']]);
            }

            return response()->json([
                'ok' => true,
                'source' => [
                    'id' => $dataSource->id,
                    'name' => $dataSource->name,
                    'code' => $dataSource->code,
                    'base_url' => $dataSource->base_url,
                ],
                'http_status' => $health['http_status'] ?? 200,
                'response_time_ms' => $health['response_time_ms'] ?? 0,
                'status' => $health['status'] ?? 'healthy',
                'sample_records' => $recentRaw,
                'detected_fields' => $health['detected_fields'] ?? [],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }
}

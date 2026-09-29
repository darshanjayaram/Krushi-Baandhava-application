<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MarketRequest;
use App\Models\AuditLog;
use App\Models\DataSource;
use App\Models\District;
use App\Models\Market;
use App\Models\MarketSourceMapping;
use App\Models\Taluk;
use App\Services\Ingestion\MarketPriceIngestionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MarketController extends Controller
{
    /**
     * Standard Karnataka APMC and Board Market Presets for quick auto-fill.
     */
    protected function getKarnatakaPresets(): array
    {
        return \App\Support\KarnatakaMandiDirectory::all();
    }

    /**
     * Auto-generate a clean, unique code for a mandi when none is provided.
     */
    protected function generateMarketCode(string $name, ?int $ignoreId = null): string
    {
        $clean = preg_replace('/\s*\(.*?\)/', '', $name);
        $clean = preg_replace('/\s+APMC/i', '', $clean);
        $base = 'KA_APMC_' . Str::upper(Str::slug(trim($clean), '_'));
        if (strlen($base) > 35) {
            $base = substr($base, 0, 35);
        }
        $code = $base;
        $counter = 1;
        while (Market::where('code', $code)->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $code = "{$base}_{$counter}";
            $counter++;
        }
        return $code;
    }

    /**
     * Display a listing of APMC markets.
     */
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $districtId = $request->query('district_id');

        $markets = Market::with(['district', 'taluk'])
            ->when($districtId, function ($query, $districtId) {
                $query->where('district_id', $districtId);
            })
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('name_kn', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $districts = District::where('is_active', true)->orderBy('name')->get();

        return view('admin.master.markets.index', compact('markets', 'districts', 'search', 'districtId'));
    }

    /**
     * Show the form for creating a new APMC market.
     */
    public function create(Request $request): View
    {
        $selectedDistrictId = $request->query('district_id');
        $districts = District::where('is_active', true)->orderBy('name')->get();
        $taluks = Taluk::where('is_active', true)
            ->when($selectedDistrictId, fn ($q) => $q->where('district_id', $selectedDistrictId))
            ->orderBy('name')
            ->get();
        $presets = $this->getKarnatakaPresets();

        return view('admin.master.markets.form', [
            'market' => new Market(['district_id' => $selectedDistrictId, 'market_type' => 'APMC', 'is_active' => true]),
            'districts' => $districts,
            'taluks' => $taluks,
            'presets' => $presets,
            'isEdit' => false,
        ]);
    }

    /**
     * Store a newly created market.
     */
    public function store(MarketRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        if (empty($data['code'])) {
            $data['code'] = $this->generateMarketCode($data['name']);
        }

        $market = Market::create($data);

        AuditLog::log('market.create', 'Market', $market->id, null, $market->toArray());

        return redirect()->route('admin.markets.index')
            ->with('success', "Mandi '{$market->name}' registered successfully with code [{$market->code}].");
    }

    /**
     * Show the form for editing the market.
     */
    public function edit(Market $market): View
    {
        $districts = District::where('is_active', true)->orderBy('name')->get();
        $taluks = Taluk::where('district_id', $market->district_id)->orderBy('name')->get();
        $dataSources = DataSource::where('is_active', true)->orderBy('name')->get();
        $presets = $this->getKarnatakaPresets();

        $market->load(['sourceMappings.dataSource']);

        return view('admin.master.markets.form', [
            'market' => $market,
            'districts' => $districts,
            'taluks' => $taluks,
            'dataSources' => $dataSources,
            'presets' => $presets,
            'isEdit' => true,
        ]);
    }

    /**
     * Update the specified market.
     */
    public function update(MarketRequest $request, Market $market): RedirectResponse
    {
        $oldValues = $market->toArray();
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        if (empty($data['code'])) {
            $data['code'] = $this->generateMarketCode($data['name'], $market->id);
        }

        $market->update($data);

        AuditLog::log('market.update', 'Market', $market->id, $oldValues, $market->toArray());

        return redirect()->route('admin.markets.index')
            ->with('success', "Mandi '{$market->name}' updated successfully.");
    }

    /**
     * Toggle the active status of a market.
     */
    public function toggleStatus(Market $market): RedirectResponse
    {
        $market->is_active = !$market->is_active;
        $market->save();

        AuditLog::log('market.status_toggle', 'Market', $market->id, null, ['is_active' => $market->is_active]);

        $statusLabel = $market->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Mandi '{$market->name}' was {$statusLabel}.");
    }

    /**
     * Add a raw feed alias mapping directly to this market.
     */
    public function addAlias(Request $request, Market $market, MarketPriceIngestionService $service): RedirectResponse
    {
        $validated = $request->validate([
            'source_market_name' => ['required', 'string', 'max:255'],
            'data_source_id' => ['nullable', 'exists:data_sources,id'],
            'source_district_name' => ['nullable', 'string', 'max:150'],
        ]);

        $dataSourceId = !empty($validated['data_source_id'])
            ? (int) $validated['data_source_id']
            : (DataSource::where('code', 'data_gov_mandi')->value('id') ?? DataSource::first()->id);

        $mapping = MarketSourceMapping::updateOrCreate(
            [
                'data_source_id' => $dataSourceId,
                'source_market_name' => trim($validated['source_market_name']),
                'source_district_name' => !empty($validated['source_district_name']) ? trim($validated['source_district_name']) : null,
            ],
            [
                'market_id' => $market->id,
                'is_verified' => true,
            ]
        );

        AuditLog::log('market.alias_add', 'MarketSourceMapping', $mapping->id, null, $mapping->toArray());

        // Auto-reprocess affected rejected records
        $reprocessResults = $service->reprocessBatch($dataSourceId, $validated['source_market_name']);

        $msg = "Feed alias '{$validated['source_market_name']}' successfully mapped to {$market->name}.";
        if ($reprocessResults['processed'] > 0) {
            $msg .= " Auto-reprocessed {$reprocessResults['processed']} existing raw price records into active prices!";
        }

        return back()->with('success', $msg);
    }

    /**
     * Remove an alias mapping from this market.
     */
    public function removeAlias(Market $market, MarketSourceMapping $mapping): RedirectResponse
    {
        if ($mapping->market_id !== $market->id) {
            abort(403, 'Unauthorized alias modification.');
        }

        $aliasName = $mapping->source_market_name;
        $mapping->delete();

        AuditLog::log('market.alias_remove', 'MarketSourceMapping', $mapping->id, null, ['deleted_alias' => $aliasName]);

        return back()->with('success', "Alias '{$aliasName}' removed from {$market->name}.");
    }
}

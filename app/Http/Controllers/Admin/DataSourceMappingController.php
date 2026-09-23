<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Crop;
use App\Models\CropSourceMapping;
use App\Models\DataSource;
use App\Models\DataSourceMapping;
use App\Models\Market;
use App\Models\MarketSourceMapping;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DataSourceMappingController extends Controller
{
    public function index(DataSource $datasource): View
    {
        $datasource->load(['mappings', 'cropMappings.crop', 'marketMappings.market']);
        $allCrops = Crop::where('is_active', true)->orderBy('name')->get();
        $allMarkets = Market::where('is_active', true)->orderBy('name')->get();

        return view('admin.datasources.mappings', compact('datasource', 'allCrops', 'allMarkets'));
    }

    public function storeFieldMapping(Request $request, DataSource $datasource): RedirectResponse
    {
        $validated = $request->validate([
            'source_field' => ['required', 'string', 'max:100'],
            'target_field' => ['required', 'string', 'max:100'],
            'transformation_rule' => ['nullable', 'string', 'max:100'],
            'is_required' => ['sometimes', 'boolean'],
            'default_value' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['is_required'] = $request->boolean('is_required', false);
        $validated['data_source_id'] = $datasource->id;

        $mapping = DataSourceMapping::create($validated);

        AuditLog::log('create', 'DataSourceMapping', $mapping->id, null, $mapping->toArray());

        return back()->with('success', "Field mapping '{$mapping->source_field} -> {$mapping->target_field}' added.");
    }

    public function destroyFieldMapping(DataSourceMapping $mapping): RedirectResponse
    {
        $old = $mapping->toArray();
        $mapping->delete();

        AuditLog::log('delete', 'DataSourceMapping', $old['id'], $old, null);

        return back()->with('success', "Field mapping removed.");
    }

    public function storeCropAlias(Request $request, DataSource $datasource): RedirectResponse
    {
        $validated = $request->validate([
            'source_crop_name' => ['required', 'string', 'max:150'],
            'source_variety_name' => ['nullable', 'string', 'max:150'],
            'crop_id' => ['required', 'exists:crops,id'],
            'confidence_score' => ['nullable', 'numeric', 'min:0', 'max:1'],
        ]);

        $validated['data_source_id'] = $datasource->id;
        $validated['is_verified'] = true;
        $validated['confidence_score'] = $validated['confidence_score'] ?? 1.00;

        $alias = CropSourceMapping::updateOrCreate(
            [
                'data_source_id' => $datasource->id,
                'source_crop_name' => $validated['source_crop_name'],
                'source_variety_name' => $validated['source_variety_name'] ?? null,
            ],
            $validated
        );

        AuditLog::log('save_alias', 'CropSourceMapping', $alias->id, null, $alias->toArray());

        return back()->with('success', "Crop alias mapping saved.");
    }

    public function storeMarketAlias(Request $request, DataSource $datasource): RedirectResponse
    {
        $validated = $request->validate([
            'source_market_name' => ['required', 'string', 'max:150'],
            'source_district_name' => ['nullable', 'string', 'max:150'],
            'market_id' => ['required', 'exists:markets,id'],
            'confidence_score' => ['nullable', 'numeric', 'min:0', 'max:1'],
        ]);

        $validated['data_source_id'] = $datasource->id;
        $validated['is_verified'] = true;
        $validated['confidence_score'] = $validated['confidence_score'] ?? 1.00;

        $alias = MarketSourceMapping::updateOrCreate(
            [
                'data_source_id' => $datasource->id,
                'source_market_name' => $validated['source_market_name'],
                'source_district_name' => $validated['source_district_name'] ?? null,
            ],
            $validated
        );

        AuditLog::log('save_alias', 'MarketSourceMapping', $alias->id, null, $alias->toArray());

        return back()->with('success', "Market alias mapping saved.");
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MarketRequest;
use App\Models\AuditLog;
use App\Models\District;
use App\Models\Market;
use App\Models\Taluk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketController extends Controller
{
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

        return view('admin.master.markets.form', [
            'market' => new Market(['district_id' => $selectedDistrictId, 'market_type' => 'APMC', 'is_active' => true]),
            'districts' => $districts,
            'taluks' => $taluks,
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

        $market = Market::create($data);

        AuditLog::log('market.create', 'Market', $market->id, null, $market->toArray());

        return redirect()->route('admin.markets.index')
            ->with('success', "Mandi '{$market->name}' registered successfully.");
    }

    /**
     * Show the form for editing the market.
     */
    public function edit(Market $market): View
    {
        $districts = District::where('is_active', true)->orderBy('name')->get();
        $taluks = Taluk::where('district_id', $market->district_id)->orderBy('name')->get();

        return view('admin.master.markets.form', [
            'market' => $market,
            'districts' => $districts,
            'taluks' => $taluks,
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
}

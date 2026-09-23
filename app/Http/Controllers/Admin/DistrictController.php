<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DistrictRequest;
use App\Models\AuditLog;
use App\Models\District;
use App\Models\State;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DistrictController extends Controller
{
    /**
     * Display a listing of districts.
     */
    public function index(Request $request): View
    {
        $search = $request->query('search');

        $districts = District::with(['state'])
            ->withCount(['taluks', 'markets'])
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('name_kn', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.master.districts.index', compact('districts', 'search'));
    }

    /**
     * Show the form for creating a new district.
     */
    public function create(): View
    {
        $states = State::where('is_active', true)->orderBy('name')->get();

        return view('admin.master.districts.form', [
            'district' => new District(['state_id' => $states->first()?->id, 'is_active' => true]),
            'states' => $states,
            'isEdit' => false,
        ]);
    }

    /**
     * Store a newly created district in storage.
     */
    public function store(DistrictRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        $district = District::create($data);

        AuditLog::log('district.create', 'District', $district->id, null, $district->toArray());

        return redirect()->route('admin.districts.index')
            ->with('success', "District '{$district->name}' was created successfully.");
    }

    /**
     * Show the form for editing the specified district.
     */
    public function edit(District $district): View
    {
        $district->load(['taluks', 'markets']);
        $states = State::where('is_active', true)->orderBy('name')->get();

        return view('admin.master.districts.form', [
            'district' => $district,
            'states' => $states,
            'isEdit' => true,
        ]);
    }

    /**
     * Update the specified district in storage.
     */
    public function update(DistrictRequest $request, District $district): RedirectResponse
    {
        $oldValues = $district->toArray();
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        $district->update($data);

        AuditLog::log('district.update', 'District', $district->id, $oldValues, $district->toArray());

        return redirect()->route('admin.districts.index')
            ->with('success', "District '{$district->name}' was updated successfully.");
    }

    /**
     * Toggle the active status of a district.
     */
    public function toggleStatus(District $district): RedirectResponse
    {
        $district->is_active = !$district->is_active;
        $district->save();

        AuditLog::log('district.status_toggle', 'District', $district->id, null, ['is_active' => $district->is_active]);

        $statusLabel = $district->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "District '{$district->name}' was {$statusLabel}.");
    }
}

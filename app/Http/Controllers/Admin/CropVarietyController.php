<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CropVarietyRequest;
use App\Models\AuditLog;
use App\Models\CropVariety;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class CropVarietyController extends Controller
{
    /**
     * Store a newly created crop variety.
     */
    public function store(CropVarietyRequest $request): RedirectResponse
    {
        $data = $request->validated();
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }
        $data['is_active'] = $request->boolean('is_active', true);

        $variety = CropVariety::create($data);

        AuditLog::log('crop_variety.create', 'CropVariety', $variety->id, null, $variety->toArray());

        return back()->with('success', "Variety '{$variety->name}' added successfully.");
    }

    /**
     * Update the specified crop variety.
     */
    public function update(CropVarietyRequest $request, CropVariety $variety): RedirectResponse
    {
        $oldValues = $variety->toArray();
        $data = $request->validated();
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }
        $data['is_active'] = $request->boolean('is_active', true);

        $variety->update($data);

        AuditLog::log('crop_variety.update', 'CropVariety', $variety->id, $oldValues, $variety->toArray());

        return back()->with('success', "Variety '{$variety->name}' updated successfully.");
    }

    /**
     * Remove the specified crop variety.
     */
    public function destroy(CropVariety $variety): RedirectResponse
    {
        $name = $variety->name;
        $oldValues = $variety->toArray();

        $variety->delete();

        AuditLog::log('crop_variety.delete', 'CropVariety', $variety->id, $oldValues, null);

        return back()->with('success', "Variety '{$name}' was removed.");
    }
}

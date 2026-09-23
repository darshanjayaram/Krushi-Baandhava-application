<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TalukRequest;
use App\Models\AuditLog;
use App\Models\Taluk;
use Illuminate\Http\RedirectResponse;

class TalukController extends Controller
{
    /**
     * Store a newly created taluk.
     */
    public function store(TalukRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $taluk = Taluk::create($data);

        AuditLog::log('taluk.create', 'Taluk', $taluk->id, null, $taluk->toArray());

        return back()->with('success', "Taluk '{$taluk->name}' added successfully.");
    }

    /**
     * Update the specified taluk.
     */
    public function update(TalukRequest $request, Taluk $taluk): RedirectResponse
    {
        $oldValues = $taluk->toArray();
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $taluk->update($data);

        AuditLog::log('taluk.update', 'Taluk', $taluk->id, $oldValues, $taluk->toArray());

        return back()->with('success', "Taluk '{$taluk->name}' updated successfully.");
    }

    /**
     * Remove the specified taluk.
     */
    public function destroy(Taluk $taluk): RedirectResponse
    {
        $name = $taluk->name;
        $oldValues = $taluk->toArray();

        // Guard against deleting taluks with markets attached
        if ($taluk->markets()->exists()) {
            return back()->with('error', "Cannot delete taluk '{$name}' because active markets are associated with it. Please reassign or delete the markets first.");
        }

        $taluk->delete();

        AuditLog::log('taluk.delete', 'Taluk', $taluk->id, $oldValues, null);

        return back()->with('success', "Taluk '{$name}' was deleted.");
    }
}

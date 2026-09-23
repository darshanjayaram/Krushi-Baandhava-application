<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\FeatureFlag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class FeatureFlagController extends Controller
{
    /**
     * Display a listing of all platform feature flags.
     */
    public function index(Request $request): View
    {
        $query = FeatureFlag::query();

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('key', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'enabled') {
                $query->where('is_enabled', true);
            } elseif ($request->status === 'disabled') {
                $query->where('is_enabled', false);
            }
        }

        $flags = $query->orderBy('name')->get();
        $totalCount = FeatureFlag::count();
        $enabledCount = FeatureFlag::where('is_enabled', true)->count();
        $disabledCount = $totalCount - $enabledCount;

        return view('admin.feature_flags.index', compact('flags', 'totalCount', 'enabledCount', 'disabledCount'));
    }

    /**
     * Toggle the active status of a feature flag.
     */
    public function toggle(FeatureFlag $featureFlag, Request $request): RedirectResponse|JsonResponse
    {
        $oldState = (bool) $featureFlag->is_enabled;
        $newState = !$oldState;

        $featureFlag->update(['is_enabled' => $newState]);

        // Invalidate cache immediately
        Cache::forget("feature_flag_{$featureFlag->key}");

        // Audit log
        AuditLog::log(
            'feature_flag.toggle',
            'FeatureFlag',
            $featureFlag->id,
            ['is_enabled' => $oldState],
            ['is_enabled' => $newState]
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_enabled' => $newState,
                'message' => "Feature flag '{$featureFlag->name}' is now " . ($newState ? 'enabled' : 'disabled') . '.',
            ]);
        }

        return redirect()->back()->with('success', "Feature flag '{$featureFlag->name}' was " . ($newState ? 'enabled' : 'disabled') . ' successfully.');
    }

    /**
     * Update feature flag details.
     */
    public function update(Request $request, FeatureFlag $featureFlag): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_enabled' => ['nullable', 'boolean'],
        ]);

        $oldValues = $featureFlag->only(['name', 'description', 'is_enabled']);
        $validated['is_enabled'] = $request->boolean('is_enabled');

        $featureFlag->update($validated);

        // Invalidate cache
        Cache::forget("feature_flag_{$featureFlag->key}");

        // Audit log
        AuditLog::log(
            'feature_flag.update',
            'FeatureFlag',
            $featureFlag->id,
            $oldValues,
            $featureFlag->only(['name', 'description', 'is_enabled'])
        );

        return redirect()->route('admin.feature-flags.index')
            ->with('success', "Feature flag '{$featureFlag->name}' updated successfully.");
    }
}

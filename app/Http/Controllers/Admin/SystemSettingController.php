<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\District;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class SystemSettingController extends Controller
{
    /**
     * Display system configuration settings grouped by domain.
     */
    public function index(Request $request): View
    {
        $allSettings = SystemSetting::orderBy('id')->get();
        $groupedSettings = $allSettings->groupBy('group');

        $activeTab = $request->query('tab', 'general');
        if (!$groupedSettings->has($activeTab) && $groupedSettings->isNotEmpty()) {
            $activeTab = $groupedSettings->keys()->first();
        }

        $districts = District::orderBy('name')->get();

        return view('admin.settings.index', compact('groupedSettings', 'activeTab', 'districts'));
    }

    /**
     * Update system configuration settings in bulk or by group.
     */
    public function update(Request $request): RedirectResponse
    {
        $inputSettings = $request->input('settings', []);
        $updatedKeys = [];
        $oldValues = [];
        $newValues = [];

        foreach ($inputSettings as $key => $val) {
            $setting = SystemSetting::where('key', $key)->first();
            if (!$setting) {
                continue;
            }

            $oldValues[$key] = $setting->value;

            // Type conversion
            if ($setting->type === 'boolean') {
                $formattedValue = $val ? 'true' : 'false';
            } elseif ($setting->type === 'integer') {
                $formattedValue = (string) intval($val);
            } elseif ($setting->type === 'json' && is_array($val)) {
                $formattedValue = json_encode($val);
            } else {
                $formattedValue = (string) $val;
            }

            $setting->update(['value' => $formattedValue]);
            Cache::forget("system_setting_{$key}");

            $newValues[$key] = $formattedValue;
            $updatedKeys[] = $key;
        }

        if (!empty($updatedKeys)) {
            AuditLog::log(
                'settings.bulk_update',
                'SystemSetting',
                null,
                $oldValues,
                $newValues
            );
        }

        $tab = $request->input('tab', 'general');

        return redirect()->route('admin.settings.index', ['tab' => $tab])
            ->with('success', 'System configuration settings updated successfully.');
    }
}

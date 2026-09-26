<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Crop;
use App\Models\District;
use App\Models\ForecastRun;
use App\Models\PriceForecast;
use App\Models\SyncLog;
use App\Models\SystemSetting;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
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
        $totalForecasts = PriceForecast::count();
        $totalCrops = Crop::where('is_active', true)->count();
        $lastForecastRun = ForecastRun::latest()->first();
        $totalSyncLogs = SyncLog::count();

        return view('admin.settings.index', compact(
            'groupedSettings',
            'activeTab',
            'districts',
            'totalForecasts',
            'totalCrops',
            'lastForecastRun',
            'totalSyncLogs'
        ));
    }

    /**
     * Update system configuration settings in bulk or by group.
     */
    public function update(Request $request): RedirectResponse
    {
        $destinationPath = public_path('uploads/branding');
        if (!File::exists($destinationPath)) {
            File::makeDirectory($destinationPath, 0755, true);
        }

        // Handle Application Logo Upload
        if ($request->hasFile('app_logo')) {
            $request->validate([
                'app_logo' => 'image|mimes:png,jpg,jpeg,svg,webp|max:2048',
            ]);

            $file = $request->file('app_logo');
            $fileName = 'app_logo_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move($destinationPath, $fileName);
            $logoUrl = '/uploads/branding/' . $fileName;

            $oldLogo = SystemSetting::get('app_logo', '/icons/icon-192.svg');
            SystemSetting::set('app_logo', $logoUrl, 'string', 'general', 'Application branding logo path.');
            Cache::forget('system_setting_app_logo');

            AuditLog::log('settings.update_logo', 'SystemSetting', null, ['app_logo' => $oldLogo], ['app_logo' => $logoUrl]);
        } elseif ($request->boolean('remove_logo')) {
            $oldLogo = SystemSetting::get('app_logo', '/icons/icon-192.svg');
            SystemSetting::set('app_logo', '/icons/icon-192.svg', 'string', 'general', 'Application branding logo path.');
            Cache::forget('system_setting_app_logo');

            AuditLog::log('settings.reset_logo', 'SystemSetting', null, ['app_logo' => $oldLogo], ['app_logo' => '/icons/icon-192.svg']);
        }

        // Handle PWA Mobile Icon Upload
        if ($request->hasFile('pwa_icon')) {
            $request->validate([
                'pwa_icon' => 'image|mimes:png,jpg,jpeg,svg,webp|max:3072',
            ]);

            $file = $request->file('pwa_icon');
            $fileName = 'pwa_icon_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move($destinationPath, $fileName);
            $iconUrl = '/uploads/branding/' . $fileName;

            $oldIcon = SystemSetting::get('pwa_icon', '/icons/icon-512.svg');
            SystemSetting::set('pwa_icon', $iconUrl, 'string', 'pwa', 'Application 512x512 mobile installation home icon.');
            Cache::forget('system_setting_pwa_icon');

            AuditLog::log('settings.update_pwa_icon', 'SystemSetting', null, ['pwa_icon' => $oldIcon], ['pwa_icon' => $iconUrl]);
        }

        $inputSettings = $request->input('settings', []);
        $updatedKeys = [];
        $oldValues = [];
        $newValues = [];

        foreach ($inputSettings as $key => $val) {
            $setting = SystemSetting::where('key', $key)->first();
            if (!$setting) {
                $setting = SystemSetting::create([
                    'key' => $key,
                    'value' => (string) $val,
                    'type' => 'string',
                    'group' => $tab,
                    'description' => ucwords(str_replace('_', ' ', $key)),
                ]);
            }

            $oldValues[$key] = $setting->value;

            // Accurate Type Conversion
            if ($setting->type === 'boolean') {
                $formattedValue = filter_var($val, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
            } elseif ($setting->type === 'integer') {
                $formattedValue = (string) intval($val);
            } elseif ($setting->type === 'json') {
                if (is_array($val)) {
                    $formattedValue = json_encode(array_values(array_map('intval', $val)));
                } else {
                    $formattedValue = (string) $val;
                }
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

    /**
     * Clear all application caches (framework optimize, views, routes, config, memory stores).
     */
    public function clearCache(): RedirectResponse
    {
        Artisan::call('optimize:clear');
        Cache::flush();

        AuditLog::log('system.clear_cache', 'System', null, [], ['status' => 'cleared']);

        return redirect()->route('admin.settings.index', ['tab' => 'maintenance'])
            ->with('success', 'All application caches (config, routes, views, memory cache) cleared successfully.');
    }

    /**
     * Safely run pending migrations and synchronize system seeders.
     */
    public function updateDatabase(): RedirectResponse
    {
        Artisan::call('migrate', ['--force' => true]);
        Artisan::call('db:seed', ['--class' => 'SystemSettingSeeder', '--force' => true]);

        AuditLog::log('system.update_database', 'System', null, [], ['status' => 'migrated']);

        return redirect()->route('admin.settings.index', ['tab' => 'maintenance'])
            ->with('success', 'Database schema migrated and master seeds updated successfully.');
    }

    /**
     * Prune stale historical logs and expired raw payloads older than 30 days.
     */
    public function pruneData(): RedirectResponse
    {
        $cutoff = Carbon::now()->subDays(30);
        $deletedSyncLogs = SyncLog::where('created_at', '<', $cutoff)->delete();

        AuditLog::log('system.prune_data', 'System', null, [], ['deleted_sync_logs' => $deletedSyncLogs]);

        return redirect()->route('admin.settings.index', ['tab' => 'maintenance'])
            ->with('success', "Stale system logs pruned successfully ({$deletedSyncLogs} expired logs removed).");
    }

    /**
     * Trigger on-demand batch price forecasting across all Karnataka crops.
     */
    public function triggerForecasting(): RedirectResponse
    {
        Artisan::call('krushi:generate-forecasts');

        AuditLog::log('system.trigger_forecasting', 'System', null, [], ['status' => 'completed']);

        return redirect()->route('admin.settings.index', ['tab' => 'forecasting'])
            ->with('success', 'Statistical price forecasting batch execution completed across all active Karnataka crops.');
    }
}

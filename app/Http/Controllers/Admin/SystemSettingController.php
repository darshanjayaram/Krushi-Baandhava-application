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
use Illuminate\Http\JsonResponse;
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
        $managedGroups = [
            'general',
            'pwa',
            'weather',
            'maps',
            'data_sources',
            'forecasting',
            'best_months_to_sell',
            'performance',
            'maintenance',
            'localization',
        ];

        // Auto-heal and normalize domain groups for deployment environments
        SystemSetting::where('key', 'seasonality_years')
            ->where('group', '!=', 'best_months_to_sell')
            ->update([
                'group' => 'best_months_to_sell',
                'type' => 'integer',
                'description' => 'Number of historical years evaluated to compute monthly seasonal price indices and peak harvest selling months.'
            ]);

        SystemSetting::firstOrCreate(
            ['key' => 'seasonality_years'],
            [
                'value' => '5',
                'type' => 'integer',
                'group' => 'best_months_to_sell',
                'description' => 'Number of historical years evaluated to compute monthly seasonal price indices and peak harvest selling months.'
            ]
        );

        SystemSetting::where('key', 'forecast_minimum_observations')
            ->where('group', '!=', 'forecasting')
            ->update([
                'group' => 'forecasting',
                'type' => 'integer',
                'description' => 'Minimum historical price observations required before producing a forecast.'
            ]);

        SystemSetting::firstOrCreate(
            ['key' => 'forecast_minimum_observations'],
            [
                'value' => '30',
                'type' => 'integer',
                'group' => 'forecasting',
                'description' => 'Minimum historical price observations required before producing a forecast.'
            ]
        );

        SystemSetting::where('key', 'forecast_confidence_threshold')
            ->where('group', '!=', 'forecasting')
            ->update([
                'group' => 'forecasting',
                'type' => 'integer',
                'description' => 'Minimum confidence percentage required to display forecast on farmer mobile screen.'
            ]);

        SystemSetting::firstOrCreate(
            ['key' => 'forecast_confidence_threshold'],
            [
                'value' => '70',
                'type' => 'integer',
                'group' => 'forecasting',
                'description' => 'Minimum confidence percentage required to display forecast on farmer mobile screen.'
            ]
        );

        $allSettings = SystemSetting::whereIn('group', $managedGroups)
            ->whereNotIn('key', ['crop_price_staleness_days', 'category_price_staleness_days'])
            ->orderBy('id')
            ->get();

        $groupedSettings = collect($managedGroups)->mapWithKeys(function ($group) use ($allSettings) {
            return [$group => $allSettings->where('group', $group)->values()];
        })->filter(function ($items) {
            return $items->isNotEmpty();
        });

        $activeTab = $request->query('tab', 'general');
        if (!$groupedSettings->has($activeTab) && $groupedSettings->isNotEmpty()) {
            $activeTab = $groupedSettings->keys()->first();
        }

        $districts = District::orderBy('name')->get();
        $totalForecasts = PriceForecast::count();
        $totalCrops = Crop::where('is_active', true)->count();
        $lastForecastRun = ForecastRun::latest()->first();
        $totalSyncLogs = SyncLog::count();
        $cropCategories = \App\Models\CropCategory::where('is_active', true)->orderBy('id')->get();

        return view('admin.settings.index', compact(
            'groupedSettings',
            'activeTab',
            'districts',
            'cropCategories',
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

            // Automatically sync standard PWA installation icons from the new application logo
            $this->syncPwaIconsFromLogo($logoUrl);

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

        $tab = $request->input('tab', 'general');
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
                    $isAssoc = !empty($val) && is_string(array_key_first($val));
                    if ($isAssoc) {
                        $formattedValue = json_encode(array_map('intval', $val));
                    } else {
                        $formattedValue = json_encode(array_values(array_map('intval', $val)));
                    }
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

        // Automatically synchronize the PWA manifest.json on disk
        app(\App\Services\Pwa\PwaManifestService::class)->syncDiskManifest();

        $tab = $request->input('tab', 'general');

        return redirect()->route('admin.settings.index', ['tab' => $tab])
            ->with('success', 'System configuration settings updated successfully.');
    }

    /**
     * Clear all application caches (framework optimize, views, routes, config, memory stores).
     */
    public function clearCache(Request $request): RedirectResponse|JsonResponse
    {
        $existingHeartbeat = Cache::get('scheduler_last_heartbeat') 
            ?? SystemSetting::get('scheduler_last_heartbeat');

        Artisan::call('optimize:clear');
        Cache::flush();

        // Self-Healing Guard: Restore scheduler heartbeat so clearing cache does not cause the dashboard to show "Cron Stopped"
        if ($existingHeartbeat) {
            Cache::forever('scheduler_last_heartbeat', $existingHeartbeat);
            try {
                SystemSetting::set('scheduler_last_heartbeat', (string) $existingHeartbeat, 'string', 'system', 'Timestamp of last scheduler execution');
            } catch (\Throwable $e) {}
        }

        AuditLog::log('system.clear_cache', 'System', null, [], ['status' => 'cleared']);

        $message = 'All application caches (config, routes, views, memory cache) cleared successfully.';

        if ($request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'ok' => true,
                'message' => $message,
            ]);
        }

        $tab = $request->input('tab', 'maintenance');

        return redirect()->route('admin.settings.index', ['tab' => $tab])
            ->with('success', $message);
    }

    /**
     * Compile and cache configuration, routes, and views for production maximum performance.
     */
    public function optimizeApp(Request $request): RedirectResponse|JsonResponse
    {
        Artisan::call('optimize');

        AuditLog::log('system.optimize_app', 'System', null, [], ['status' => 'optimized']);

        $message = 'Application optimized for production! Config, routes, and views have been pre-compiled for maximum performance.';

        if ($request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'ok' => true,
                'message' => $message,
            ]);
        }

        $tab = $request->input('tab', 'maintenance');

        return redirect()->route('admin.settings.index', ['tab' => $tab])
            ->with('success', $message);
    }

    /**
     * Safely run pending migrations and synchronize system seeders.
     */
    public function updateDatabase(Request $request): RedirectResponse|JsonResponse
    {
        Artisan::call('migrate', ['--force' => true]);
        Artisan::call('db:seed', ['--class' => 'SystemSettingSeeder', '--force' => true]);

        AuditLog::log('system.update_database', 'System', null, [], ['status' => 'migrated']);

        $message = 'Database schema migrated and master seeds updated successfully.';

        if ($request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'ok' => true,
                'message' => $message,
            ]);
        }

        $tab = $request->input('tab', 'maintenance');

        return redirect()->route('admin.settings.index', ['tab' => $tab])
            ->with('success', $message);
    }

    /**
     * Prune stale historical logs and expired raw payloads older than 30 days.
     */
    public function pruneData(Request $request): RedirectResponse|JsonResponse
    {
        $cutoff = Carbon::now()->subDays(30);
        $deletedSyncLogs = SyncLog::where('created_at', '<', $cutoff)->delete();

        AuditLog::log('system.prune_data', 'System', null, [], ['deleted_sync_logs' => $deletedSyncLogs]);

        $message = "Stale system logs pruned successfully ({$deletedSyncLogs} expired logs removed).";

        if ($request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'ok' => true,
                'message' => $message,
            ]);
        }

        return redirect()->route('admin.settings.index', ['tab' => 'maintenance'])
            ->with('success', $message);
    }

    /**
     * Trigger on-demand batch price forecasting across all Karnataka crops.
     */
    public function triggerForecasting(Request $request): RedirectResponse|JsonResponse
    {
        Artisan::call('krushi:generate-forecasts');

        AuditLog::log('system.trigger_forecasting', 'System', null, [], ['status' => 'completed']);

        $message = 'Statistical price forecasting batch execution completed across all active Karnataka crops.';

        if ($request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'ok' => true,
                'message' => $message,
            ]);
        }

        return redirect()->route('admin.settings.index', ['tab' => 'forecasting'])
            ->with('success', $message);
    }

    /**
     * Automatically regenerate high-resolution standard PNG icons for PWA installation
     * matching the uploaded application branding logo.
     */
    protected function syncPwaIconsFromLogo(string $logoPath): void
    {
        $fullPath = public_path(ltrim($logoPath, '/'));
        if (!file_exists($fullPath)) {
            return;
        }

        try {
            $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
            $src = match($ext) {
                'png' => @imagecreatefrompng($fullPath),
                'jpg', 'jpeg' => @imagecreatefromjpeg($fullPath),
                'webp' => @imagecreatefromwebp($fullPath),
                default => null,
            };

            if (!$src) {
                return;
            }

            $w = imagesx($src);
            $h = imagesy($src);

            foreach ([192, 512] as $size) {
                $dest = imagecreatetruecolor($size, $size);
                imagealphablending($dest, false);
                imagesavealpha($dest, true);
                $transparent = imagecolorallocatealpha($dest, 255, 255, 255, 127);
                imagefilledrectangle($dest, 0, 0, $size, $size, $transparent);
                imagecopyresampled($dest, $src, 0, 0, 0, 0, $size, $size, $w, $h);
                $target = public_path('icons/icon-' . $size . '.png');
                imagepng($dest, $target, 9);
                imagedestroy($dest);
            }

            imagedestroy($src);
        } catch (\Throwable $e) {
            \Log::warning('PWA icon generation from logo skipped: ' . $e->getMessage());
        }
    }
}

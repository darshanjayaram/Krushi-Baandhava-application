<?php

namespace App\Console\Commands;

use App\Models\Crop;
use App\Models\District;
use App\Models\Market;
use App\Models\MarketPrice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ProductionHealthCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:health-check {--json : Output report in JSON format} {--fail-fast : Exit immediately on first error}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform production health, environment, master data, and permissions checks';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $checks = [];
        $hasErrors = false;

        // 1. PHP Version Check
        $phpVersion = PHP_VERSION;
        $phpOk = version_compare($phpVersion, '8.2.0', '>=');
        $checks[] = [
            'category' => 'Runtime',
            'check' => 'PHP Version (>= 8.2.0)',
            'status' => $phpOk ? 'PASS' : 'FAIL',
            'details' => "Detected PHP {$phpVersion}",
        ];
        if (!$phpOk) $hasErrors = true;

        // 2. Required & Recommended PHP Extensions
        $criticalExtensions = ['pdo_mysql', 'curl', 'mbstring', 'fileinfo', 'openssl', 'xml'];
        $recommendedExtensions = ['intl', 'gd', 'bcmath', 'zip'];

        $missingCritical = [];
        foreach ($criticalExtensions as $ext) {
            if (!extension_loaded($ext)) {
                $missingCritical[] = $ext;
            }
        }

        $missingRecommended = [];
        foreach ($recommendedExtensions as $ext) {
            if (!extension_loaded($ext)) {
                $missingRecommended[] = $ext;
            }
        }

        $extStatus = 'PASS';
        $extDetails = 'All core & recommended extensions loaded';

        if (!empty($missingCritical)) {
            $extStatus = 'FAIL';
            $extDetails = 'Missing critical: ' . implode(', ', $missingCritical);
            $hasErrors = true;
        } elseif (!empty($missingRecommended)) {
            $extStatus = 'WARN';
            $extDetails = 'Core OK. Recommended missing on cPanel: ' . implode(', ', $missingRecommended);
        }

        $checks[] = [
            'category' => 'Runtime',
            'check' => 'PHP Extensions',
            'status' => $extStatus,
            'details' => $extDetails,
        ];

        // 3. Application Key
        $hasKey = !empty(config('app.key'));
        $checks[] = [
            'category' => 'Security',
            'check' => 'Application Key',
            'status' => $hasKey ? 'PASS' : 'FAIL',
            'details' => $hasKey ? 'Configured' : 'Missing APP_KEY in .env',
        ];
        if (!$hasKey) $hasErrors = true;

        // 4. Debug Mode Check for Production
        $isProd = app()->environment('production');
        $isDebug = (bool) config('app.debug');
        $debugOk = !$isProd || !$isDebug;
        $checks[] = [
            'category' => 'Security',
            'check' => 'Debug Mode in Production',
            'status' => $debugOk ? 'PASS' : 'WARN',
            'details' => "Env: " . app()->environment() . ", APP_DEBUG=" . ($isDebug ? 'true' : 'false'),
        ];

        // 5. Database Connection & Migrations
        $dbOk = false;
        $dbDetails = '';
        try {
            DB::connection()->getPdo();
            $dbOk = true;
            $tableCount = count(DB::select('SHOW TABLES'));
            $dbDetails = "Connected (" . config('database.default') . ", {$tableCount} tables)";
        } catch (\Throwable $e) {
            $dbDetails = 'Connection failed: ' . $e->getMessage();
            $hasErrors = true;
        }
        $checks[] = [
            'category' => 'Database',
            'check' => 'Database Connectivity',
            'status' => $dbOk ? 'PASS' : 'FAIL',
            'details' => $dbDetails,
        ];

        // 6. Master Data Presence (Karnataka scope)
        $masterDataOk = false;
        $masterDetails = '';
        if ($dbOk) {
            try {
                $districtCount = District::count();
                $marketCount = Market::karnataka()->count();
                $cropCount = Crop::where('is_active', true)->count();
                $priceCount = MarketPrice::karnataka()->count();

                $masterDataOk = ($districtCount > 0 && $marketCount > 0 && $cropCount > 0);
                $masterDetails = "Districts: {$districtCount}, Mandis: {$marketCount}, Crops: {$cropCount}, Prices: {$priceCount}";
            } catch (\Throwable $e) {
                $masterDetails = 'Query error: ' . $e->getMessage();
            }
        }
        $checks[] = [
            'category' => 'Master Data',
            'check' => 'Karnataka Master Data Seeded',
            'status' => $masterDataOk ? 'PASS' : 'WARN',
            'details' => $masterDetails,
        ];

        // 7. Writable Filesystem Permissions
        $storageWritable = is_writable(storage_path());
        $cacheWritable = is_writable(base_path('bootstrap/cache'));
        $permsOk = $storageWritable && $cacheWritable;
        $checks[] = [
            'category' => 'Filesystem',
            'check' => 'Storage & Cache Write Permissions',
            'status' => $permsOk ? 'PASS' : 'FAIL',
            'details' => "storage/: " . ($storageWritable ? 'Writable' : 'NOT Writable') . ", bootstrap/cache/: " . ($cacheWritable ? 'Writable' : 'NOT Writable'),
        ];
        if (!$permsOk) $hasErrors = true;

        // 8. Public Build Assets
        $buildManifestExists = File::exists(public_path('build/manifest.json'));
        $checks[] = [
            'category' => 'Frontend',
            'check' => 'Vite Production Build Assets',
            'status' => $buildManifestExists ? 'PASS' : 'WARN',
            'details' => $buildManifestExists ? 'public/build/manifest.json present' : 'Run npm run build before deploying',
        ];

        // Output results
        if ($this->option('json')) {
            $this->line(json_encode([
                'status' => $hasErrors ? 'error' : 'ok',
                'timestamp' => now()->toIso8601String(),
                'checks' => $checks,
            ], JSON_PRETTY_PRINT));
            return $hasErrors ? 1 : 0;
        }

        $this->newLine();
        $this->info('========================================================================');
        $this->info('       Krushi Baandhava — Production Health & Deployment Verification   ');
        $this->info('========================================================================');
        $this->newLine();

        $rows = array_map(function ($c) {
            $statusFormatted = match ($c['status']) {
                'PASS' => '<info>PASS</info>',
                'WARN' => '<comment>WARN</comment>',
                default => '<error>FAIL</error>',
            };
            return [$c['category'], $c['check'], $statusFormatted, $c['details']];
        }, $checks);

        $this->table(['Category', 'Check', 'Status', 'Details'], $rows);
        $this->newLine();

        if ($hasErrors) {
            $this->error('✘ Production health check identified critical errors. Resolve before deploying.');
            return 1;
        }

        $this->info('✔ Production health check passed successfully. System is deployment-ready.');
        return 0;
    }
}

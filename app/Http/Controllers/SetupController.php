<?php

namespace App\Http\Controllers;

use App\Models\Crop;
use App\Models\District;
use App\Models\Market;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class SetupController extends Controller
{
    /**
     * Show the web setup / installation wizard.
     */
    public function index()
    {
        $lockFile = storage_path('installed');
        $isInstalled = File::exists($lockFile);

        $dbConnected = false;
        $dbError = null;
        $districtsCount = 0;
        $marketsCount = 0;
        $cropsCount = 0;

        try {
            DB::connection()->getPdo();
            $dbConnected = true;

            if (\Illuminate\Support\Facades\Schema::hasTable('districts')) {
                $districtsCount = District::count();
                $marketsCount = Market::count();
                $cropsCount = Crop::count();
            }

            if ($districtsCount > 0 && $cropsCount > 0 && $isInstalled) {
                return view('setup.locked', [
                    'districtsCount' => $districtsCount,
                    'marketsCount' => $marketsCount,
                    'cropsCount' => $cropsCount,
                    'installedAt' => $this->getInstalledTimestamp($lockFile),
                ]);
            }
        } catch (\Throwable $e) {
            $dbError = $e->getMessage();
        }

        // Prefill values from current environment
        $dbHost = config('database.connections.mysql.host', '127.0.0.1');
        $dbPort = config('database.connections.mysql.port', '3306');
        $dbDatabase = config('database.connections.mysql.database', 'krushi_baandhava');
        $dbUsername = config('database.connections.mysql.username', 'root');
        $appUrl = config('app.url', url('/'));
        $apiKey = config('services.data_gov_in.api_key', env('DATA_GOV_IN_API_KEY', ''));

        // System Diagnostic Checks
        $phpVersion = PHP_VERSION;
        $phpOk = version_compare($phpVersion, '8.2.0', '>=');
        $extensions = [
            'pdo' => extension_loaded('pdo'),
            'pdo_mysql' => extension_loaded('pdo_mysql'),
            'mbstring' => extension_loaded('mbstring'),
            'openssl' => extension_loaded('openssl'),
            'tokenizer' => extension_loaded('tokenizer'),
            'xml' => extension_loaded('xml'),
            'curl' => extension_loaded('curl'),
        ];
        $allExtensionsOk = !in_array(false, $extensions, true);

        $writablePaths = [
            'storage' => is_writable(storage_path()),
            'bootstrap_cache' => is_writable(base_path('bootstrap/cache')),
        ];

        return view('setup.index', compact(
            'isInstalled',
            'dbConnected',
            'dbError',
            'districtsCount',
            'marketsCount',
            'cropsCount',
            'phpVersion',
            'phpOk',
            'extensions',
            'allExtensionsOk',
            'writablePaths',
            'dbHost',
            'dbPort',
            'dbDatabase',
            'dbUsername',
            'appUrl',
            'apiKey'
        ));
    }

    /**
     * AJAX Endpoint: Test MySQL Database Connection.
     */
    public function testDb(Request $request)
    {
        $request->validate([
            'host' => 'required|string',
            'port' => 'required',
            'database' => 'required|string',
            'username' => 'required|string',
            'password' => 'nullable|string',
        ]);

        try {
            $dsn = "mysql:host={$request->host};port={$request->port};dbname={$request->database};charset=utf8mb4";
            new \PDO($dsn, $request->username, $request->password ?? '', [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_TIMEOUT => 4,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Successfully connected to MySQL database!',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * AJAX Endpoint: Test data.gov.in API key.
     */
    public function testApiKey(Request $request)
    {
        $apiKey = $request->input('api_key');
        if (empty($apiKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter an API key to test.',
            ], 422);
        }

        try {
            $response = Http::timeout(5)->get('https://api.data.gov.in/resource/9ef84268-d588-465a-a308-a864a43d0070', [
                'api-key' => $apiKey,
                'format' => 'json',
                'limit' => 1,
            ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'API Key verified successfully! Connection to data.gov.in established.',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'data.gov.in returned error: ' . $response->status() . ' - ' . ($response->json()['message'] ?? 'Invalid Key'),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not reach data.gov.in: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Execute the full installation, database setup, master data seeding, and security lockdown.
     */
    public function run(Request $request)
    {
        $lockFile = storage_path('installed');
        if (File::exists($lockFile)) {
            return response()->view('setup.locked', [
                'districtsCount' => District::count(),
                'marketsCount' => Market::count(),
                'cropsCount' => Crop::count(),
                'installedAt' => $this->getInstalledTimestamp($lockFile),
            ], 403);
        }

        $request->validate([
            'db_host' => 'required|string',
            'db_port' => 'required',
            'db_database' => 'required|string',
            'db_username' => 'required|string',
            'db_password' => 'nullable|string',
            'admin_name' => 'required|string|min:3|max:100',
            'admin_email' => 'required|email|max:150',
            'admin_password' => 'required|string|min:8|confirmed',
            'app_url' => 'nullable|url',
            'api_key' => 'nullable|string',
            'install_mandis' => 'nullable',
            'install_crops' => 'nullable',
            'sync_prices' => 'nullable',
        ]);

        try {
            // 1. Update .env with entered Database Credentials and API key
            $envUpdates = [
                'DB_HOST' => $request->db_host,
                'DB_PORT' => $request->db_port,
                'DB_DATABASE' => $request->db_database,
                'DB_USERNAME' => $request->db_username,
                'DB_PASSWORD' => $request->db_password ?? '',
            ];

            if ($request->filled('app_url')) {
                $envUpdates['APP_URL'] = $request->app_url;
            }

            if ($request->filled('api_key')) {
                $envUpdates['DATA_GOV_IN_API_KEY'] = $request->api_key;
            }

            $this->updateEnvironmentFile($envUpdates);

            // 2. Reconfigure active database connection
            config([
                'database.connections.mysql.host' => $request->db_host,
                'database.connections.mysql.port' => $request->db_port,
                'database.connections.mysql.database' => $request->db_database,
                'database.connections.mysql.username' => $request->db_username,
                'database.connections.mysql.password' => $request->db_password ?? '',
            ]);
            DB::purge('mysql');
            DB::connection()->getPdo();

            // 3. Run Migrations
            Artisan::call('migrate', ['--force' => true]);

            // 4. Run Core Settings & CMS Seeders
            Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\SystemSettingSeeder', '--force' => true]);
            Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\FeatureFlagSeeder', '--force' => true]);
            Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\DataSourceSeeder', '--force' => true]);
            Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\CedaDataSourceSeeder', '--force' => true]);
            Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\AgriculturalCmsSeeder', '--force' => true]);

            // 5. Seed Karnataka APMC Mandis Master (Zero Manual Entry)
            if ($request->boolean('install_mandis', true)) {
                Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\GeographicSeeder', '--force' => true]);
                Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\KarnatakaMandiAliasSeeder', '--force' => true]);
            }

            // 6. Seed Karnataka Crop Commodities Master (Zero Manual Entry)
            if ($request->boolean('install_crops', true)) {
                Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\CropMasterSeeder', '--force' => true]);
                Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\AddMissedKarnatakaCropsSeeder', '--force' => true]);
            }

            // 7. Create Custom Super Admin Account
            $firstDistrict = District::first();
            User::updateOrCreate(
                ['email' => $request->admin_email],
                [
                    'name' => $request->admin_name,
                    'phone' => '9800000001',
                    'role' => User::ROLE_SUPER_ADMIN,
                    'preferred_language' => 'kn',
                    'district_id' => $firstDistrict?->id,
                    'password' => Hash::make($request->admin_password),
                    'email_verified_at' => now(),
                ]
            );

            // 8. Sync Live APMC Prices if requested
            if ($request->boolean('sync_prices', false)) {
                try {
                    Artisan::call('sync:market-prices', ['--source' => 'data_gov_mandi']);
                } catch (\Throwable $e) {
                    // Log but do not break setup
                    report($e);
                }
            }

            // 9. Optimize & Clear Caches
            Artisan::call('optimize:clear');

            // 10. Permanently Lock Setup Wizard
            File::put($lockFile, json_encode([
                'installed_at' => now()->toIso8601String(),
                'version' => '1.0.0',
                'admin_email' => $request->admin_email,
                'districts_count' => District::count(),
                'markets_count' => Market::count(),
                'crops_count' => Crop::count(),
            ], JSON_PRETTY_PRINT));

            return redirect()->route('admin.login')->with('success', 'Krushi Baandhava has been installed and secured successfully! Setup is now permanently locked. Please log in with your Super Admin account.');

        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Installation failed: ' . $e->getMessage());
        }
    }

    /**
     * Safely update .env file keys.
     */
    protected function updateEnvironmentFile(array $values): void
    {
        $envPath = base_path('.env');
        if (!File::exists($envPath)) {
            if (File::exists(base_path('.env.example'))) {
                File::copy(base_path('.env.example'), $envPath);
            } else {
                File::put($envPath, '');
            }
        }

        $content = File::get($envPath);
        foreach ($values as $key => $value) {
            $formattedValue = (str_contains($value, ' ') || str_contains($value, '#') || str_contains($value, '$'))
                ? '"' . addcslashes($value, '"\\') . '"'
                : $value;

            if (preg_match("/^{$key}=/m", $content)) {
                $content = preg_replace("/^{$key}=.*/m", "{$key}={$formattedValue}", $content);
            } else {
                $content .= "\n{$key}={$formattedValue}";
            }
        }
        File::put($envPath, $content);
    }

    /**
     * Get installation timestamp string.
     */
    protected function getInstalledTimestamp(string $lockFile): string
    {
        try {
            $data = json_decode(File::get($lockFile), true);
            return $data['installed_at'] ?? 'Previously Installed';
        } catch (\Throwable $e) {
            return 'Installed';
        }
    }
}

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
        if ($this->isLocked()) {
            return $this->lockedResponse();
        }

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

        return view('setup.index', [
            'isInstalled' => false,
            'dbConnected' => $dbConnected,
            'dbError' => $dbError,
            'districtsCount' => $districtsCount,
            'marketsCount' => $marketsCount,
            'cropsCount' => $cropsCount,
            'phpVersion' => $phpVersion,
            'phpOk' => $phpOk,
            'extensions' => $extensions,
            'allExtensionsOk' => $allExtensionsOk,
            'writablePaths' => $writablePaths,
            'dbHost' => $dbHost,
            'dbPort' => $dbPort,
            'dbDatabase' => $dbDatabase,
            'dbUsername' => $dbUsername,
            'appUrl' => $appUrl,
            'apiKey' => $apiKey,
        ]);
    }

    /**
     * AJAX Endpoint: Test MySQL Database Connection.
     */
    public function testDb(Request $request)
    {
        if ($this->isLocked()) {
            return response()->json([
                'success' => false,
                'message' => 'Setup wizard is permanently locked.',
            ], 403);
        }

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
        if ($this->isLocked()) {
            return response()->json([
                'success' => false,
                'message' => 'Setup wizard is permanently locked.',
            ], 403);
        }

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
        if ($this->isLocked()) {
            return $this->lockedResponse();
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
            $lockFile = storage_path('installed');
            File::put($lockFile, json_encode([
                'installed_at' => now()->toIso8601String(),
                'version' => '1.0.0',
                'admin_email' => $request->admin_email,
                'districts_count' => District::count(),
                'markets_count' => Market::count(),
                'crops_count' => Crop::count(),
            ], JSON_PRETTY_PRINT));

            $this->updateEnvironmentFile([
                'APP_INSTALLED' => 'true',
                'ENABLE_SETUP_WIZARD' => 'false',
            ]);

            return redirect()->route('admin.login')->with('success', 'Krushi Baandhava has been installed and secured successfully! Setup is now permanently locked. Please log in with your Super Admin account.');

        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Installation failed: ' . $e->getMessage());
        }
    }

    /**
     * Check if setup is permanently locked.
     */
    protected function isLocked(): bool
    {
        $lockFile = storage_path('installed');
        if (File::exists($lockFile)) {
            return true;
        }

        // Check environment locks
        $appInstalled = filter_var(env('APP_INSTALLED', false), FILTER_VALIDATE_BOOLEAN);
        $enableWizard = env('ENABLE_SETUP_WIZARD') !== null 
            ? filter_var(env('ENABLE_SETUP_WIZARD'), FILTER_VALIDATE_BOOLEAN) 
            : null;

        if ($appInstalled || $enableWizard === false) {
            $this->ensureLockFileExists('Environment set to installed or wizard disabled');
            return true;
        }

        // In production, setup is strictly locked unless explicitly ENABLE_SETUP_WIZARD=true
        if (app()->isProduction() && $enableWizard !== true) {
            $this->ensureLockFileExists('Production environment automatic lockdown');
            return true;
        }

        // Auto-detect existing active database schema and catalog/users
        try {
            DB::connection()->getPdo();

            $hasUsers = \Illuminate\Support\Facades\Schema::hasTable('users') 
                && User::where('role', '!=', User::ROLE_FARMER)->exists();

            $hasData = \Illuminate\Support\Facades\Schema::hasTable('districts') 
                && District::count() > 0 
                && \Illuminate\Support\Facades\Schema::hasTable('crops') 
                && Crop::count() > 0;

            if ($hasUsers || $hasData) {
                $this->ensureLockFileExists('Active database detected with existing users or agricultural data');
                $this->updateEnvironmentFile([
                    'APP_INSTALLED' => 'true',
                    'ENABLE_SETUP_WIZARD' => 'false',
                ]);
                return true;
            }
        } catch (\Throwable $e) {
            // Database not yet connected or tables don't exist yet
        }

        return false;
    }

    /**
     * Return standard 403 response with locked view.
     */
    protected function lockedResponse()
    {
        $districtsCount = 0;
        $marketsCount = 0;
        $cropsCount = 0;

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('districts')) {
                $districtsCount = District::count();
                $marketsCount = Market::count();
                $cropsCount = Crop::count();
            }
        } catch (\Throwable $e) {
            // DB not reachable
        }

        return response()->view('setup.locked', [
            'districtsCount' => $districtsCount,
            'marketsCount' => $marketsCount,
            'cropsCount' => $cropsCount,
            'installedAt' => $this->getInstalledTimestamp(storage_path('installed')),
        ], 403);
    }

    /**
     * Ensure the storage/installed lockfile exists.
     */
    protected function ensureLockFileExists(string $reason = 'Installed'): void
    {
        $lockFile = storage_path('installed');
        if (!File::exists($lockFile)) {
            try {
                $districtsCount = 0;
                $marketsCount = 0;
                $cropsCount = 0;

                if (\Illuminate\Support\Facades\Schema::hasTable('districts')) {
                    $districtsCount = District::count();
                    $marketsCount = Market::count();
                    $cropsCount = Crop::count();
                }

                File::put($lockFile, json_encode([
                    'installed_at' => now()->toIso8601String(),
                    'reason' => $reason,
                    'districts_count' => $districtsCount,
                    'markets_count' => $marketsCount,
                    'crops_count' => $cropsCount,
                ], JSON_PRETTY_PRINT));
            } catch (\Throwable $e) {
                @file_put_contents($lockFile, 'LOCKED: ' . now()->toIso8601String());
            }
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
            if (!File::exists($lockFile)) {
                return 'Active System';
            }
            $data = json_decode(File::get($lockFile), true);
            return $data['installed_at'] ?? 'Previously Installed';
        } catch (\Throwable $e) {
            return 'Installed';
        }
    }
}

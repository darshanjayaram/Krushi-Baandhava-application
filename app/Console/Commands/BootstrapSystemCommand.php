<?php

namespace App\Console\Commands;

use App\Models\Crop;
use App\Models\District;
use App\Models\Market;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class BootstrapSystemCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:bootstrap
                            {--force : Force the operation without confirmation}
                            {--sync-prices : Synchronize live prices from data.gov.in after initialization}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fresh install bootstrap: Run migrations, seed all Karnataka districts, APMC mandis, crops, settings, and admin account.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("=================================================");
        $this->info("🌾 Krushi Baandhava — Fresh Deployment Bootstrap");
        $this->info("=================================================\n");

        if (!$this->option('force') && app()->isProduction()) {
            if (!$this->confirm('This will initialize tables and seed all Karnataka master data. Do you wish to continue?')) {
                $this->warn('Bootstrap aborted.');
                return self::FAILURE;
            }
        }

        // 1. Run Migrations
        $this->line("⏳ Step 1/4: Running database migrations...");
        $exitCode = Artisan::call('migrate', ['--force' => true], $this->output);
        if ($exitCode !== 0) {
            $this->error("❌ Migration failed. Check your database connection credentials in .env.");
            return self::FAILURE;
        }
        $this->info("   ✅ Database tables created successfully.\n");

        // 2. Run Database Seeders
        $this->line("⏳ Step 2/4: Seeding Karnataka master data (Districts, APMCs, Crops, Varietals, Admin)...");
        $exitCode = Artisan::call('db:seed', ['--force' => true], $this->output);
        if ($exitCode !== 0) {
            $this->error("❌ Database seeding failed.");
            return self::FAILURE;
        }
        $this->info("   ✅ Master data seeded successfully.\n");

        // 3. Mark Installed
        $lockFile = storage_path('installed');
        File::put($lockFile, json_encode([
            'installed_at' => now()->toIso8601String(),
            'version' => '1.0.0',
            'environment' => config('app.env'),
        ], JSON_PRETTY_PRINT));

        // 4. Verification Summary
        $districtsCount = District::count();
        $marketsCount = Market::count();
        $cropsCount = Crop::count();
        $adminUser = User::where('role', 'admin')->first();

        $this->line("⏳ Step 3/4: Optimizing application caches...");
        Artisan::call('optimize:clear');
        $this->info("   ✅ Caches cleared.\n");

        $this->info("=================================================");
        $this->info("🎉 Krushi Baandhava Initialized Successfully!");
        $this->info("=================================================");
        $this->line("   📍 Karnataka Districts: {$districtsCount}");
        $this->line("   🏢 APMC Markets:        {$marketsCount}");
        $this->line("   🌱 Canonical Crops:     {$cropsCount}");
        if ($adminUser) {
            $this->line("   🔑 Admin Login:         {$adminUser->email} / password");
        }
        $this->info("=================================================\n");

        // Optional Step 4: Sync Live Prices
        if ($this->option('sync-prices')) {
            $this->line("⏳ Step 4/4: Synchronizing live APMC prices from data.gov.in...");
            Artisan::call('sync:market-prices', ['--source' => 'data_gov_mandi'], $this->output);
            $this->info("   ✅ Live mandi prices synchronized.\n");
        }

        return self::SUCCESS;
    }
}

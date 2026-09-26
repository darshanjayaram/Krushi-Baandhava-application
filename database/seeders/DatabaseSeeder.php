<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            GeographicSeeder::class,
            CropMasterSeeder::class,
            AdminUserSeeder::class,
            FeatureFlagSeeder::class,
            SystemSettingSeeder::class,
            DataSourceSeeder::class,
            CedaDataSourceSeeder::class,
            TssSirsiDataSourceSeeder::class,
            AgriculturalCmsSeeder::class,
            ComprehensiveKarnatakaMarketPricesSeeder::class,
            AddMissedKarnatakaCropsSeeder::class,
            KarnatakaMandiAliasSeeder::class,
        ]);
    }
}

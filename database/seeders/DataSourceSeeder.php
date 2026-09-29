<?php

namespace Database\Seeders;

use App\Models\DataSource;
use App\Services\DataSources\CoconutBoard\CoconutBoardDataProvider;
use App\Services\DataSources\CoffeeBoard\CoffeeBoardDataProvider;
use Illuminate\Database\Seeder;

class DataSourceSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Coffee Board of India (Direct Web Scraper)
        DataSource::updateOrCreate(
            ['code' => 'coffee_board'],
            [
                'name' => 'Coffee Board of India (Direct Web Scraper)',
                'provider_class' => CoffeeBoardDataProvider::class,
                'type' => 'market_prices',
                'base_url' => 'https://coffeeboard.gov.in',
                'endpoint' => '',
                'auth_type' => 'none',
                'sync_frequency' => 'daily',
                'is_active' => true,
                'timeout_seconds' => 20,
            ]
        );

        // 2. Coconut Development Board (Direct Web Scraper)
        DataSource::updateOrCreate(
            ['code' => 'coconut_board'],
            [
                'name' => 'Coconut Development Board (Direct Web Scraper)',
                'provider_class' => CoconutBoardDataProvider::class,
                'type' => 'market_prices',
                'base_url' => 'https://coconutboard.gov.in',
                'endpoint' => '',
                'auth_type' => 'none',
                'sync_frequency' => 'daily',
                'is_active' => true,
                'timeout_seconds' => 20,
            ]
        );
    }
}

<?php

namespace App\Services\DataSources;

use App\Models\DataSource;
use App\Services\DataSources\Agmarknet\AgmarknetMarketDataProvider;
use App\Services\DataSources\CoconutBoard\CoconutBoardDataProvider;
use App\Services\DataSources\CoffeeBoard\CoffeeBoardDataProvider;
use App\Services\DataSources\Contracts\MarketDataProviderInterface;
use App\Services\DataSources\DataGov\DataGovMarketDataProvider;
use App\Services\DataSources\Krama\KramaMarketDataProvider;
use InvalidArgumentException;

class DataSourceRegistry
{
    /**
     * Map of known provider classes with human-readable labels.
     *
     * @return array<string, string>
     */
    public static function getAvailableProviders(): array
    {
        return [
            DataGovMarketDataProvider::class => 'data.gov.in Mandi Prices Provider (Official Open Data)',
            AgmarknetMarketDataProvider::class => 'Agmarknet Market Data Provider (National APMC Portal)',
            CoffeeBoardDataProvider::class => 'Coffee Board of India Provider (Arabica & Robusta Daily Rates)',
            CoconutBoardDataProvider::class => 'Coconut Development Board Provider (Coconut & Copra Rates)',
            KramaMarketDataProvider::class => 'KRAMA Karnataka State APMC Provider (State Access Placeholder)',
        ];
    }

    /**
     * Resolve a provider adapter instance for the given DataSource.
     */
    public static function make(DataSource $dataSource): MarketDataProviderInterface
    {
        $class = $dataSource->provider_class;

        if (!class_exists($class)) {
            throw new InvalidArgumentException("Provider class [{$class}] does not exist.");
        }

        if (!is_subclass_of($class, MarketDataProviderInterface::class)) {
            throw new InvalidArgumentException("Provider class [{$class}] must implement MarketDataProviderInterface.");
        }

        if (!$dataSource->relationLoaded('credential')) {
            $dataSource->load('credential');
        }

        return new $class($dataSource);
    }
}

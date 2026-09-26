<?php

namespace App\Services\DataSources;

use App\Models\DataSource;
use App\Services\DataSources\Ceda\CedaAgmarknetDataProvider;
use App\Services\DataSources\CoconutBoard\CoconutBoardDataProvider;
use App\Services\DataSources\CoffeeBoard\CoffeeBoardDataProvider;
use App\Services\DataSources\Contracts\MarketDataProviderInterface;
use App\Services\DataSources\DataGov\DataGovMarketDataProvider;
use App\Services\DataSources\TssSirsi\TssSirsiDataProvider;
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
            CedaAgmarknetDataProvider::class => 'CEDA Agmarknet Provider (Ashoka University — Authenticated Mandi REST API & Historical Time-Series)',
            CoffeeBoardDataProvider::class => 'Coffee Board of India (Direct Website Web Scraper — Arabica & Robusta Daily Rates)',
            CoconutBoardDataProvider::class => 'Coconut Development Board (Direct Website Web Scraper — Coconut & Copra Rates)',
            TssSirsiDataProvider::class => 'TSS Sirsi Cooperative Society (Arecanut Tender Auction Rates — Rashi, Chali, Bette, Bilegotu, Kempugotu)',
        ];
    }

    protected static array $customResolvers = [];

    public static function register(string $code, callable $resolver): void
    {
        static::$customResolvers[$code] = $resolver;
    }

    public static function reset(): void
    {
        static::$customResolvers = [];
    }

    /**
     * Resolve a provider adapter instance for the given DataSource.
     */
    public static function make(DataSource $dataSource): MarketDataProviderInterface
    {
        if (isset(static::$customResolvers[$dataSource->code])) {
            return (static::$customResolvers[$dataSource->code])($dataSource);
        }

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

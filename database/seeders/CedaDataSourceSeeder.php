<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CedaDataSourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * CEDA Agmarknet has been superseded by KRAMA (Primary Live Feed)
     * and Official AGMARKNET (Multi-Year Historical Deep Archives & Predictions).
     */
    public function run(): void
    {
        $this->call(KramaAndAgmarknetSeeder::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketSourceMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'data_source_id',
        'source_market_name',
        'source_district_name',
        'market_id',
        'confidence_score',
        'is_verified',
    ];

    protected function casts(): array
    {
        return [
            'confidence_score' => 'decimal:2',
            'is_verified' => 'boolean',
        ];
    }

    public function dataSource(): BelongsTo
    {
        return $this->belongsTo(DataSource::class);
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }
}

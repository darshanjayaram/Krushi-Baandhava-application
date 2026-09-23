<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketArrival extends Model
{
    use HasFactory;

    protected $fillable = [
        'market_id',
        'crop_id',
        'variety_id',
        'arrival_date',
        'quantity',
        'unit',
        'data_source_id',
    ];

    protected function casts(): array
    {
        return [
            'arrival_date' => 'date',
            'quantity' => 'decimal:2',
        ];
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function crop(): BelongsTo
    {
        return $this->belongsTo(Crop::class);
    }

    public function variety(): BelongsTo
    {
        return $this->belongsTo(CropVariety::class, 'variety_id');
    }

    public function dataSource(): BelongsTo
    {
        return $this->belongsTo(DataSource::class);
    }
}
